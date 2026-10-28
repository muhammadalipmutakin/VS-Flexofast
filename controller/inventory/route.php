<?php
// controller/inventory/route.php (REVISED - Include Confirmation Date and Item List)

header('Content-Type: application/json');
// Asumsi: Path file koneksi DB
require_once '../db/config.php'; // Sesuaikan path jika berbeda

// Dapatkan koneksi DB
$conn = getDbConnection();

// --- Database Functions ---

/**
 * Membaca Data Outbound Request dengan JOIN ke Item dan Konfirmasi
 */
function readOutboundRequests($conn, $filters)
{
    $startDate = $filters['start_date'] ?? date('Y-m-d');
    $endDate = $filters['end_date'] ?? date('Y-m-d');
    $dateField = $filters['date_field'] ?? 'order_date';
    $itemFilter = $filters['item'] ?? '';
    $jenisFilter = $filters['jenis'] ?? '';
    $statusFilter = $filters['status'] ?? '';

    $sql = "
        SELECT 
            orq.id, orq.order_date, orq.sla_date, orq.order_number, 
            orq.qty, orq.keterangan, orq.jenis, orq.is_confirm,
            i.item, i.desc, i.brand,
            k.id AS konfirmasi_id, k.supply_qty, k.discrepancy, k.status AS konfirmasi_status,
            DATE_FORMAT(k.created_at, '%Y-%m-%d %H:%i') AS konfirmasi_at 
        FROM outbound_request orq
        INNER JOIN item i ON orq.id_item = i.id
        LEFT JOIN konfirmasi k ON orq.id = k.id_outbond_request
        WHERE orq.deleted_at IS NULL
    ";

    $params = [];
    $types = "";

    // Filter tanggal
    $sql .= " AND orq.{$dateField} BETWEEN ? AND ? ";
    $types .= "ss";
    $params[] = $startDate;
    $params[] = $endDate;

    // Filter Item
    if (!empty($itemFilter)) {
        $sql .= " AND i.item = ? ";
        $types .= "s";
        $params[] = $itemFilter;
    }

    // Filter Jenis
    if (!empty($jenisFilter)) {
        $sql .= " AND orq.jenis = ? ";
        $types .= "s";
        $params[] = $jenisFilter;
    }

    // Filter Status Konfirmasi
    if (!empty($statusFilter)) {
        if ($statusFilter === 'Terkonfirmasi') {
            $sql .= " AND orq.is_confirm = TRUE ";
        } elseif ($statusFilter === 'Belum Terkonfirmasi') {
            $sql .= " AND orq.is_confirm = FALSE ";
        } else {
            $sql .= " AND k.status = ? ";
            $types .= "s";
            $params[] = $statusFilter;
        }
    }

    $sql .= " ORDER BY orq.order_date DESC, orq.id DESC";

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $requests = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return [
        'status' => 'success',
        'data' => $requests
    ];
}

/**
 * Fungsi baru: Mendapatkan daftar item untuk dropdown
 */
function getItemList($conn)
{
    // PERBAIKAN: Menambahkan backticks pada `desc` karena merupakan reserved keyword
    $sql = "SELECT id, item, `desc`, brand FROM item WHERE deleted_at IS NULL ORDER BY item ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return [
        'status' => 'success',
        'data' => $items
    ];
}

/**
 * Melakukan Konfirmasi Awal (Membuat entri di tabel konfirmasi)
 */
