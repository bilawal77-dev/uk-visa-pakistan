# UK Visa Pakistan - Web Portal & Advisory Platform

Professional immigration guidance and eligibility assessment platform for UK visa applicants from Pakistan.

![UK Visa Pakistan Logo](images/logo.png)

## Overview
UK Visa Pakistan is a full-featured web platform providing comprehensive legal routes, document audits, fee calculators, live UKVI policy intelligence, and staff administrative controls.

## Key Features
- **Official Royal Branding:** Bespoke coat of arms crest featuring UK and Pakistan iconography, optimized responsive layout, and typography (Cinzel & Cormorant Garamond).
- **Dedicated News & Policy Updates (`/news-updates/`):** Real-time Home Office announcements, category filtering (eVisa, Student CAS, Skilled Worker, Gerry's VFS, Family/Spouse), and dynamic reader modal.
- **Staff Admin Portal (`/admin/`):**
  - **Consultation Leads Management:** View, track, update status, and export client inquiries.
  - **Policy News & Articles:** Add, edit, preview, and delete live visa news directly from the admin panel.
  - **Live Announcement Ticker:** Update global emergency ticker in real-time.
- **RESTful PHP & MySQL Backend:**
  - `/api/news.php`: Dynamic articles and policy updates endpoint with MySQL and JSON fallback.
  - `/api/leads.php`: Secure intake of client consultation forms.
  - `/api/announcement.php`: Real-time notification ticker API.
  - `/install.php`: Web-based database installer for shared hosting (cPanel / Hostinger).
- **SEO & High Performance:** Fast static pages with interactive React hydration, meta open-graph tags, and WhatsApp direct consultation routing.

## Installation & Deployment

### 1. Upload to Web Server
Upload all files to your server's `public_html` directory (Apache, cPanel, or Hostinger).

### 2. Configure Database
Edit `api/config.php` with your MySQL database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
```
Or import `database.sql` directly via phpMyAdmin.

### 3. Admin Login
- URL: `/admin/`
- Default Username: `admin`
- Default Password: `ukvisa2026` (or `admin`)

---
&copy; 2026 UK Visa Pakistan. All rights reserved.
