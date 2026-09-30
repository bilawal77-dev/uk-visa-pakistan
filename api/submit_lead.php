<?php
/**
 * UK Visa Pakistan - Submit Consultation Lead Endpoint
 * Accepts JSON or Form POST and records into MySQL `leads` table.
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

require_once __DIR__ . '/db.php';

// Parse JSON or standard POST input
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!$input) {
    $input = $_POST;
}

$name      = trim($input['name'] ?? 'Applicant');
$phone     = trim($input['phone'] ?? 'N/A');
$email     = trim($input['email'] ?? 'N/A');
$visaRoute = trim($input['visaRoute'] ?? ($input['route'] ?? 'Standard Visitor Visa'));
$message   = trim($input['message'] ?? 'General inquiry via website.');

if (empty($name) || (empty($phone) && empty($email))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Name and contact details (Phone or Email) are required.']);
    exit;
}

$pdo = getDBConnection();
$savedToDb = false;
$insertedId = null;

if ($pdo !== null) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `leads` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `phone` varchar(100) NOT NULL,
            `email` varchar(255) NOT NULL,
            `visa_route` varchar(255) NOT NULL,
            `message` text DEFAULT NULL,
            `status` enum('new','contacted','completed') NOT NULL DEFAULT 'new',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $stmt = $pdo->prepare("
            INSERT INTO `leads` (`name`, `phone`, `email`, `visa_route`, `message`, `status`)
            VALUES (:name, :phone, :email, :visa_route, :message, 'new')
        ");
        $stmt->execute([
            ':name'       => $name,
            ':phone'      => $phone,
            ':email'      => $email,
            ':visa_route' => $visaRoute,
            ':message'    => $message,
        ]);
        $insertedId = (int)$pdo->lastInsertId();
        $savedToDb = true;
    } catch (Exception $e) {
        $savedToDb = false;
    }
}

// Always maintain backup / file fallback so admin can view inquiries in all environments
$backupFile = __DIR__ . '/leads_backup.json';
$records = [];
if (file_exists($backupFile)) {
    $records = json_decode(@file_get_contents($backupFile), true) ?: [];
}
if (!$insertedId) {
    $insertedId = 'lead-' . time();
}
$newRecord = [
    'id'         => $insertedId,
    'name'       => $name,
    'phone'      => $phone,
    'email'      => $email,
    'visaRoute'  => $visaRoute,
    'visa_route' => $visaRoute,
    'message'    => $message,
    'status'     => 'new',
    'date'       => date('d M Y, H:i'),
    'created_at' => date('Y-m-d H:i:s')
];
array_unshift($records, $newRecord);
@file_put_contents($backupFile, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Email notification to Admin
$adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'info@ukvisapakistan.com';
$subject = "New UK Visa Inquiry: $name ($visaRoute)";
$body = "New consultation inquiry submitted on UK Visa Pakistan:\n\n"
      . "Applicant: $name\n"
      . "Phone / WhatsApp: $phone\n"
      . "Email: $email\n"
      . "Visa Route: $visaRoute\n"
      . "Date: " . date('d M Y, H:i') . "\n\n"
      . "Message:\n$message\n";

$headers = "From: noreply@" . ($_SERVER['SERVER_NAME'] ?? 'ukvisapakistan.com') . "\r\n"
         . "Reply-To: " . ($email !== 'N/A' ? $email : 'noreply@ukvisapakistan.com') . "\r\n"
         . "X-Mailer: PHP/" . phpversion();

@mail($adminEmail, $subject, $body, $headers);

echo json_encode([
    'success' => true,
    'message' => 'Consultation lead recorded successfully.',
    'id'      => $insertedId,
    'savedTo' => $savedToDb ? 'mysql' : 'file_backup'
]);
