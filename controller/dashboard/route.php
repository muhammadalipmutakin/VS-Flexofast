<?php
// controller/dashboard/route.php

header('Content-Type: application/json');
date_default_timezone_set('Asia/Jakarta'); // Pastikan zona waktu yang benar

// Asumsi: Path file koneksi DB
require_once '../db/config.php';

// Fungsi untuk membuat respons error JSON
function errorResponse($message)
{
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit();
}

try {
    $conn = getDbConnection();
    $today = date('Y-m-d');

    // 1. Total Master Item
    $query_item_count = "
        SELECT COUNT(id) AS total_items
        FROM item
        WHERE deleted_at IS NULL
    ";
    $result_item_count = $conn->query($query_item_count);
    $item_count = $result_item_count->fetch_assoc();
    $total_items = (int)($item_count['total_items'] ?? 0);

    // 2. Ringkasan Statistik Hari Ini
    $query_summary = "
        SELECT
            COUNT(orq.id) AS total_requests_today,
            SUM(CASE WHEN orq.is_confirm = TRUE THEN 1 ELSE 0 END) AS total_confirmed_today,
            SUM(CASE WHEN orq.is_confirm = FALSE THEN 1 ELSE 0 END) AS total_unconfirmed_today,
            SUM(CASE WHEN k.status IN ('Fullfill Pas', 'Fullfill Lebih') THEN 1 ELSE 0 END) AS fullfill_count_today
        FROM outbound_request orq
        LEFT JOIN konfirmasi k ON orq.id = k.id_outbond_request
        WHERE DATE(orq.created_at) = '{$today}'
        AND orq.deleted_at IS NULL
    ";
    $result_summary = $conn->query($query_summary);
    if (!$result_summary) {
        throw new Exception("Query summary failed: " . $conn->error);
    }
    $summary = $result_summary->fetch_assoc();

    $total_requests_today = (int)($summary['total_requests_today'] ?? 0);
    $total_confirmed_today = (int)($summary['total_confirmed_today'] ?? 0);
    $total_unconfirmed_today = (int)($summary['total_unconfirmed_today'] ?? 0);
    $fullfill_count_today = (int)($summary['fullfill_count_today'] ?? 0);

    // Hitung Persentase Fullfill Hari Ini
    $safe_total_confirmed_today = $total_confirmed_today > 0 ? $total_confirmed_today : 1;
    $percentage_fullfill_today = round(($fullfill_count_today / $safe_total_confirmed_today) * 100, 2);


    // 3. Data Chart: Tren Permintaan dan Fullfill 7 Hari Terakhir
    $query_trend = "
        SELECT
            DATE(orq.created_at) AS report_date,
            COUNT(orq.id) AS total_requests,
            SUM(CASE WHEN orq.is_confirm = TRUE THEN 1 ELSE 0 END) AS total_confirmed,
            SUM(CASE WHEN k.status IN ('Fullfill Pas', 'Fullfill Lebih') THEN 1 ELSE 0 END) AS fullfill_count
        FROM outbound_request orq
        LEFT JOIN konfirmasi k ON orq.id = k.id_outbond_request
        WHERE orq.deleted_at IS NULL
        AND DATE(orq.created_at) >= DATE_SUB('{$today}', INTERVAL 6 DAY)
        GROUP BY report_date
        ORDER BY report_date ASC
    ";
    $result_trend = $conn->query($query_trend);
    if (!$result_trend) {
        throw new Exception("Query trend failed: " . $conn->error);
    }
    $trend_data = [];
    while ($row = $result_trend->fetch_assoc()) {
        $confirmed = (int)$row['total_confirmed'];
        $fullfill = (int)$row['fullfill_count'];
        $safe_confirmed = $confirmed > 0 ? $confirmed : 1;
        $percentage = round(($fullfill / $safe_confirmed) * 100, 2);

        $trend_data[$row['report_date']] = [
            'total_requests' => (int)$row['total_requests'],
            'percentage_fullfill' => $percentage
        ];
    }

    // Siapkan data 7 hari lengkap (untuk Chart.js)
    $chart_labels = [];
    $chart_requests = [];
    $chart_fullfill_percent = [];

    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days", strtotime($today)));
        $label = date('d/m', strtotime($date));

        $chart_labels[] = $label;
        $chart_requests[] = $trend_data[$date]['total_requests'] ?? 0;
        $chart_fullfill_percent[] = $trend_data[$date]['percentage_fullfill'] ?? 0;
    }

    // 4. Data Tabel Detail Item Hari Ini
    $query_detail_today = "
        SELECT
            it.item,
            SUM(orq.qty) AS total_requested_qty,
            SUM(COALESCE(k.supply_qty, 0)) AS total_supplied_qty,
            COUNT(orq.id) AS total_requests,
            SUM(CASE WHEN orq.is_confirm = TRUE THEN 1 ELSE 0 END) AS total_confirmed,
            SUM(CASE WHEN k.status IN ('Fullfill Pas', 'Fullfill Lebih') THEN 1 ELSE 0 END) AS fullfill_count
        FROM outbound_request orq
        JOIN item it ON orq.id_item = it.id
        LEFT JOIN konfirmasi k ON orq.id = k.id_outbond_request
        WHERE orq.deleted_at IS NULL
        AND DATE(orq.created_at) = '{$today}'
        GROUP BY it.item
        ORDER BY it.item ASC
    ";
    $result_detail_today = $conn->query($query_detail_today);
    $details_today = $result_detail_today->fetch_all(MYSQLI_ASSOC);

    // 5. Data Tabel Permintaan Hari Ini (Sudah Konfirm)
    $query_confirmed_today = "
        SELECT
            orq.id,
            orq.order_number,
            DATE_FORMAT(orq.order_date, '%d-%m-%Y') AS order_date,
            it.item,
            orq.qty AS requested_qty,
            k.supply_qty,
            k.status
        FROM outbound_request orq
        JOIN item it ON orq.id_item = it.id
        JOIN konfirmasi k ON orq.id = k.id_outbond_request
        WHERE orq.is_confirm = TRUE
        AND orq.deleted_at IS NULL
        AND DATE(orq.created_at) = '{$today}'
        ORDER BY orq.created_at DESC
    ";
    $result_confirmed_today = $conn->query($query_confirmed_today);
    $confirmed_today = $result_confirmed_today->fetch_all(MYSQLI_ASSOC);

    // 6. Data Tabel Permintaan Hari Ini (Belum Konfirm)
    $query_unconfirmed_today = "
        SELECT
            orq.id,
            orq.order_number,
            DATE_FORMAT(orq.order_date, '%d-%m-%Y') AS order_date,
            it.item,
            orq.qty,
            orq.jenis
        FROM outbound_request orq
        JOIN item it ON orq.id_item = it.id
        WHERE orq.is_confirm = FALSE
        AND orq.deleted_at IS NULL
        AND DATE(orq.created_at) = '{$today}'
        ORDER BY orq.created_at ASC
    ";
    $result_unconfirmed_today = $conn->query($query_unconfirmed_today);
    $unconfirmed_today = $result_unconfirmed_today->fetch_all(MYSQLI_ASSOC);


    // Kirimkan respon JSON
    echo json_encode([
        'status' => 'success',
        'data' => [
            'summary' => [
                'total_items' => $total_items,
                'total_requests_today' => $total_requests_today,
                'total_confirmed_today' => $total_confirmed_today,
                'total_unconfirmed_today' => $total_unconfirmed_today,
                'percentage_fullfill_today' => $percentage_fullfill_today
            ],
            'chart_trend' => [
                'labels' => $chart_labels,
                'requests' => $chart_requests,
                'fullfill_percent' => $chart_fullfill_percent
            ],
            'confirmed_today' => $confirmed_today,
            'unconfirmed_today' => $unconfirmed_today,
            'details_today' => $details_today
        ]
    ]);
} catch (Exception $e) {
    errorResponse('Gagal mengambil data dashboard: ' . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
