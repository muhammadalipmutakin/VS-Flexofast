<?php
// controller/outbound/manage_route.php

header('Content-Type: application/json');

// Asumsi: Path file koneksi DB
require_once '../db/config.php';

// Dapatkan koneksi DB (asumsi fungsi ini ada di ../db/config.php)
$conn = getDbConnection();

// --- Database Functions ---

/**
 * Mengambil semua Permintaan Outbound dengan filter, termasuk status konfirmasi.
 */
function readOutboundRequests($conn, $filters)
{
    // Filter
    $start_date = $filters['start_date'] ?? null;
    $end_date = $filters['end_date'] ?? null;
    $item_id = $filters['item_id'] ?? null;
    $jenis = $filters['jenis'] ?? null;
    $limit = 20; // Batasan data, bisa diatur dinamis
    $offset = 0; // Offset, bisa diatur dinamis

    $where = "r.deleted_at IS NULL";
    $params = [];
    $types = "";

    if (!empty($start_date)) {
        $where .= " AND r.order_date >= ?";
        $params[] = $start_date;
        $types .= "s";
    }
    if (!empty($end_date)) {
        $where .= " AND r.order_date <= ?";
        $params[] = $end_date;
        $types .= "s";
    }
    if (!empty($item_id)) {
        // Pastikan $item_id adalah integer sebelum ditambahkan ke parameter SQL
        if (is_numeric($item_id)) {
            $where .= " AND r.id_item = ?";
            $params[] = (int)$item_id;
            $types .= "i";
        }
    }
    if (!empty($jenis) && in_array($jenis, ['butuh_validasi', 'butuh_replen'])) {
        $where .= " AND r.jenis = ?";
        $params[] = $jenis;
        $types .= "s";
    }

    $sql = "SELECT 
                r.id, 
                r.order_date, 
                r.sla_date, 
                r.order_number, 
                r.qty AS requested_qty, 
                r.keterangan, 
                r.jenis, 
                r.is_confirm, 
                i.item, 
                i.`desc`, 
                i.brand,
                k.status AS konfirmasi_status
            FROM 
                outbound_request r
            LEFT JOIN 
                item i ON r.id_item = i.id
            LEFT JOIN
                konfirmasi k ON r.id = k.id_outbond_request
            WHERE 
                $where
            ORDER BY 
                r.created_at DESC
            LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($sql);

    // Bind parameter dinamis
    if ($types) {
        // mysqli_stmt::bind_param requires parameters to be passed by reference
        $bind_names = array($types);
        for ($i = 0; $i < count($params); $i++) {
            $bind_names[] = &$params[$i];
        }
        // Pastikan ada parameter untuk dibinding
        if (count($params) > 0) {
            call_user_func_array(array($stmt, 'bind_param'), $bind_names);
        }
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
 * Memeriksa apakah id_item ada di tabel item.
 */
function checkIfItemExists($conn, $id_item)
{
    // Hanya cek jika id_item > 0, karena 0 pasti tidak valid di tabel master
    if ($id_item <= 0) {
        return false;
    }
    $sql = "SELECT id FROM item WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // Gagal mempersiapkan query, anggap error dan kembalikan false
        return false;
    }
    $stmt->bind_param("i", $id_item);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}


/**
 * Mengupdate Permintaan Outbound yang BELUM dikonfirmasi.
 */
function updateOutboundRequest($conn, $data)
{
    $id = $data['id'] ?? null;
    $order_date = $data['order_date'] ?? null;
    $sla_date = $data['sla_date'] ?? null;
    $order_number = $data['order_number'] ?? null;
    $id_item = $data['id_item'] ?? null;
    $qty = $data['qty'] ?? null;
    $keterangan = $data['keterangan'] ?? null;
    $jenis = $data['jenis'] ?? null;

    if (empty($id) || empty($id_item) || empty($jenis)) {
        return ['status' => 'error', 'message' => 'ID, Item, dan Jenis permintaan wajib diisi.'];
    }

    // 1. Cek apakah sudah dikonfirmasi
    $check_sql = "SELECT is_confirm FROM outbound_request WHERE id = ? AND deleted_at IS NULL";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        return ['status' => 'error', 'message' => 'Gagal mempersiapkan query cek konfirmasi: ' . $conn->error];
    }
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $request_data = $check_result->fetch_assoc();
    $check_stmt->close();

    if (!$request_data) {
        return ['status' => 'error', 'message' => 'Permintaan tidak ditemukan.'];
    }
    if ($request_data['is_confirm']) {
        return ['status' => 'error', 'message' => 'Permintaan yang sudah dikonfirmasi tidak bisa diubah.'];
    }

    // 2. Bersihkan dan Konversi data (Sama seperti fungsi create)
    $order_date = empty($order_date) ? NULL : $order_date;
    $sla_date = empty($sla_date) ? NULL : $sla_date;
    $order_number = ($order_number === '' || $order_number === null) ? NULL : $order_number;
    $keterangan = ($keterangan === '' || $keterangan === null) ? NULL : $keterangan;
    $qty = is_numeric($qty) ? (int)$qty : NULL;

    // --- PERBAIKAN: Validasi input id_item sebelum casting ---
    if (!is_numeric($id_item) || $id_item <= 0) {
        return ['status' => 'error', 'message' => "ID Item ({$id_item}) tidak valid. Harus berupa angka positif."];
    }
    $id_item = (int)$id_item;
    // ----------------------------------------

    // --- Validasi Foreign Key (dari perbaikan sebelumnya) ---
    if (!checkIfItemExists($conn, $id_item)) {
        return ['status' => 'error', 'message' => "ID Item ({$id_item}) tidak ditemukan di master data item."];
    }
    // -------------------------------------------------------

    // 3. Persiapan Query Update
    $sql = "UPDATE outbound_request 
            SET order_date = ?, sla_date = ?, order_number = ?, id_item = ?, qty = ?, keterangan = ?, jenis = ?
            WHERE id = ? AND deleted_at IS NULL";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['status' => 'error', 'message' => 'Gagal mempersiapkan query update: ' . $conn->error];
    }

    // Type binding: sssiissi (8 parameter total)
    $stmt->bind_param(
        "sssiissi",
        $order_date,
        $sla_date,
        $order_number,
        $id_item,
        $qty,
        $keterangan,
        $jenis,
        $id
    );

    // 4. Eksekusi
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        $stmt->close();
        if ($affected > 0) {
            return ['status' => 'success', 'message' => "Permintaan ID: {$id} berhasil diupdate."];
        } else {
            return ['status' => 'success', 'message' => "Permintaan ID: {$id} tidak mengalami perubahan (pastikan data baru berbeda dari data lama)."];
        }
    } else {
        $error = $stmt->error;
        $stmt->close();
        // Menampilkan error detail jika terjadi error SQL lain
        return ['status' => 'error', 'message' => "Gagal mengupdate permintaan: " . $error];
    }
}

