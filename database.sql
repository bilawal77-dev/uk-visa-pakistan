-- -------------------------------------------------------------
-- UK Visa Pakistan - Database Schema & Initial Data
-- Database: u354666206_ukvisa
-- -------------------------------------------------------------

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table structure for table `leads`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `visa_route` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','contacted','completed') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `announcements`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default announcement ticker if not exists
INSERT INTO `announcements` (`id`, `message`, `is_visible`) VALUES
(1, 'Priority Biometrics available at Gerry\'s Islamabad, Lahore & Karachi centres.', 1)
ON DUPLICATE KEY UPDATE `message` = VALUES(`message`);

-- --------------------------------------------------------
-- Table structure for table `articles` / `news`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `articles` (
  `id` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert latest initial UK visa news updates for Pakistani applicants
INSERT INTO `articles` (`id`, `title`, `slug`, `category`, `catLabel`, `priority`, `date`, `readTime`, `excerpt`, `content`, `sourceUrl`, `image`, `featured`, `status`) VALUES
('update-evisa-transition-2026', 'Transition to Digital UK eVisa: Phasing out BRPs for Pakistani Applicants', 'transition-digital-uk-evisa-pakistan', 'EVISA', 'eVisa & Digital', 'Urgent Transition', '29 September 2026', '5 min read', 'UKVI is actively replacing physical Biometric Residence Permits (BRPs) and vignette sticker stamps with digital eVisas. Pakistani passport holders with valid UK leave must link their passports to a UKVI account before international travel.', 'The UK Visas and Immigration (UKVI) department is completing its transition to a fully digital immigration system. Physical immigration documents, including Biometric Residence Permits (BRPs), Biometric Residence Cards (BRCs), and passport vignette endorsement stickers, are being phased out in favour of digital eVisas.\n\nKey Guidelines for Pakistani Applicants & Residents:\n1. Creating your UKVI Account: Individuals residing in the UK or holding valid indefinite or temporary leave must create a UKVI account via gov.uk/evisa to access their digital status.\n2. Linking Pakistani Passport: Ensure your current Pakistani passport number and details are strictly identical in your UKVI account.\n3. Travel Verification: Airlines boarding passengers in Islamabad, Karachi, and Lahore will digitally verify visa status before boarding.\n4. Share Codes: Landlords and employers in the UK will now only accept online share codes generated from your UKVI portal.', 'https://www.gov.uk/guidance/online-immigration-status-evisa', 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?auto=format&fit=crop&w=800&q=75', 1, 'published'),

('update-student-cas-funds-2026', 'Student Route (CAS) Financial Maintenance & Pakistani Bank Account Rules', 'student-route-cas-funds-pakistan', 'STUDENT', 'Student & CAS', 'Financial Rule', '18 September 2026', '4 min read', 'Home Office enforces updated maintenance fund levels for Pakistani students. Bank statements must strictly demonstrate funds held uninterrupted for 28 consecutive days prior to application submission.', 'Applying for a UK Student visa from Pakistan requires strict financial compliance under Appendix Student of the UK Immigration Rules.\n\nCritical Checklist for Autumn/Winter Intake:\n1. Maintenance Amounts: Students intending to study in Inner London must demonstrate £1,483 per month (up to 9 months, £13,347 maximum). For institutions outside London, the requirement is £1,136 per month.\n2. The 28-Day Holding Rule: Funds must be held in an acceptable financial institution in Pakistan for a minimum continuous 28-day period.\n3. Acceptable SBP-Regulated Banks: Accounts must be with banks regulated by the State Bank of Pakistan (SBP).\n4. Credibility Interviews: Expect potential UKVI credibility interviews regarding course choice and career trajectory.', 'https://www.gov.uk/student-visa', 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=800&q=75', 0, 'published'),

('update-vfs-priority-slots-2026', 'Gerry\'s VFS Visa Application Centres: Priority & Prime-Time Appointments in Pakistan', 'gerrys-vfs-priority-appointment-slots-pakistan', 'VFS', 'Gerry\'s VFS Notices', 'VFS Centre Alert', '10 September 2026', '3 min read', 'Gerry\'s VFS centres in Islamabad, Lahore, Karachi, and Mirpur announce revised appointment allocation schedules, VIP Premium Lounge enhancements, and biometrics collection procedures.', 'Visa applicants across Pakistan booking biometrics through the Gerry\'s Visa Application Centres (VAC) in Islamabad, Lahore, Karachi, and Mirpur should take note of updated operational protocols.\n\nImportant Centre Information:\n1. Prime Time & Priority Slots: Priority Visa service and Super Priority Visa appointment slots are released every weekday morning at 08:30 AM PKT.\n2. Document Scanning & Submission: Applicants are strongly advised to upload all supporting documents electronically at least 24 hours prior to their appointment.\n3. Original Passports & CNIC: Ensure your original valid Pakistani passport, previous expired passports, and original CNIC/Smart Card are brought to the centre.', 'https://visa.vfsglobal.com/pak/en/gbr/', 'https://images.unsplash.com/photo-1541872703-74c5e44368f9?auto=format&fit=crop&w=800&q=75', 0, 'published'),

('update-skilled-worker-salary-2026', 'Skilled Worker Route: Minimum Salary Thresholds & Certificate of Sponsorship (CoS)', 'skilled-worker-salary-thresholds-pakistan', 'WORK', 'Skilled Worker', 'Policy Update', '28 August 2026', '6 min read', 'Detailed overview of the general £38,700 salary threshold for new Skilled Worker applications, the Immigration Salary List (ISL), and sponsor licence verification for Pakistani professionals.', 'Pakistani professionals pursuing career opportunities in the UK under the Skilled Worker visa route must satisfy stringent Home Office requirements.\n\nKey Requirements for Pakistani Applicants:\n1. Defined Certificate of Sponsorship (DCoS): Overseas applicants outside the UK require an approved Defined CoS from a licensed A-rated sponsor.\n2. General Salary Floor: The baseline general salary threshold is £38,700 per annum or the going rate for the SOC code.\n3. English Language: B1 level CEFR English proficiency through an approved SELT centre (IELTS for UKVI, PTE Academic UKVI).', 'https://www.gov.uk/skilled-worker-visa', 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=800&q=75', 0, 'published'),

('update-spouse-settlement-rules-2026', 'UK Spouse & Partner Settlement: Income Thresholds & Appendix FM Guidance', 'spouse-settlement-rules-income-threshold-pakistan', 'FAMILY', 'Family & Spouse', 'Settlement Advisory', '15 August 2026', '5 min read', 'Navigating the minimum income requirement, genuine relationship evidence (Nikahnama and NADRA registration), and accommodation checks for Pakistani spouses joining partners in the UK.', 'Spousal and partner settlement applications under Appendix FM represent one of the most thoroughly audited visa categories for Pakistani applicants.\n\nCrucial Evidence Requirements:\n1. Minimum Income Requirement: Sponsoring partner in the UK must meet the required financial threshold.\n2. Genuine & Subsisting Relationship: Submit NADRA Marriage Certificate, Urdu Nikahnama, wedding photographs, joint travel, and communication records.\n3. English Language: CEFR A1 Speaking and Listening test from an approved centre in Pakistan.', 'https://www.gov.uk/uk-family-visa/partner-spouse', 'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?auto=format&fit=crop&w=800&q=75', 0, 'published')

ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `excerpt` = VALUES(`excerpt`), `content` = VALUES(`content`);

-- --------------------------------------------------------
-- Table structure for table `admin_users`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user credentials
INSERT INTO `admin_users` (`id`, `username`, `password`) VALUES
(1, 'admin', 'ukvisa2026')
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);

COMMIT;
