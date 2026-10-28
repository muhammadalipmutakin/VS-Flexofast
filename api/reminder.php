<?php
// api/reminder-checker.php
header('Content-Type: application/json');

// Set timezone to Asia/Jakarta (UTC+7)
date_default_timezone_set('Asia/Jakarta');

// Load environment and database config
require_once '../controller/db/config.php';

class SimpleMailer
{
    private $socket;
    private $host;
    private $port;
    private $username;
    private $password;
    private $fromEmail;
    private $fromName;

    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->fromEmail = $config['from_email'];
        $this->fromName = $config['from_name'];
    }

    private function connect()
    {
        $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, 30);
        if (!$this->socket) {
            throw new Exception("Cannot connect to SMTP server: $errstr ($errno)");
        }
        $this->readResponse();
        return $this;
    }

    private function sendCommand($command, $expectedCode = null)
    {
        fputs($this->socket, $command . "\r\n");
        if ($expectedCode !== null) {
            $response = $this->readResponse();
            if (substr($response, 0, 3) != $expectedCode) {
                throw new Exception("SMTP error: Expected $expectedCode, got: $response");
            }
        }
    }

    private function readResponse()
    {
        $response = '';
        while ($str = fgets($this->socket, 4096)) {
            $response .= $str;
            if (substr($str, 3, 1) == ' ') break;
        }
        return $response;
    }

    public function sendEmail($toEmail, $toName, $subject, $body)
    {
        try {
            $this->connect();

            // EHLO
            $this->sendCommand("EHLO " . $this->host, 250);

            // Start TLS
            $this->sendCommand("STARTTLS", 220);
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Cannot enable TLS encryption");
            }
            $this->sendCommand("EHLO " . $this->host, 250);

            // Auth
            $this->sendCommand("AUTH LOGIN", 334);
            $this->sendCommand(base64_encode($this->username), 334);
            $this->sendCommand(base64_encode($this->password), 235);

            // Set from and to
            $this->sendCommand("MAIL FROM: <{$this->fromEmail}>", 250);
            $this->sendCommand("RCPT TO: <{$toEmail}>", 250);

            // Data
            $this->sendCommand("DATA", 354);

            // Email headers and content
            $emailData = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
            $emailData .= "To: {$toName} <{$toEmail}>\r\n";
            $emailData .= "Subject: {$subject}\r\n";
            $emailData .= "MIME-Version: 1.0\r\n";
            $emailData .= "Content-Type: text/html; charset=\"UTF-8\"\r\n";
            $emailData .= "\r\n";
            $emailData .= $body . "\r\n";
            $emailData .= ".\r\n";

            $this->sendCommand($emailData, 250);

            // Quit
            $this->sendCommand("QUIT", 221);

            fclose($this->socket);
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            if ($this->socket) {
                fclose($this->socket);
            }
            return false;
        }
    }
}