/**
 * Melakukan Soft Delete pada Permintaan Outbound yang BELUM dikonfirmasi.
 */
function deleteOutboundRequest($conn, $id)
{
    if (empty($id)) {
        return ['status' => 'error', 'message' => 'ID permintaan wajib diisi.'];
    }

    // 1. Cek apakah sudah dikonfirmasi
    $check_sql = "SELECT is_confirm FROM outbound_request WHERE id = ? AND deleted_at IS NULL";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        return ['status' => 'error', 'message' => 'Gagal mempersiapkan query cek konfirmasi: ' . $conn->error];
    }
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $request_data = $check_result->fetch_assoc();
    $check_stmt->close();

    if (!$request_data) {
        return ['status' => 'error', 'message' => 'Permintaan tidak ditemukan atau sudah dihapus.'];
    }
    if ($request_data['is_confirm']) {
        return ['status' => 'error', 'message' => 'Permintaan yang sudah dikonfirmasi tidak bisa dihapus.'];
    }

    // 2. Soft Delete
    $sql = "UPDATE outbound_request SET deleted_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['status' => 'error', 'message' => 'Gagal mempersiapkan query soft delete: ' . $conn->error];
    }
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $stmt->close();
        return ['status' => 'success', 'message' => "Permintaan ID: {$id} berhasil dihapus (soft delete)."];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['status' => 'error', 'message' => "Gagal menghapus permintaan: " . $error];
    }
}

// --- Routing Logic ---
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$response = ['status' => 'error', 'message' => 'Aksi tidak dikenal.'];

if ($method === 'GET') {
    if ($action === 'read') {
        // Ambil filter dari query string
        $filters = [
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'item_id' => $_GET['item_id'] ?? null,
            'jenis' => $_GET['jenis'] ?? null,
        ];
        $response = readOutboundRequests($conn, $filters);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;

    if ($action === 'update') {
        $response = updateOutboundRequest($conn, $input);
    } elseif ($action === 'delete') {
        $id = $input['id'] ?? null;
        $response = deleteOutboundRequest($conn, $id);
    }
}

echo json_encode($response);

if (isset($conn)) {
    $conn->close();
}
