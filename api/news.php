<?php
/**
 * UK Visa Pakistan - News & Policy Updates API
 * Supports GET (list all news), POST (create / update / delete), and DELETE.
 * Works seamlessly with both `articles` table or `news` table in MySQL,
 * and includes automatic JSON file fallback (`news.json`).
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/db.php';
$pdo = getDBConnection();
$jsonFile = __DIR__ . '/news.json';

// Helper to read JSON fallback
function loadNewsFromJson($filePath) {
    if (file_exists($filePath)) {
        $content = @file_get_contents($filePath);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return [];
}

// Helper to write JSON fallback
function saveNewsToJson($filePath, $data) {
    @file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

// Detect which table exists in MySQL (articles or news)
$activeTable = 'news';
if ($pdo !== null) {
    try {
        $chkArticles = $pdo->query("SHOW TABLES LIKE 'articles'")->fetch();
        if ($chkArticles) {
            $activeTable = 'articles';
        } else {
            $chkNews = $pdo->query("SHOW TABLES LIKE 'news'")->fetch();
            if ($chkNews) {
                $activeTable = 'news';
            }
        }
    } catch (Exception $e) {}
}

// Helper to normalize article record
function normalizeArticleRow($row) {
    return [
        'id'        => (string)($row['id'] ?? uniqid()),
        'title'     => $row['title'] ?? '',
        'category'  => strtoupper($row['category'] ?? 'GENERAL'),
        'catLabel'  => $row['catLabel'] ?? $row['cat_label'] ?? $row['category'] ?? 'Visa Update',
        'priority'  => $row['priority'] ?? 'Standard',
        'date'      => $row['date'] ?? date('d F Y'),
        'readTime'  => $row['readTime'] ?? $row['read_time'] ?? '4 min read',
        'excerpt'   => $row['excerpt'] ?? '',
        'content'   => $row['content'] ?? $row['body'] ?? $row['excerpt'] ?? '',
        'sourceUrl' => $row['sourceUrl'] ?? $row['source_url'] ?? 'https://www.gov.uk/browse/visas-immigration',
        'image'     => $row['image'] ?? 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=800&q=75',
        'featured'  => !empty($row['featured']),
        'status'    => $row['status'] ?? 'published'
    ];
}

$method = $_SERVER['REQUEST_METHOD'];

// Handle GET: List all news / articles
if ($method === 'GET') {
    if ($pdo !== null) {
        try {
            $stmt = $pdo->query("SELECT * FROM `{$activeTable}` ORDER BY id DESC");
            $rows = $stmt->fetchAll();
            if ($rows && count($rows) > 0) {
                $result = [];
                foreach ($rows as $r) {
                    $result[] = normalizeArticleRow($r);
                }
                echo json_encode(['success' => true, 'data' => $result, 'source' => 'mysql', 'table' => $activeTable]);
                exit;
            }
        } catch (Exception $e) {
            // fallback to JSON
        }
    }

    $fallback = loadNewsFromJson($jsonFile);
    echo json_encode(['success' => true, 'data' => $fallback, 'source' => 'json_fallback']);
    exit;
}

// Handle DELETE: remove news item
if ($method === 'DELETE' || ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete')) {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?: $_POST;
    $id = $_GET['id'] ?? ($payload['id'] ?? null);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Article ID is required for deletion.']);
        exit;
    }

    if ($pdo !== null) {
        try {
            $del = $pdo->prepare("DELETE FROM `{$activeTable}` WHERE `id` = :id");
            $del->execute([':id' => $id]);
        } catch (Exception $e) {}
    }

    // Always sync with json fallback
    $all = loadNewsFromJson($jsonFile);
    $all = array_values(array_filter($all, function($item) use ($id) {
        return (string)$item['id'] !== (string)$id;
    }));
    saveNewsToJson($jsonFile, $all);

    echo json_encode([
        'success' => true,
        'message' => 'Article deleted successfully.',
        'id'      => $id
    ]);
    exit;
}

// Handle POST: Create or Update
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;

    $action = $_GET['action'] ?? ($data['action'] ?? 'save');
    $id = trim((string)($data['id'] ?? ''));

    $title     = trim($data['title'] ?? '');
    $category  = strtoupper(trim($data['category'] ?? 'GENERAL'));
    $catLabel  = trim($data['catLabel'] ?? '');
    if (empty($catLabel)) {
        $labels = [
            'EVISA'   => 'eVisa & Digital',
            'STUDENT' => 'Student & CAS',
            'WORK'    => 'Skilled Worker',
            'VFS'     => "Gerry's VFS Notices",
            'FAMILY'  => 'Family & Spouse',
            'POLICY'  => 'Policy Update',
            'GENERAL' => 'Visa Update'
        ];
        $catLabel = $labels[$category] ?? ucfirst(strtolower($category));
    }
    $priority  = trim($data['priority'] ?? 'Standard');
    $date      = trim($data['date'] ?? date('d F Y'));
    $readTime  = trim($data['readTime'] ?? '4 min read');
    $excerpt   = trim($data['excerpt'] ?? '');
    $content   = trim($data['content'] ?? ($excerpt ?: 'No content provided.'));
    $sourceUrl = trim($data['sourceUrl'] ?? 'https://www.gov.uk/browse/visas-immigration');
    $image     = trim($data['image'] ?? '');
    if (empty($image)) {
        $image = 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=800&q=75';
    }
    $featured  = !empty($data['featured']) ? 1 : 0;
    $status    = trim($data['status'] ?? 'published');

    if (empty($title)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Article title is required.']);
        exit;
    }

    if (empty($excerpt)) {
        $excerpt = mb_substr(strip_tags($content), 0, 180) . '...';
    }

    $isUpdate = ($action === 'update' || !empty($id));
    if (!$isUpdate || empty($id)) {
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
        $id = 'news-' . trim($slug, '-') . '-' . substr(uniqid(), -4);
    }

    $record = [
        'id'        => $id,
        'title'     => $title,
        'category'  => $category,
        'catLabel'  => $catLabel,
        'priority'  => $priority,
        'date'      => $date,
        'readTime'  => $readTime,
        'excerpt'   => $excerpt,
        'content'   => $content,
        'sourceUrl' => $sourceUrl,
        'image'     => $image,
        'featured'  => (bool)$featured,
        'status'    => $status
    ];

    // Try save to MySQL
    if ($pdo !== null) {
        try {
            // First check columns of activeTable to avoid unknown column errors
            $stmtCols = $pdo->query("SHOW COLUMNS FROM `{$activeTable}`");
            $existingCols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);

            $insertData = [
                'id'       => $id,
                'title'    => $title,
                'category' => $category,
                'excerpt'  => $excerpt,
            ];

            if (in_array('catLabel', $existingCols))  $insertData['catLabel'] = $catLabel;
            if (in_array('priority', $existingCols))  $insertData['priority'] = $priority;
            if (in_array('date', $existingCols))      $insertData['date'] = $date;
            if (in_array('readTime', $existingCols))  $insertData['readTime'] = $readTime;
            if (in_array('read_time', $existingCols)) $insertData['read_time'] = $readTime;
            if (in_array('content', $existingCols))   $insertData['content'] = $content;
            if (in_array('body', $existingCols))      $insertData['body'] = $content;
            if (in_array('sourceUrl', $existingCols)) $insertData['sourceUrl'] = $sourceUrl;
            if (in_array('source_url', $existingCols))$insertData['source_url'] = $sourceUrl;
            if (in_array('image', $existingCols))     $insertData['image'] = $image;
            if (in_array('featured', $existingCols))  $insertData['featured'] = $featured;
            if (in_array('status', $existingCols))    $insertData['status'] = $status;
            if (in_array('slug', $existingCols))      $insertData['slug'] = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));

            $fields = array_keys($insertData);
            $placeholders = array_map(function($f) { return ':' . $f; }, $fields);
            $updates = array_map(function($f) { return "`$f` = VALUES(`$f`)"; }, $fields);

            $sql = "INSERT INTO `{$activeTable}` (" . implode(',', array_map(function($f){ return "`$f`"; }, $fields)) . ")
                    VALUES (" . implode(',', $placeholders) . ")
                    ON DUPLICATE KEY UPDATE " . implode(',', $updates);

            $stmt = $pdo->prepare($sql);
            $execParams = [];
            foreach ($insertData as $k => $v) {
                $execParams[':' . $k] = $v;
            }
            $stmt->execute($execParams);
        } catch (Exception $e) {}
    }

    // Always update JSON backup file
    $all = loadNewsFromJson($jsonFile);
    $found = false;
    foreach ($all as &$item) {
        if ((string)$item['id'] === (string)$id) {
            $item = $record;
            $found = true;
            break;
        }
    }
    if (!$found) {
        array_unshift($all, $record);
    }
    saveNewsToJson($jsonFile, $all);

    echo json_encode([
        'success' => true,
        'message' => 'Article saved successfully.',
        'data'    => $record
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
