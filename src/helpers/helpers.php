<?php
/**
 * HELPER FUNCTIONS
 * সহায়ক ফাংশন - সাধারণ ব্যবহারের জন্য
 */

// ============================================
// PERMISSION HELPERS
// ============================================

/**
 * চেক করুন ব্যবহারকারীর অনুমতি আছে কিনা
 */
function hasPermission($permissionCode, $userId = null) {
    if ($userId === null) {
        $userId = getCurrentUserId();
    }
    
    if (!$userId) {
        return false;
    }
    
    $permEngine = new PermissionEngine($userId);
    return $permEngine->hasPermission($permissionCode);
}

/**
 * চেক করুন ব্যবহারকারীর যেকোনো একটি অনুমতি আছে কিনা
 */
function hasAnyPermission(array $permissionCodes, $userId = null) {
    if ($userId === null) {
        $userId = getCurrentUserId();
    }
    
    if (!$userId) {
        return false;
    }
    
    $permEngine = new PermissionEngine($userId);
    return $permEngine->hasAnyPermission($permissionCodes);
}

/**
 * চেক করুন ব্যবহারকারীর সমস্ত অনুমতি আছে কিনা
 */
function hasAllPermissions(array $permissionCodes, $userId = null) {
    if ($userId === null) {
        $userId = getCurrentUserId();
    }
    
    if (!$userId) {
        return false;
    }
    
    $permEngine = new PermissionEngine($userId);
    return $permEngine->hasAllPermissions($permissionCodes);
}

/**
 * শুধুমাত্র অনুমোদিত ব্যবহারকারীদের জন্য কন্টেন্ট দেখান
 */
function restrictTo($permissionCode) {
    if (!hasPermission($permissionCode)) {
        echo '<div class="alert alert-danger">আপনার এই ফিচার অ্যাক্সেস করার অনুমতি নেই।</div>';
        return false;
    }
    return true;
}

// ============================================
// USER HELPERS
// ============================================

/**
 * বর্তমান ব্যবহারকারীর ID পান
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * বর্তমান ব্যবহারকারীর অবজেক্ট পান
 */
function getCurrentUser() {
    $userId = getCurrentUserId();
    if (!$userId) {
        return null;
    }
    
    $db = Database::getInstance();
    return $db->fetchOne("SELECT * FROM `users` WHERE `id` = {$userId}");
}

/**
 * ব্যবহারকারী লগইন আছে কিনা চেক করুন
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * ব্যবহারকারী লগআউট করুন
 */
function logout() {
    session_destroy();
    header('Location: /login');
    exit;
}

// ============================================
// MENU HELPERS
// ============================================

/**
 * সাইডবার মেনু রেন্ডার করুন
 */
function getSidebarMenu() {
    $userId = getCurrentUserId();
    if (!$userId) {
        return '';
    }
    
    $menuManager = new MenuManager();
    $menus = $menuManager->getSidebarMenus($userId);
    $currentRoute = $_GET['page'] ?? 'dashboard';
    
    return $menuManager->renderSidebarHTML($menus, '/' . $currentRoute);
}

/**
 * একটি মেনু রুট পান (কোড দ্বারা)
 */
function getMenuRoute($menuCode) {
    $menuManager = new MenuManager();
    return $menuManager->getRouteByMenuCode($menuCode);
}

// ============================================
// LOGGING HELPERS
// ============================================

/**
 * অ্যাকটিভিটি লগ করুন
 */
function logActivity($action, $module, $description = '', $oldValue = '', $newValue = '') {
    $userId = getCurrentUserId();
    if (!$userId) {
        return false;
    }
    
    $logger = new ActivityLogger();
    return $logger->log($userId, $action, $module, $description, $oldValue, $newValue, 'success');
}

/**
 * ব্যর্থ অ্যাকশন লগ করুন
 */
function logFailedAction($action, $module, $description = '') {
    $userId = getCurrentUserId();
    if (!$userId) {
        return false;
    }
    
    $logger = new ActivityLogger();
    return $logger->logFailedAction($userId, $action, $module, $description);
}

