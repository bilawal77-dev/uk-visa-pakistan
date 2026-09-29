<?php
/**
 * UK Visa Pakistan - Announcement Ticker Endpoint
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/db.php';
$pdo = getDBConnection();

$defaultAnnouncement = [
    'message'   => "Priority Biometrics available at Gerry's Islamabad, Lahore & Karachi centres.",
    'isVisible' => true
];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($pdo !== null) {
        try {
            $stmt = $pdo->query("SELECT message, is_visible AS isVisible FROM `announcements` ORDER BY id DESC LIMIT 1");
            $row = $stmt->fetch();
            if ($row) {
                $row['isVisible'] = (bool)$row['isVisible'];
                echo json_encode(['success' => true, 'data' => $row]);
                exit;
            }
        } catch (Exception $e) {
            // fallback below
        }
    }
    echo json_encode(['success' => true, 'data' => $defaultAnnouncement]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;
    $message = trim($data['message'] ?? $defaultAnnouncement['message']);
    $isVisible = isset($data['isVisible']) ? (int)(bool)$data['isVisible'] : 1;

    if ($pdo !== null) {
        try {
            $stmt = $pdo->prepare("INSERT INTO `announcements` (`message`, `is_visible`) VALUES (:msg, :vis)");
            $stmt->execute([':msg' => $message, ':vis' => $isVisible]);
            echo json_encode(['success' => true, 'message' => 'Announcement updated in MySQL.']);
            exit;
        } catch (Exception $e) {
            // fallback below
        }
    }
    echo json_encode(['success' => true, 'message' => 'Announcement received.']);
    exit;
}