function confirmRequest($conn, $data)
{
    $outboundRequestId = $data['id_outbond_request'] ?? null;
    $supplyQty = $data['supply_qty'] ?? null;
    $requestQty = $data['request_qty'] ?? null;

    if (empty($outboundRequestId) || !is_numeric($supplyQty) || !is_numeric($requestQty) || $supplyQty < 0) {
        return ['status' => 'error', 'message' => 'Data konfirmasi tidak valid.'];
    }

    $supplyQty = (int)$supplyQty;
    $requestQty = (int)$requestQty;
    $discrepancy = $supplyQty - $requestQty;
    $status = calculateStatus($discrepancy);

    // 1. Cek apakah sudah ada konfirmasi (untuk mencegah duplikasi)
    $stmt_check = $conn->prepare("SELECT COUNT(id) FROM konfirmasi WHERE id_outbond_request = ?");
    $stmt_check->bind_param("i", $outboundRequestId);
    $stmt_check->execute();
    $count = 0;
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($count > 0) {
        return ['status' => 'error', 'message' => 'Request ini sudah dikonfirmasi. Gunakan aksi UPDATE (Edit Konfirmasi).'];
    }

    $conn->begin_transaction();

    try {
        // 2. Insert ke tabel konfirmasi (created_at akan terisi otomatis oleh database)
        $stmt_conf = $conn->prepare("INSERT INTO konfirmasi (id_outbond_request, supply_qty, discrepancy, status) VALUES (?, ?, ?, ?)");
        $stmt_conf->bind_param("iids", $outboundRequestId, $supplyQty, $discrepancy, $status);
        if (!$stmt_conf->execute()) {
            throw new Exception("Gagal menyimpan konfirmasi: " . $stmt_conf->error);
        }
        $stmt_conf->close();

        // 3. Update is_confirm di outbound_request
        $stmt_update = $conn->prepare("UPDATE outbound_request SET is_confirm = TRUE WHERE id = ? AND deleted_at IS NULL");
        $stmt_update->bind_param("i", $outboundRequestId);
        if (!$stmt_update->execute()) {
            throw new Exception("Gagal mengupdate outbound request: " . $stmt_update->error);
        }
        $stmt_update->close();

        $conn->commit();

        return ['status' => 'success', 'message' => "Permintaan berhasil dikonfirmasi dengan status: **{$status}**."];
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error Konfirmasi: " . $e->getMessage());
        return ['status' => 'error', 'message' => "Gagal Konfirmasi: " . $e->getMessage()];
    }
}

/**
 * Mengupdate Konfirmasi (Fitur Edit)
 */
function updateConfirmation($conn, $data)
{
    $konfirmasiId = $data['konfirmasi_id'] ?? null;
    $supplyQty = $data['supply_qty'] ?? null;
    $requestQty = $data['request_qty'] ?? null;

    // Validasi
    if (empty($konfirmasiId) || !is_numeric($supplyQty) || !is_numeric($requestQty) || $supplyQty < 0) {
        return ['status' => 'error', 'message' => 'Data update konfirmasi tidak valid.'];
    }

    $supplyQty = (int)$supplyQty;
    $requestQty = (int)$requestQty;
    $discrepancy = $supplyQty - $requestQty;
    $status = calculateStatus($discrepancy);

    $sql = "UPDATE konfirmasi SET supply_qty=?, discrepancy=?, status=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisi", $supplyQty, $discrepancy, $status, $konfirmasiId);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return ['status' => 'success', 'message' => "Konfirmasi berhasil diupdate menjadi status: **{$status}**."];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Tidak ada perubahan data atau konfirmasi tidak ditemukan.'];
        }
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['status' => 'error', 'message' => "Gagal mengupdate konfirmasi: " . $error];
    }
}

/**
 * Helper untuk menentukan status berdasarkan Discrepancy
 */
function calculateStatus($discrepancy)
{
    if ($discrepancy > 0) {
        return 'Fullfill Lebih';
    } elseif ($discrepancy < 0) {
        return 'Kurang Supply';
    } else {
        return 'Fullfill Pas';
    }
}

// --- Routing Logic ---
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

if ($method === 'GET') {
    switch ($action) {
        case 'read':
            // Ambil filter dari GET request
            $filters = [
                'start_date' => $_GET['start_date'] ?? null,
                'end_date' => $_GET['end_date'] ?? null,
                'date_field' => $_GET['date_field'] ?? null,
                'item' => $_GET['item'] ?? null,
                'jenis' => $_GET['jenis'] ?? null,
                'status' => $_GET['status'] ?? null,
            ];

            // Set default tanggal hari ini
            if (empty($filters['start_date'])) $filters['start_date'] = date('Y-m-d');
            if (empty($filters['end_date'])) $filters['end_date'] = date('Y-m-d');
            if (empty($filters['date_field'])) $filters['date_field'] = 'order_date';

            $response = readOutboundRequests($conn, $filters);
            echo json_encode($response);
            break;

        case 'get_items':
            // Endpoint baru untuk mendapatkan daftar item
            $response = getItemList($conn);
            echo json_encode($response);
            break;

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
            break;
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;

    if (empty($action) || !isset($input)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid atau data kosong.']);
        exit;
    }

    $response = ['status' => 'error', 'message' => 'Aksi tidak dikenal.'];

    switch ($action) {
        case 'confirm':
            $response = confirmRequest($conn, $input);
            break;
        case 'update_confirm':
            $response = updateConfirmation($conn, $input);
            break;
        default:
            break;
    }

    echo json_encode($response);
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
}

if (isset($conn)) {
    $conn->close();
}
