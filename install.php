<?php
/**
 * UK Visa Pakistan - Web Database Installer
 * Run this in your browser once after uploading to shared hosting (e.g. yourdomain.com/install.php)
 */

$message = '';
$isSuccess = false;
$errorHelp = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost  = trim($_POST['db_host'] ?? 'localhost');
    $dbName  = trim($_POST['db_name'] ?? '');
    $dbUser  = trim($_POST['db_user'] ?? '');
    $dbPass  = trim($_POST['db_pass'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? 'info@ukvisapakistan.com');

    if (empty($dbName) || empty($dbUser)) {
        $message = 'Please provide both Database Name and Database User.';
    } else {
        try {
            // In shared hosting (cPanel), users must connect directly to their specific database
            $pdo = null;
            try {
                $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
            } catch (PDOException $eDirect) {
                // If direct connection fails because DB does not exist yet (local/VPS root user)
                try {
                    $pdoRoot = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                    $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                } catch (Exception $eRoot) {
                    throw $eDirect; // Re-throw the original direct connection exception
                }
            }

            // Create tables if not exists
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

            $pdo->exec("CREATE TABLE IF NOT EXISTS `announcements` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `message` text NOT NULL,
                `is_visible` tinyint(1) NOT NULL DEFAULT 1,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Seed default announcement if table is empty
            $checkAnn = $pdo->query("SELECT COUNT(*) AS cnt FROM `announcements`")->fetch();
            if (($checkAnn['cnt'] ?? 0) == 0) {
                $pdo->exec("INSERT INTO `announcements` (`message`, `is_visible`) VALUES ('Priority Biometrics available at Gerry\'s Islamabad, Lahore & Karachi centres.', 1)");
            }

            $pdo->exec("CREATE TABLE IF NOT EXISTS `news` (
                `id` varchar(100) NOT NULL,
                `title` varchar(255) NOT NULL,
                `category` varchar(50) NOT NULL,
                `catLabel` varchar(100) DEFAULT NULL,
                `priority` varchar(100) DEFAULT 'Normal',
                `date` varchar(100) NOT NULL,
                `readTime` varchar(50) DEFAULT '4 min read',
                `excerpt` text NOT NULL,
                `content` longtext NOT NULL,
                `sourceUrl` varchar(500) DEFAULT 'https://www.gov.uk/browse/visas-immigration',
                `image` varchar(500) DEFAULT NULL,
                `featured` tinyint(1) NOT NULL DEFAULT 0,
                `status` varchar(50) NOT NULL DEFAULT 'published',
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Seed initial news articles from json file if table is empty
            $checkNews = $pdo->query("SELECT COUNT(*) AS cnt FROM `news`")->fetch();
            $newsJsonFile = __DIR__ . '/api/news.json';
            if (($checkNews['cnt'] ?? 0) == 0 && file_exists($newsJsonFile)) {
                $seedItems = json_decode(@file_get_contents($newsJsonFile), true) ?: [];
                $insNews = $pdo->prepare("INSERT INTO `news` (`id`, `title`, `category`, `catLabel`, `priority`, `date`, `readTime`, `excerpt`, `content`, `sourceUrl`, `image`, `featured`, `status`)
                    VALUES (:id, :title, :category, :catLabel, :priority, :date, :readTime, :excerpt, :content, :sourceUrl, :image, :featured, :status)");
                foreach ($seedItems as $sItem) {
                    $insNews->execute([
                        ':id'        => $sItem['id'] ?? ('news-' . uniqid()),
                        ':title'     => $sItem['title'] ?? '',
                        ':category'  => $sItem['category'] ?? 'GENERAL',
                        ':catLabel'  => $sItem['catLabel'] ?? 'Visa Update',
                        ':priority'  => $sItem['priority'] ?? 'Normal',
                        ':date'      => $sItem['date'] ?? date('d F Y'),
                        ':readTime'  => $sItem['readTime'] ?? '4 min read',
                        ':excerpt'   => $sItem['excerpt'] ?? '',
                        ':content'   => $sItem['content'] ?? ($sItem['excerpt'] ?? ''),
                        ':sourceUrl' => $sItem['sourceUrl'] ?? 'https://www.gov.uk/browse/visas-immigration',
                        ':image'     => $sItem['image'] ?? 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=800&q=75',
                        ':featured'  => !empty($sItem['featured']) ? 1 : 0,
                        ':status'    => $sItem['status'] ?? 'published'
                    ]);
                }
            }

            // Create admin_users table
            $pdo->exec("CREATE TABLE IF NOT EXISTS `admin_users` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `username` varchar(100) NOT NULL UNIQUE,
                `password` varchar(255) NOT NULL,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $checkAdmin = $pdo->query("SELECT COUNT(*) AS cnt FROM `admin_users`")->fetch();
            if (($checkAdmin['cnt'] ?? 0) == 0) {
                $pdo->exec("INSERT INTO `admin_users` (`username`, `password`) VALUES ('admin', 'ukvisa2026')");
            }

            // Write api/config.php
            $configContent = "<?php\n";
            $configContent .= "/**\n * UK Visa Pakistan - Database Configuration\n * Generated automatically by install.php\n */\n\n";
            $configContent .= "define('DB_HOST', " . var_export($dbHost, true) . ");\n";
            $configContent .= "define('DB_NAME', " . var_export($dbName, true) . ");\n";
            $configContent .= "define('DB_USER', " . var_export($dbUser, true) . ");\n";
            $configContent .= "define('DB_PASS', " . var_export($dbPass, true) . ");\n";
            $configContent .= "define('DB_CHARSET', 'utf8mb4');\n";
            $configContent .= "define('ADMIN_EMAIL', " . var_export($adminEmail, true) . ");\n";

            @file_put_contents(__DIR__ . '/api/config.php', $configContent);

            $isSuccess = true;
            $message = 'MySQL Database connected and all tables (leads, announcements, news) installed successfully!';
        } catch (PDOException $e) {
            $rawMsg = $e->getMessage();
            $message = 'Database Connection Failed: ' . htmlspecialchars($rawMsg);

            if (strpos($rawMsg, '1045') !== false || strpos($rawMsg, 'Access denied') !== false) {
                $errorHelp = '<strong>Troubleshooting "Access Denied [1045]":</strong><br>'
                    . '1. In cPanel, domain name (e.g. <code>ukvisapakistan.com</code>) is <strong>NOT</strong> your database user.<br>'
                    . '2. Go to cPanel &rarr; <strong>MySQL Databases</strong>.<br>'
                    . '3. Look at your cPanel username prefix (e.g. <code>ukvisap_</code>).<br>'
                    . '4. Under <strong>Create New Database</strong>: create e.g. <code>ukvisap_db</code>.<br>'
                    . '5. Under <strong>MySQL Users - Add New User</strong>: create e.g. <code>ukvisap_admin</code> with a password.<br>'
                    . '6. <strong>CRITICAL STEP:</strong> Scroll down to <strong>Add User To Database</strong>, select the user and database, click <strong>Add</strong>, check <mark>ALL PRIVILEGES</mark>, and click <strong>Make Changes</strong>!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <link rel="icon" type="image/png" href="/images/logo.png">
    <title>Database Setup &middot; UK Visa Pakistan</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Cormorant+Garamond:wght@600;700&family=EB+Garamond:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #F7F2E7;
            color: #1E1A13;
            font-family: 'EB Garamond', Georgia, serif;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px 16px;
        }
        .card {
            background: #ffffff;
            border: 1px solid #EEE3CB;
            border-top: 4px solid #0B3D2E;
            max-width: 560px;
            width: 100%;
            padding: 36px 32px;
            box-shadow: 0 10px 30px rgba(11, 31, 58, 0.08);
            border-radius: 2px;
        }
        .logo-img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            display: block;
            margin: 0 auto 16px auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15));
        }
        h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 30px;
            color: #0B1F3A;
            text-align: center;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .eyebrow {
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #6B0E1A;
            text-align: center;
            font-weight: bold;
            margin-bottom: 4px;
            display: block;
        }
        .desc {
            font-size: 15px;
            color: #555;
            text-align: center;
            margin-bottom: 24px;
        }
        .alert {
            padding: 14px 16px;
            border-radius: 2px;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .alert-success {
            background: #E4ECE6;
            color: #0B3D2E;
            border: 1px solid #0B3D2E;
        }
        .alert-error {
            background: #FDF2F2;
            color: #9B1C1C;
            border: 1px solid #F8B4B4;
        }
        .help-box {
            background: #FFFBEB;
            border: 1px solid #FDE68A;
            color: #92400E;
            padding: 14px;
            border-radius: 2px;
            font-size: 13px;
            font-family: sans-serif;
            margin-top: 10px;
            line-height: 1.6;
        }
        .help-box code {
            background: #FEF3C7;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: bold;
            color: #78350F;
        }
        .cpanel-tip {
            background: #F0FDF4;
            border-left: 3px solid #0B3D2E;
            padding: 12px 14px;
            font-size: 13px;
            font-family: sans-serif;
            color: #14532D;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .field {
            margin-bottom: 16px;
        }
        label {
            display: block;
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-weight: bold;
            color: #0B1F3A;
            margin-bottom: 6px;
        }
        input[type="text"], input[type="password"], input[type="email"] {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 2px;
            font-family: sans-serif;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        input:focus {
            border-color: #AD8A34;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: #6B0E1A;
            color: #F1E6C8;
            border: 1px solid #AD8A34;
            font-family: 'Cinzel', serif;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            cursor: pointer;
            margin-top: 24px;
            transition: background 0.2s;
            text-align: center;
            text-decoration: none;
        }
        .btn:hover {
            background: #8C1A26;
            color: #ffffff;
        }
        .btn-green {
            background: #0B3D2E;
        }
        .btn-green:hover {
            background: #1D5C43;
        }
        .footer-links {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 14px;
        }
        .footer-links a {
            color: #0B3D2E;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="card">
        <img src="/images/logo.png" alt="UK Visa Pakistan Logo" class="logo-img">
        <span class="eyebrow">Shared Hosting Installer</span>
        <h1>Database Setup</h1>
        <p class="desc">Connect MySQL database to store consultation inquiries and live website updates.</p>

        <?php if ($message): ?>
            <div class="alert <?= $isSuccess ? 'alert-success' : 'alert-error' ?>">
                <?= $message ?>
                <?php if ($errorHelp): ?>
                    <div class="help-box">
                        <?= $errorHelp ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($isSuccess): ?>
            <p style="margin-bottom: 20px; font-size: 16px; text-align: center; color: #0B3D2E;">
                Everything is configured and ready. You can now use your live website and staff admin portal.
            </p>
            <a href="/" class="btn btn-green">Go to Website Homepage &rarr;</a>
            <a href="/admin/" class="btn" style="margin-top: 10px;">Open Staff Admin Portal &rarr;</a>
            <a href="/news-updates/" class="btn btn-green" style="margin-top: 10px;">View Live News Page &rarr;</a>
        <?php else: ?>
            <div class="cpanel-tip">
                <strong>cPanel Notice:</strong> In cPanel, your database name and database user will have a prefix, e.g. <code>username_ukvisadb</code> and <code>username_admin</code>. Make sure you also use <em>"Add User to Database"</em> and grant <strong>ALL PRIVILEGES</strong>.
            </div>

            <form method="POST">
                <div class="field">
                    <label>Database Host</label>
                    <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
                    <small style="font-size: 11px; color: #777;">Usually <strong>localhost</strong> on cPanel, Namecheap, Hostinger.</small>
                </div>

                <div class="field">
                    <label>Database Name *</label>
                    <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" placeholder="e.g. yourprefix_ukvisa" required>
                    <small style="font-size: 11px; color: #777;">Full name from cPanel, including prefix.</small>
                </div>

                <div class="field">
                    <label>Database User *</label>
                    <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" placeholder="e.g. yourprefix_dbuser" required>
                    <small style="font-size: 11px; color: #777;">Do not use your domain name. Use the MySQL user created in cPanel.</small>
                </div>

                <div class="field">
                    <label>Database Password</label>
                    <input type="password" name="db_pass" placeholder="••••••••••••">
                </div>

                <div class="field">
                    <label>Admin Notification Email</label>
                    <input type="email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? 'info@ukvisapakistan.com') ?>" required>
                </div>

                <button type="submit" class="btn">Connect &amp; Create Tables</button>
            </form>
        <?php endif; ?>

        <div class="footer-links">
            <a href="/">&larr; Return to Website</a>
        </div>
    </div>
</body>
</html>