class ReminderChecker
{
    private $conn;
    private $mailer;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initializeMailer();
        $this->setDatabaseTimezone();
    }

    private function setDatabaseTimezone()
    {
        // Set MySQL timezone to Asia/Jakarta
        $this->conn->query("SET time_zone = '+07:00'");
    }

    private function initializeMailer()
    {
        $mailConfig = [
            'host' => $_ENV['EMAIL_HOST'] ?? 'smtp.gmail.com',
            'port' => $_ENV['EMAIL_PORT'] ?? 587,
            'username' => $_ENV['EMAIL_USER'] ?? '',
            'password' => $_ENV['EMAIL_PASS'] ?? '',
            'from_email' => $_ENV['EMAIL_USER'] ?? '',
            'from_name' => $_ENV['EMAIL_FROM_NAME'] ?? 'VS Flexo System'
        ];

        $this->mailer = new SimpleMailer($mailConfig);
    }

    /**
     * Get pending outbound requests that need reminders (3 hari terakhir saja)
     */
    private function getPendingRequests()
    {
        $currentTime = date('Y-m-d H:i:s');

        // Cari request yang belum dikonfirmasi dan dibuat dalam 3 hari terakhir
        $query = "
            SELECT 
                orq.id,
                orq.order_number,
                orq.order_date,
                orq.sla_date,
                orq.qty,
                orq.keterangan,
                orq.jenis,
                orq.created_at,
                i.item,
                i.brand,
                TIMESTAMPDIFF(MINUTE, orq.created_at, ?) as minutes_passed
            FROM outbound_request orq
            JOIN item i ON orq.id_item = i.id
            WHERE orq.is_confirm = FALSE
            AND orq.deleted_at IS NULL
            AND orq.created_at >= DATE_SUB(?, INTERVAL 3 DAY)
            ORDER BY orq.created_at ASC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $currentTime, $currentTime);
        $stmt->execute();
        $result = $stmt->get_result();

        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        $stmt->close();

        return $requests;
    }

    /**
     * Get email by role
     */
    private function getEmailByRole($role)
    {
        $query = "SELECT email, nama FROM email WHERE role = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $email = $result->fetch_assoc();
        $stmt->close();

        return $email ?: null;
    }

    /**
     * Group requests by reminder type
     */
    private function groupRequestsByReminderType($requests)
    {
        $grouped = [
            'hour_1' => [], // 1-2 jam
            'hour_2' => []  // 2+ jam
        ];

        foreach ($requests as $request) {
            $minutesPassed = $request['minutes_passed'];

            if ($minutesPassed >= 120) {
                $grouped['hour_2'][] = $request;
            } elseif ($minutesPassed >= 60) {
                $grouped['hour_1'][] = $request;
            }
        }

        return $grouped;
    }

    /**
     * Create email HTML content with card-based layout for mobile
     */
    private function createEmailContent($requests, $reminderType)
    {
        if ($reminderType === 1) {
            $title = "⏰ REMINDER 1 JAM - Permintaan Outbound Belum Dikonfirmasi";
            $timeInfo = "1-2 jam";
            $alertClass = "alert-warning";
            $alertIcon = "⏰";
        } else {
            $title = "🚨 REMINDER 2 JAM - Permintaan Outbound Belum Dikonfirmasi - PERLU TINDAKAN SEGERA";
            $timeInfo = "2+ jam";
            $alertClass = "alert-urgent";
            $alertIcon = "🚨";
        }

        $requestCount = count($requests);
        $currentTime = date('d/m/Y H:i:s');

        // Build request cards
        $requestCards = '';
        foreach ($requests as $index => $request) {
            $orderNumber = isset($request['order_number']) && !empty($request['order_number'])
                ? $request['order_number']
                : 'ID-' . $request['id'];
            $orderDate = isset($request['order_date']) && !empty($request['order_date'])
                ? date('d/m/Y', strtotime($request['order_date']))
                : '-';
            $slaDate = isset($request['sla_date']) && !empty($request['sla_date'])
                ? date('d/m/Y', strtotime($request['sla_date']))
                : '-';
            $keterangan = isset($request['keterangan']) && !empty($request['keterangan'])
                ? $request['keterangan']
                : '-';
            $waitingTime = $this->formatWaitingTime($request['minutes_passed']);
            $waitingColor = $reminderType === 2 ? '#dc3545' : '#856404';

            $requestCards .= "
            <div class=\"request-card\">
                <div class=\"card-header\">
                    <span class=\"request-number\">#" . ($index + 1) . "</span>
                    <span class=\"order-number\">" . htmlspecialchars($orderNumber) . "</span>
                    <span class=\"waiting-time\" style=\"color: {$waitingColor};\">" . htmlspecialchars($waitingTime) . "</span>
                </div>
                <div class=\"card-content\">
                    <div class=\"info-row\">
                        <span class=\"info-label\">Item:</span>
                        <span class=\"info-value\">" . htmlspecialchars($request['item']) . "</span>
                    </div>
                    <div class=\"info-row\">
                        <span class=\"info-label\">Brand:</span>
                        <span class=\"info-value\">" . htmlspecialchars($request['brand']) . "</span>
                    </div>
                    <div class=\"info-row\">
                        <span class=\"info-label\">Qty:</span>
                        <span class=\"info-value\">" . htmlspecialchars($request['qty']) . "</span>
                    </div>
                    <div class=\"info-row\">
                        <span class=\"info-label\">Jenis:</span>
                        <span class=\"info-value\">" . htmlspecialchars($request['jenis']) . "</span>
                    </div>
                    <div class=\"info-row\">
                        <span class=\"info-label\">Tgl Order:</span>
                        <span class=\"info-value\">" . htmlspecialchars($orderDate) . "</span>
                    </div>
                    <div class=\"info-row\">
                        <span class=\"info-label\">Tgl SLA:</span>
                        <span class=\"info-value\">" . htmlspecialchars($slaDate) . "</span>
                    </div>
                    <div class=\"info-row\">
                        <span class=\"info-label\">Keterangan:</span>
                        <span class=\"info-value\">" . htmlspecialchars($keterangan) . "</span>
                    </div>
                    <div class=\"info-row\">
                        <span class=\"info-label\">Dibuat:</span>
                        <span class=\"info-value\">" . htmlspecialchars($request['created_at']) . "</span>
                    </div>
                </div>
            </div>";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset=\"UTF-8\">
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body { 
                    font-family: 'Segoe UI', Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0; 
                    padding: 0; 
                    background: #f6f9fc;
                    -webkit-text-size-adjust: 100%;
                }
                
                .container { 
                    max-width: 100%; 
                    margin: 0 auto; 
                    background: white; 
                    overflow: hidden;
                }
                
                .header { 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    padding: 25px 20px; 
                    color: white;
                    text-align: center;
                }
                
                .header h1 { 
                    margin: 0; 
                    font-size: 22px; 
                    font-weight: 600;
                }
                
                .header p { 
                    margin: 8px 0 0 0; 
                    opacity: 0.9;
                    font-size: 14px;
                }
                
                .content { 
                    padding: 20px; 
                }
                
                .alert { 
                    padding: 18px; 
                    border-radius: 8px; 
                    margin: 15px 0; 
                    border-left: 4px solid;
                }
                
                .alert-warning { 
                    background: #fff3cd; 
                    border-color: #ffc107;
                    color: #856404;
                }
                
                .alert-urgent { 
                    background: #f8d7da; 
                    border-color: #dc3545;
                    color: #721c24;
                }
                
                .summary { 
                    background: #e7f3ff; 
                    padding: 15px; 
                    border-radius: 8px; 
                    margin: 15px 0;
                    border-left: 4px solid #007bff;
                }
                
                /* Request Cards Styling */
                .requests-container {
                    margin: 20px 0;
                }
                
                .request-card {
                    background: white;
                    border: 1px solid #e0e0e0;
                    border-radius: 8px;
                    margin-bottom: 15px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    overflow: hidden;
                }
                
                .card-header {
                    background: #f8f9fa;
                    padding: 12px 15px;
                    border-bottom: 1px solid #e0e0e0;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 10px;
                }
                
                .request-number {
                    background: #6c757d;
                    color: white;
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 12px;
                    font-weight: bold;
                }
                
                .order-number {
                    font-weight: bold;
                    font-size: 14px;
                    color: #333;
                    flex-grow: 1;
                    margin: 0 10px;
                }
                
                .waiting-time {
                    font-weight: bold;
                    font-size: 13px;
                    white-space: nowrap;
                }
                
                .card-content {
                    padding: 15px;
                }
                
                .info-row {
                    display: flex;
                    margin-bottom: 8px;
                    align-items: flex-start;
                }
                
                .info-row:last-child {
                    margin-bottom: 0;
                }
                
                .info-label {
                    font-weight: 600;
                    color: #495057;
                    min-width: 100px;
                    font-size: 13px;
                    flex-shrink: 0;
                }
                
                .info-value {
                    color: #333;
                    font-size: 13px;
                    flex-grow: 1;
                    word-break: break-word;
                }
                
                .action-box { 
                    background: #d1ecf1; 
                    padding: 18px; 
                    border-radius: 8px; 
                    margin: 20px 0;
                    border-left: 4px solid #17a2b8;
                }
                
                .footer { 
                    margin-top: 25px; 
                    padding-top: 18px; 
                    border-top: 1px solid #e9ecef; 
                    font-size: 11px; 
                    color: #6c757d;
                    text-align: center;
                }
                
                .status-badge { 
                    display: inline-block; 
                    padding: 6px 12px; 
                    background: #dc3545; 
                    color: white; 
                    border-radius: 20px; 
                    font-size: 12px; 
                    font-weight: 600;
                    margin: 5px 0;
                }
                
                .timezone-info {
                    font-size: 10px;
                    color: #6c757d;
                    margin-top: 3px;
                }
                
                /* Desktop Table View */
                .desktop-table {
                    display: none;
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                    font-size: 14px;
                }
                
                .desktop-table th {
                    background: #f8f9fa;
                    padding: 12px 8px;
                    text-align: left;
                    border-bottom: 2px solid #dee2e6;
                    font-weight: 600;
                    color: #495057;
                }
                
                .desktop-table td {
                    padding: 10px 8px;
                    border-bottom: 1px solid #dee2e6;
                }
                
                .desktop-table tr:hover {
                    background: #f8f9fa;
                }
                
                /* Show desktop table on larger screens */
                @media (min-width: 768px) {
                    .desktop-table {
                        display: table;
                    }
                    
                    .requests-container {
                        display: none;
                    }
                    
                    .content { 
                        padding: 25px; 
                    }
                    
                    .header { 
                        padding: 30px 25px; 
                    }
                    
                    .header h1 { 
                        font-size: 24px; 
                    }
                }
                
                /* Mobile optimizations */
                @media (max-width: 480px) {
                    .content { 
                        padding: 15px; 
                    }
                    
                    .header { 
                        padding: 20px 15px; 
                    }
                    
                    .header h1 { 
                        font-size: 20px; 
                    }
                    
                    .alert { 
                        padding: 15px; 
                    }
                    
                    .card-header {
                        padding: 10px 12px;
                    }
                    
                    .card-content {
                        padding: 12px;
                    }
                    
                    .info-label {
                        min-width: 80px;
                        font-size: 12px;
                    }
                    
                    .info-value {
                        font-size: 12px;
                    }
                }
            </style>
        </head>
        <body>
            <div class=\"container\">
                <div class=\"header\">
                    <h1>VS Flexo System</h1>
                    <p>Outbound Request Reminder</p>
                </div>
                
                <div class=\"content\">
                    <div class=\"alert {$alertClass}\">
                        <h3 style=\"margin: 0 0 8px 0; font-size: 18px;\">{$alertIcon} {$title}</h3>
                        <p style=\"margin: 0; font-size: 14px;\">Terdapat <strong>{$requestCount} permintaan outbound</strong> yang sudah menunggu konfirmasi selama <strong>{$timeInfo}</strong>" .
            ($reminderType === 2 ? " dan membutuhkan perhatian segera!" : ".") . "</p>
                    </div>
                    
                    <div class=\"summary\">
                        <h4 style=\"margin: 0 0 8px 0; color: #004085; font-size: 16px;\">📊 Ringkasan Permintaan</h4>
                        <p style=\"margin: 0; font-size: 14px;\">Total: <strong>{$requestCount} permintaan</strong> yang belum dikonfirmasi</p>
                    </div>
                    
                    <div>
                        <h3 style=\"margin-top: 0; color: #495057; font-size: 16px; margin-bottom: 15px;\">📋 Daftar Permintaan Outbound yang Belum Dikonfirmasi</h3>
                        
                        <!-- Desktop Table (shown on larger screens) -->
                        <table class=\"desktop-table\">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nomor Order</th>
                                    <th>Item</th>
                                    <th>Brand</th>
                                    <th>Qty</th>
                                    <th>Tgl Order</th>
                                    <th>Tgl SLA</th>
                                    <th>Keterangan</th>
                                    <th>Menunggu</th>
                                </tr>
                            </thead>
                            <tbody>
                                " . $this->createDesktopTableRows($requests, $reminderType) . "
                            </tbody>
                        </table>
                        
                        <!-- Mobile Cards (shown on smaller screens) -->
                        <div class=\"requests-container\">
                            {$requestCards}
                        </div>
                    </div>
                    
                    <div class=\"action-box\">
                        <h4 style=\"margin: 0 0 12px 0; color: #0c5460; font-size: 16px;\">📋 Tindakan yang Diperlukan</h4>
                        <p style=\"margin: 0 0 8px 0; font-size: 14px;\">Silakan login ke sistem VS Flexo untuk melakukan konfirmasi pada permintaan outbound di atas.</p>
                        <p style=\"margin: 0; font-size: 14px;\">Status saat ini: <span class=\"status-badge\">BELUM DIKONFIRMASI</span></p>
                    </div>
                    
                    <div class=\"footer\">
                        <p style=\"margin: 0 0 5px 0;\">Email ini dikirim secara otomatis oleh Sistem VS Flexo</p>
                        <p style=\"margin: 0 0 5px 0;\">Jam server: {$currentTime} <span class=\"timezone-info\">(WIB - UTC+7)</span></p>
                        <p style=\"margin: 0;\">© 2024 VS Flexo System. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Create desktop table rows
     */
    private function createDesktopTableRows($requests, $reminderType)
    {
        $rows = '';
        foreach ($requests as $index => $request) {
            $orderNumber = isset($request['order_number']) && !empty($request['order_number'])
                ? $request['order_number']
                : 'ID-' . $request['id'];
            $orderDate = isset($request['order_date']) && !empty($request['order_date'])
                ? date('d/m/Y', strtotime($request['order_date']))
                : '-';
            $slaDate = isset($request['sla_date']) && !empty($request['sla_date'])
                ? date('d/m/Y', strtotime($request['sla_date']))
                : '-';
            $keterangan = isset($request['keterangan']) && !empty($request['keterangan'])
                ? $request['keterangan']
                : '-';
            $waitingTime = $this->formatWaitingTime($request['minutes_passed']);
            $waitingColor = $reminderType === 2 ? '#dc3545' : '#856404';

            $rows .= "
            <tr>
                <td>" . ($index + 1) . "</td>
                <td><strong>" . htmlspecialchars($orderNumber) . "</strong></td>
                <td>" . htmlspecialchars($request['item']) . "</td>
                <td>" . htmlspecialchars($request['brand']) . "</td>
                <td>" . htmlspecialchars($request['qty']) . "</td>
                <td>" . htmlspecialchars($orderDate) . "</td>
                <td>" . htmlspecialchars($slaDate) . "</td>
                <td>" . htmlspecialchars($keterangan) . "</td>
                <td><strong style=\"color: {$waitingColor};\">" . htmlspecialchars($waitingTime) . "</strong></td>
            </tr>";
        }
        return $rows;
    }

    /**
     * Format waiting time from minutes to readable format
     */
    private function formatWaitingTime($minutes)
    {
        if ($minutes < 60) {
            return $minutes . ' menit';
        } elseif ($minutes < 120) {
            return '1 jam ' . ($minutes - 60) . ' menit';
        } else {
            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;
            return $hours . ' jam ' . $remainingMinutes . ' menit';
        }
    }

    /**
     * Send reminder email with all requests in one email
     */
    private function sendReminderEmail($toEmail, $toName, $requests, $reminderType)
    {
        try {
            $requestCount = count($requests);

            if ($reminderType === 1) {
                $subject = "⏰ REMINDER 1 JAM - {$requestCount} Permintaan Outbound Belum Dikonfirmasi";
            } else {
                $subject = "🚨 REMINDER 2 JAM - {$requestCount} Permintaan Outbound Belum Dikonfirmasi - PERLU TINDAKAN SEGERA";
            }

            $emailContent = $this->createEmailContent($requests, $reminderType);

            $emailSent = $this->mailer->sendEmail($toEmail, $toName, $subject, $emailContent);

            return $emailSent;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Main function to check and send reminders
     */
    public function checkAndSendReminders()
    {
        $results = [
            'status' => 'success',
            'checked_at' => date('d/m/Y H:i:s') . ' WIB',
            'reminders_sent' => 0,
            'total_requests_checked' => 0,
            'details' => []
        ];

        try {
            $pendingRequests = $this->getPendingRequests();
            $results['total_requests_checked'] = count($pendingRequests);

            if (empty($pendingRequests)) {
                $results['message'] = 'Tidak ada permintaan outbound yang membutuhkan reminder.';
                return $results;
            }

            // Group requests by reminder type
            $groupedRequests = $this->groupRequestsByReminderType($pendingRequests);

            // Send reminders for each type
            foreach ($groupedRequests as $type => $requests) {
                if (empty($requests)) {
                    continue;
                }

                $reminderType = ($type === 'hour_2') ? 2 : 1;
                $emailRole = ($type === 'hour_2') ? 'atasan_2' : 'atasan_1';
                $emailData = $this->getEmailByRole($emailRole);

                if ($emailData) {
                    $emailSent = $this->sendReminderEmail(
                        $emailData['email'],
                        $emailData['nama'],
                        $requests,
                        $reminderType
                    );

                    if ($emailSent) {
                        $results['reminders_sent']++;
                        $results['details'][] = [
                            'reminder_type' => $reminderType . '_hour',
                            'sent_to' => $emailData['email'],
                            'sent_to_name' => $emailData['nama'],
                            'requests_count' => count($requests),
                            'status' => 'success'
                        ];
                    } else {
                        $results['details'][] = [
                            'reminder_type' => $reminderType . '_hour',
                            'sent_to' => $emailData['email'],
                            'requests_count' => count($requests),
                            'status' => 'email_failed'
                        ];
                    }
                }
            }

            if ($results['reminders_sent'] > 0) {
                $results['message'] = "Berhasil mengirim {$results['reminders_sent']} reminder email yang mencakup {$results['total_requests_checked']} permintaan.";
            } else {
                $results['message'] = "Tidak ada reminder yang perlu dikirim dari {$results['total_requests_checked']} permintaan yang diperiksa.";
            }
        } catch (Exception $e) {
            $results['status'] = 'error';
            $results['message'] = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            error_log("Reminder checker error: " . $e->getMessage());
        }

        return $results;
    }
}

// Main execution
try {
    $conn = getDbConnection();
    $checker = new ReminderChecker($conn);

    $result = $checker->checkAndSendReminders();

    echo json_encode($result, JSON_PRETTY_PRINT);

    $conn->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
        'checked_at' => date('d/m/Y H:i:s') . ' WIB'
    ]);
}
