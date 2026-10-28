<?php
// controller/item/route.php

header('Content-Type: application/json');

// Sertakan file konfigurasi database yang baru dengan path yang Anda berikan
// Pastikan file db/config.php berada satu level di atas folder controller/item/
require_once '../db/config.php';

// --- Database Functions (MySQLi Implementation) ---

/**
 * Membuat Item Baru
 */
function createItem($conn, $data)
{
    $item = $data['item'] ?? '';
    $desc = $data['desc'] ?? null;
    $brand = $data['brand'] ?? null;

    if (empty($item)) {
        return ['status' => 'error', 'message' => 'Nama item tidak boleh kosong.'];
    }

    $stmt = $conn->prepare("INSERT INTO item (item, `desc`, brand) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $item, $desc, $brand);

    if ($stmt->execute()) {
        $stmt->close();
        return ['status' => 'success', 'message' => 'Item baru berhasil ditambahkan.'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['status' => 'error', 'message' => "Gagal menambahkan item: " . $error];
    }
}

/**
 * Membaca Item dengan Pagination dan Pencarian
 */
function readItems($conn, $page, $limit, $search)
{
    $offset = ($page - 1) * $limit;
    $search_param = '%' . $search . '%';

    // 1. Hitung Total Data (Untuk Pagination)
    $count_sql = "SELECT COUNT(id) AS total FROM item WHERE deleted_at IS NULL ";
    $where_clause = "";
    if (!empty($search)) {
        $where_clause = "AND (item LIKE ? OR `desc` LIKE ? OR brand LIKE ?) ";
        $count_sql .= $where_clause;
    }

    $stmt_count = $conn->prepare($count_sql);
    if (!empty($search)) {
        $stmt_count->bind_param("sss", $search_param, $search_param, $search_param);
    }
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $total_items = $result_count->fetch_assoc()['total'];
    $stmt_count->close();

    // 2. Ambil Data Item
    $data_sql = "SELECT id, item, `desc`, brand FROM item WHERE deleted_at IS NULL ";
    $data_sql .= $where_clause;
    $data_sql .= "ORDER BY id DESC LIMIT ?, ?";

    $stmt_data = $conn->prepare($data_sql);

    // Dynamic binding parameters for LIMIT
    $bind_types = "";
    $bind_params = [];

    if (!empty($search)) {
        $bind_types = "sss";
        $bind_params[] = $search_param;
        $bind_params[] = $search_param;
        $bind_params[] = $search_param;
    }

    $bind_types .= "ii";
    $bind_params[] = &$offset; // Gunakan referensi untuk limit/offset
    $bind_params[] = &$limit;

    // Tambahkan $stmt_data sebagai parameter pertama untuk bind_param
    array_unshift($bind_params, $bind_types);

    // Menggunakan ReflectionClass untuk mengatasi keterbatasan call_user_func_array pada PHP >= 8.0,
    // atau menggunakan operator spread (...) jika Anda yakin PHP >= 5.6.
    // Karena kode ini kompleks, kita akan menggunakan operator spread (...) yang lebih modern
    // dan memastikan array_shift di atas berfungsi untuk menghapus $bind_types dari $bind_params

    // Hapus $bind_types dari $bind_params untuk persiapan pemanggilan $stmt_data->bind_param()
    $bind_types = array_shift($bind_params);
    $stmt_data->bind_param($bind_types, ...$bind_params);


    $stmt_data->execute();
    $result_data = $stmt_data->get_result();
    $items = $result_data->fetch_all(MYSQLI_ASSOC);
    $stmt_data->close();

    return [
        'status' => 'success',
        'data' => [
            'items' => $items,
            'total_items' => (int)$total_items
        ]
    ];
}

/**
 * Memperbarui Item
 */
function updateItem($conn, $data)
{
    $id = $data['id'] ?? null;
    $item = $data['item'] ?? '';
    $desc = $data['desc'] ?? null;
    $brand = $data['brand'] ?? null;

    if (empty($id) || empty($item)) {
        return ['status' => 'error', 'message' => 'ID dan Nama item tidak boleh kosong.'];
    }

    $stmt = $conn->prepare("UPDATE item SET item=?, `desc`=?, brand=? WHERE id=? AND deleted_at IS NULL");
    $stmt->bind_param("sssi", $item, $desc, $brand, $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'Item berhasil diperbarui.'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Item tidak ditemukan atau tidak ada perubahan data.'];
        }
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['status' => 'error', 'message' => "Gagal memperbarui item: " . $error];
    }
}

/**
 * Menghapus Item (Soft Delete)
 */
function deleteItem($conn, $id)
{
    if (empty($id)) {
        return ['status' => 'error', 'message' => 'ID item tidak valid.'];
    }

    $stmt = $conn->prepare("UPDATE item SET deleted_at=NOW() WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'Item berhasil dihapus (Soft Deleted).'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Item tidak ditemukan.'];
        }
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['status' => 'error', 'message' => "Gagal menghapus item: " . $error];
    }
}
// --- Akhir Database Functions ---


$conn = getDbConnection(); // Ambil koneksi DB dari db/config.php

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

if ($method === 'GET' && $action === 'read') {
    $page = (int)($_GET['p'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 30);
    $search = trim($_GET['q'] ?? '');

    $response = readItems($conn, $page, $limit, $search);
    echo json_encode($response);
} elseif ($method === 'POST') {
    // Pastikan menerima JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;

    if (empty($action) || !isset($input)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid atau data kosong.']);
        exit;
    }

    $response = ['status' => 'error', 'message' => 'Aksi tidak dikenal.'];

    switch ($action) {
        case 'create':
            $response = createItem($conn, $input);
            break;
        case 'update':
            $response = updateItem($conn, $input);
            break;
        case 'delete':
            $response = deleteItem($conn, $input['id'] ?? null);
            break;
        default:
            break;
    }

    echo json_encode($response);
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
}

// Tutup koneksi setelah selesai
if (isset($conn)) {
    $conn->close();
}
