<?php
// controller/report/route.php

// Sertakan file konfigurasi database
require_once '../db/config.php';

/**
 * Mengambil data Laporan Outbound Request yang sudah dikonfirmasi, difilter berdasarkan tanggal.
 * Alias 'T1' digunakan untuk menghindari konflik dengan kata kunci SQL 'OR'.
 */
function getOutboundReportData($conn, $start_date, $end_date)
{
    $sql = "
        SELECT
            T1.id AS or_id,
            T1.order_date,
            T1.sla_date,
            T1.order_number,
            T1.qty AS request_qty,
            T1.keterangan,
            T1.jenis,
            T1.created_at AS or_created_at,
            I.item,
            I.brand,
            K.supply_qty,
            K.discrepancy,
            K.status,
            K.created_at AS konfirmasi_at
        FROM outbound_request AS T1
        JOIN item AS I ON T1.id_item = I.id
        INNER JOIN konfirmasi AS K ON T1.id = K.id_outbond_request
        WHERE
            T1.deleted_at IS NULL
            AND T1.order_date BETWEEN ? AND ?
        ORDER BY
            T1.order_date DESC, T1.id DESC 
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}

// --- Main Handler ---

$conn = getDbConnection();

$action = $_GET['action'] ?? null;
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

if (empty($startDate) || empty($endDate)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tanggal awal dan akhir harus diisi.']);
    exit;
}

if ($action === 'read') {
    header('Content-Type: application/json');
    $data = getOutboundReportData($conn, $startDate, $endDate);

    // Format tanggal untuk tampilan yang lebih baik
    $formattedData = array_map(function ($row) {
        $row['or_created_at_fmt'] = date('d-m-Y H:i', strtotime($row['or_created_at']));
        $row['konfirmasi_at_fmt'] = date('d-m-Y H:i', strtotime($row['konfirmasi_at']));
        return $row;
    }, $data);

    echo json_encode(['status' => 'success', 'data' => $formattedData]);
} else {
    // Menghapus logika 'export' dari BE
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid atau tidak didukung.']);
}

// Tutup koneksi
if (isset($conn)) {
    $conn->close();
}
