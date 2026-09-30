<?php
/**
 * UK Visa Pakistan - Leads Management Endpoint for Admin Portal
 * Supports GET (list), PATCH/POST (update status), and DELETE.
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/db.php';
$pdo = getDBConnection();

$method = $_SERVER['REQUEST_METHOD'];

// Handle GET: List all leads
if ($method === 'GET') {
    if ($pdo !== null) {
        try {
            $stmt = $pdo->query("SELECT id, name, phone, email, visa_route AS visaRoute, message, DATE_FORMAT(created_at, '%d %b %Y, %H:%i') AS date, status FROM `leads` ORDER BY id DESC");
            $leads = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $leads, 'source' => 'mysql']);
            exit;
        } catch (Exception $e) {
            // fallback below
        }
    }

    // Fallback to leads_backup.json
    $backupFile = __DIR__ . '/leads_backup.json';
    $leads = [];
    if (file_exists($backupFile)) {
        $raw = json_decode(@file_get_contents($backupFile), true) ?: [];
        foreach ($raw as $item) {
            $leads[] = [
                'id'        => $item['id'] ?? ('lead-' . uniqid()),
                'name'      => $item['name'] ?? '',
                'phone'     => $item['phone'] ?? '',
                'email'     => $item['email'] ?? '',
                'visaRoute' => $item['visaRoute'] ?? ($item['visa_route'] ?? 'General Visa'),
                'message'   => $item['message'] ?? '',
                'date'      => $item['date'] ?? ($item['created_at'] ?? date('d M Y, H:i')),
                'status'    => $item['status'] ?? 'new'
            ];
        }
    }
    echo json_encode(['success' => true, 'data' => $leads, 'source' => 'file_fallback']);
    exit;
}

// Handle PATCH / POST: Update status
if ($method === 'PATCH' || ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'update')) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;
    $id = $data['id'] ?? null;
    $status = $data['status'] ?? 'new';

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Lead ID is required.']);
        exit;
    }

    if ($pdo !== null) {
        try {
            $stmt = $pdo->prepare("UPDATE `leads` SET `status` = :status WHERE `id` = :id");
            $stmt->execute([':status' => $status, ':id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Status updated in MySQL.']);
            exit;
        } catch (Exception $e) {
            // fallback
        }
    }

    // Fallback file update
    $backupFile = __DIR__ . '/leads_backup.json';
    if (file_exists($backupFile)) {
        $leads = json_decode(@file_get_contents($backupFile), true) ?: [];
        foreach ($leads as &$lead) {
            if ($lead['id'] == $id) {
                $lead['status'] = $status;
            }
        }
        @file_put_contents($backupFile, json_encode($leads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    echo json_encode(['success' => true, 'message' => 'Status updated in backup file.']);
    exit;
}

// Handle DELETE: Remove lead
if ($method === 'DELETE' || ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete')) {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $id = $data['id'] ?? null;
    }

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Lead ID is required.']);
        exit;
    }

    if ($pdo !== null) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `leads` WHERE `id` = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Lead deleted from MySQL.']);
            exit;
        } catch (Exception $e) {
            // fallback
        }
    }

    // Fallback file delete
    $backupFile = __DIR__ . '/leads_backup.json';
    if (file_exists($backupFile)) {
        $leads = json_decode(@file_get_contents($backupFile), true) ?: [];
        $leads = array_values(array_filter($leads, function($l) use ($id) {
            return $l['id'] != $id;
        }));
        @file_put_contents($backupFile, json_encode($leads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    echo json_encode(['success' => true, 'message' => 'Lead deleted from backup file.']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Unsupported method.']);
