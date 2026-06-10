<?php
/**
 * APPLICATION CONFIGURATION
 * অ্যাপ্লিকেশন কনফিগারেশন
 */

define('APP_NAME', 'ERP Starter Template');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost');
define('APP_DEBUG', true);

// Session configuration
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('SESSION_NAME', 'erp_session');

// Permission cache timeout (in seconds)
define('PERMISSION_CACHE_TIMEOUT', 3600); // 1 hour

// Pagination
define('ITEMS_PER_PAGE', 20);

// File upload
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif']);

// Log path
define('LOG_PATH', dirname(__DIR__) . '/logs/');

// Time zone
date_default_timezone_set('Asia/Dhaka');
?>
