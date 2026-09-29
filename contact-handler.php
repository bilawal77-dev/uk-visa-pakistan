<?php
/**
 * UK Visa Pakistan - Shared Hosting Contact & Consultation Form Handler
 * Works on any standard cPanel / PHP shared hosting.
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
    echo json_encode(['success' => false, 'error' => 'Only POST requests are permitted.']);
    exit;
}

// Read raw JSON input or Form POST
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    $data = $_POST;
}

// Sanitize inputs
$name    = isset($data['name']) ? htmlspecialchars(trim($data['name'])) : 'Not Provided';
$phone   = isset($data['phone']) ? htmlspecialchars(trim($data['phone'])) : 'Not Provided';
$email   = isset($data['email']) ? filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL) : 'Not Provided';
$route   = isset($data['visaRoute']) ? htmlspecialchars(trim($data['visaRoute'])) : (isset($data['route']) ? htmlspecialchars(trim($data['route'])) : 'General UK Visa');
$message = isset($data['message']) ? htmlspecialchars(trim($data['message'])) : 'No additional message';
$date    = date('d M Y, H:i');

// Change this to your business email to receive notifications
$toEmail = "info@ukvisapakistan.com"; 
$subject = "New UK Visa Inquiry: " . $name . " (" . $route . ")";

$emailBody = "--- NEW UK VISA CONSULTATION INQUIRY ---\n\n";
$emailBody .= "Applicant Name: " . $name . "\n";
$emailBody .= "Phone / WhatsApp: " . $phone . "\n";
$emailBody .= "Email: " . $email . "\n";
$emailBody .= "Visa Route: " . $route . "\n";
$emailBody .= "Date: " . $date . "\n\n";
$emailBody .= "Case Details / Message:\n" . $message . "\n";
$emailBody .= "\n----------------------------------------\n";
$emailBody .= "Sent from UK Visa Pakistan Shared Hosting Web Portal\n";

$headers = "From: noreply@" . ($_SERVER['SERVER_NAME'] ?? 'ukvisapakistan.com') . "\r\n";
$headers .= "Reply-To: " . ($email !== 'Not Provided' ? $email : 'noreply@ukvisapakistan.com') . "\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Attempt to send email via standard PHP mail()
$mailSent = false;
if (filter_var($email, FILTER_VALIDATE_EMAIL) && function_exists('mail')) {
    $mailSent = @mail($toEmail, $subject, $emailBody, $headers);
}

// Optionally log to local leads.json
$logFile = __DIR__ . '/leads_log.json';
$leads = [];
if (file_exists($logFile)) {
    $existing = @file_get_contents($logFile);
    if ($existing) {
        $leads = json_decode($existing, true) ?: [];
    }
}

$leads[] = [
    'id' => 'lead-' . time(),
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'visaRoute' => $route,
    'message' => $message,
    'date' => $date,
    'status' => 'new'
];

@file_put_contents($logFile, json_encode($leads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode([
    'success' => true,
    'message' => 'Consultation inquiry received successfully.',
    'mailSent' => $mailSent
]);