// ============================================
// VALIDATION HELPERS
// ============================================

/**
 * ইমেইল যাচাই করুন
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * ফোন নম্বর যাচাই করুন
 */
function isValidPhone($phone) {
    return preg_match('/^[\d\+\-\s\(\)]{7,}$/', $phone) ? true : false;
}

/**
 * স্ট্রিং সুরক্ষিত কিনা চেক করুন (XSS থেকে)
 */
function sanitizeString($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * SQL ইনজেকশন থেকে রক্ষা করুন
 */
function escapeSQLString($string) {
    $db = Database::getInstance();
    return $db->escape($string);
}

// ============================================
// DATE/TIME HELPERS
// ============================================

/**
 * বর্তমান তারিখ পান (ফরম্যাট করা)
 */
function getCurrentDate($format = 'Y-m-d H:i:s') {
    return date($format);
}

/**
 * তারিখ ফরম্যাট করুন (বাংলায়)
 */
function formatDateBangla($date) {
    $englishMonths = ['January', 'February', 'March', 'April', 'May', 'June', 
                      'July', 'August', 'September', 'October', 'November', 'December'];
    $bengaliMonths = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 
                      'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];
    
    $timestamp = strtotime($date);
    $formatted = date('j F Y H:i', $timestamp);
    
    return str_replace($englishMonths, $bengaliMonths, $formatted);
}

/**
 * সময় পার্থক্য দেখান (যেমন: '2 ঘন্টা আগে')
 */
function timeAgo($date) {
    $timestamp = strtotime($date);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'এখনই';
    if ($diff < 3600) return round($diff / 60) . ' মিনিট আগে';
    if ($diff < 86400) return round($diff / 3600) . ' ঘন্টা আগে';
    if ($diff < 604800) return round($diff / 86400) . ' দিন আগে';
    
    return date('d M Y', $timestamp);
}

// ============================================
// RESPONSE HELPERS
// ============================================

/**
 * সফলতার প্রতিক্রিয়া পাঠান
 */
function respondSuccess($message, $data = null) {
    return json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * ত্রুটির প্রতিক্রিয়া পাঠান
 */
function respondError($message, $errorCode = 400) {
    return json_encode([
        'success' => false,
        'message' => $message,
        'errorCode' => $errorCode
    ]);
}

/**
 * JSON প্রতিক্রিয়া পাঠান
 */
function respondJSON($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// ============================================
// URL HELPERS
// ============================================

/**
 * বেস ইউআরএল পান
 */
function baseURL() {
    return APP_URL;
}

/**
 * সম্পূর্ণ ইউআরএল তৈরি করুন
 */
function buildURL($path) {
    return APP_URL . $path;
}

/**
 * রিডিরেক্ট করুন
 */
function redirect($path) {
    header('Location: ' . buildURL($path));
    exit;
}

// ============================================
// STRING HELPERS
// ============================================

/**
 * স্ট্রিং কেটে সংক্ষিপ্ত করুন
 */
function truncateString($string, $length = 50, $suffix = '...') {
    if (strlen($string) > $length) {
        return substr($string, 0, $length) . $suffix;
    }
    return $string;
}

/**
 * স্লাগ তৈরি করুন (URL-safe)
 */
function createSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// ============================================
// DEBUG HELPERS
// ============================================

/**
 * ডেবাগ তথ্য প্রিন্ট করুন (পড়তে সহজ)
 */
function debug($data, $exit = false) {
    echo '<pre style="background: #f4f4f4; padding: 10px; border: 1px solid #ddd; margin: 10px 0;">';
    print_r($data);
    echo '</pre>';
    
    if ($exit) {
        exit;
    }
}

/**
 * লগ ফাইলে লিখুন
 */
function writeLog($message, $level = 'INFO') {
    $logFile = LOG_PATH . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$level}] {$message}\n";
    
    // ডিরেক্টরি না থাকলে তৈরি করুন
    if (!is_dir(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

?>
