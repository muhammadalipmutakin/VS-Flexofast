<?php
// controller/email/route.php

header('Content-Type: application/json');

// ASUMSI: Path file koneksi DB
require_once '../db/config.php';

// Dapatkan koneksi DB (asumsi fungsi ini ada di ../db/config.php)
$conn = getDbConnection(); 

// --- Database Functions (MySQLi Implementation) ---

/**
 * Membuat Email Baru (Hanya jika belum ada untuk role tersebut)
 */
function createEmail($conn, $data)
{
    $nama = trim($data['nama'] ?? '');
    $email = trim($data['email'] ?? '');
    $role = $data['role'] ?? '';

    if (empty($nama) || empty($email) || !in_array($role, ['atasan_1', 'atasan_2'])) {
        return ['status' => 'error', 'message' => 'Data tidak lengkap atau Role tidak valid.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 'error', 'message' => 'Format email tidak valid.'];
    }

    // Cek apakah role sudah terisi
    $stmt_check = $conn->prepare("SELECT COUNT(id) FROM email WHERE role = ? AND deleted_at IS NULL");
    $stmt_check->bind_param("s", $role);
    $stmt_check->execute();
    $count = 0;
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($count > 0) {
        return ['status' => 'error', 'message' => "Email untuk Role '{$role}' sudah ada. Silakan lakukan Update."];
    }

    $stmt = $conn->prepare("INSERT INTO email (nama, email, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nama, $email, $role);

    if ($stmt->execute()) {
        $stmt->close();
        return ['status' => 'success', 'message' => "Email '{$role}' berhasil ditambahkan."];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['status' => 'error', 'message' => "Gagal menambahkan email: " . $error];
    }
}

/**
 * Membaca Email (Filter hanya yang aktif)
 */
function readEmails($conn)
{
    $sql = "SELECT id, nama, email, role FROM email WHERE deleted_at IS NULL ORDER BY role ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $emails = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Format ulang data agar mudah diakses di FE (key by role)
    $formatted_emails = [
        'atasan_1' => null,
        'atasan_2' => null
    ];
    foreach ($emails as $email) {
        $formatted_emails[$email['role']] = $email;
    }

    return [
        'status' => 'success',
        'data' => $formatted_emails
    ];
}

/**
 * Memperbarui Email
 */
function updateEmail($conn, $data)
{
    $id = $data['id'] ?? null;
    $nama = trim($data['nama'] ?? '');
    $email = trim($data['email'] ?? '');

    if (empty($id) || empty($nama) || empty($email)) {
        return ['status' => 'error', 'message' => 'ID, Nama, dan Email tidak boleh kosong.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 'error', 'message' => 'Format email tidak valid.'];
    }

    // Cek apakah email sudah digunakan oleh data lain (kecuali data ini sendiri)
    $stmt_check_email = $conn->prepare("SELECT id FROM email WHERE email = ? AND id != ? AND deleted_at IS NULL");
    $stmt_check_email->bind_param("si", $email, $id);
    $stmt_check_email->execute();
    $stmt_check_email->store_result();
    if ($stmt_check_email->num_rows > 0) {
        $stmt_check_email->close();
        return ['status' => 'error', 'message' => "Email '{$email}' sudah digunakan oleh data lain."];
    }
    $stmt_check_email->close();

    $stmt = $conn->prepare("UPDATE email SET nama=?, email=? WHERE id=? AND deleted_at IS NULL");
    $stmt->bind_param("ssi", $nama, $email, $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'Email berhasil diperbarui.'];
        } else {
            $stmt->close();
            // Periksa apakah data memang tidak ada (ID salah)
            $stmt_check_id = $conn->prepare("SELECT id FROM email WHERE id = ? AND deleted_at IS NULL");
            $stmt_check_id->bind_param("i", $id);
            $stmt_check_id->execute();
            $stmt_check_id->store_result();
            if ($stmt_check_id->num_rows === 0) {
                return ['status' => 'error', 'message' => 'Email yang diedit tidak ditemukan.'];
            }
            $stmt_check_id->close();

            // Jika data ada tapi affected_rows = 0, berarti tidak ada perubahan
            return ['status' => 'error', 'message' => 'Tidak ada perubahan data.'];
        }
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['status' => 'error', 'message' => "Gagal memperbarui email: " . $error];
    }
}


// --- Routing Logic (PERBAIKAN: Mengganti case 'update' menjadi 'edit') ---
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

if ($method === 'GET' && $action === 'read') {
    $response = readEmails($conn);
    echo json_encode($response);
} elseif ($method === 'POST') {
    $input_json = file_get_contents('php://input');
    $input = json_decode($input_json, true);

    // 1. Cek jika JSON parsing gagal
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Error: JSON input tidak valid.']);
        if (isset($conn)) $conn->close();
        exit;
    }

    $action = $input['action'] ?? null; // Ambil action dari body JSON POST

    // 2. Cek apakah action kosong
    if (empty($action)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid atau action kosong dalam body JSON.']);
        if (isset($conn)) $conn->close();
        exit;
    }

    $response = ['status' => 'error', 'message' => 'Aksi tidak dikenal.'];

    switch ($action) {
        case 'create':
            $response = createEmail($conn, $input);
            break;
        case 'edit': // *** PERUBAHAN DI SINI: Mengubah 'update' menjadi 'edit' ***
            $response = updateEmail($conn, $input);
            break;
        default:
            // Jika aksi terdeteksi tapi bukan 'create' atau 'edit'
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
