<?php
// controller/outbound/route.php

header('Content-Type: application/json');

// Asumsi: Path file koneksi DB
require_once '../db/config.php';

// Dapatkan koneksi DB (asumsi fungsi ini ada di ../db/config.php)
// Asumsi: getDbConnection() sudah didefinisikan di ../db/config.php
$conn = getDbConnection();

// --- Database Functions ---

/**
 * Mengambil semua Item aktif untuk dropdown
 */
function readItemsForDropdown($conn)
{
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
 * Membuat Permintaan Outbound baru
 */
function createOutboundRequest($conn, $data)
{
    // 1. Validasi Input Dasar
    $order_date = $data['order_date'] ?? null;
    $sla_date = $data['sla_date'] ?? null;
    $order_number = $data['order_number'] ?? null;
    $id_item = $data['id_item'] ?? null;
    $qty = $data['qty'] ?? null;
    $keterangan = $data['keterangan'] ?? null;
    $jenis = $data['jenis'] ?? null;

    if (empty($id_item) || empty($jenis)) {
        return ['status' => 'error', 'message' => 'Item dan Jenis permintaan wajib diisi.'];
    }

    if (!in_array($jenis, ['butuh_validasi', 'butuh_replen'])) {
        return ['status' => 'error', 'message' => 'Jenis permintaan tidak valid.'];
    }

    // 2. Bersihkan dan Konversi data
    // Konversi string kosong ke NULL untuk DATE dan VARCHAR (Order Number)
    $order_date = empty($order_date) ? NULL : $order_date;
    $sla_date = empty($sla_date) ? NULL : $sla_date;
    $order_number = ($order_number === '' || $order_number === null) ? NULL : $order_number;

    // Konversi Keterangan. Penting: Keterangan (TEXT) harus di-bind sebagai string 's'.
    $keterangan = ($keterangan === '' || $keterangan === null) ? NULL : $keterangan;

    // Konversi QTY ke integer atau NULL
    $qty = is_numeric($qty) ? (int)$qty : NULL;

    // Pastikan id_item adalah integer
    $id_item = (int)$id_item;


    // 3. Persiapan Query
    $sql = "INSERT INTO outbound_request (order_date, sla_date, order_number, id_item, qty, keterangan, jenis) 
             VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    // BARIS PENTING: Perubahan Type Binding!
    // Format: sssisis (s=date, s=date, s=varchar, i=int, i=int, s=text, s=enum)
    // Sebelumnya: "sssisis" (dimana 's' ke-5 adalah QTY, 'i' ke-6 adalah KETERANGAN)
    // Sekarang:   "sssiiss"
    $stmt->bind_param(
        "sssiiss", // Perbaikan Type Binding: sss (date/varchar) i (id_item) i (qty) s (keterangan) s (jenis)
        $order_date,
        $sla_date,
        $order_number,
        $id_item,
        $qty,
        $keterangan,
        $jenis
    );

    // 4. Eksekusi
    if ($stmt->execute()) {
        $stmt->close();
        return ['status' => 'success', 'message' => "Permintaan {$jenis} berhasil diajukan."];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['status' => 'error', 'message' => "Gagal menyimpan permintaan: " . $error];
    }
}

// --- Routing Logic ---
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$response = ['status' => 'error', 'message' => 'Aksi tidak dikenal.'];

if ($method === 'GET') {
    if ($action === 'read_items') {
        $response = readItemsForDropdown($conn);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;

    if ($action === 'create') {
        $response = createOutboundRequest($conn, $input);
    }
}

echo json_encode($response);

if (isset($conn)) {
    $conn->close();
}
