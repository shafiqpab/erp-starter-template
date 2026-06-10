<?php
/**
 * ACTIVITY LOGGER CLASS
 * কার্যকলাপ লগ ক্লাস - সমস্ত ব্যবহারকারী অ্যাকশন ট্র্যাক করে
 */

class ActivityLogger {
    private $db = null;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * লগ রেকর্ড যোগ করুন
     */
    public function log($userId, $action, $module, $description = '', $oldValue = '', $newValue = '', $status = 'success') {
        $ipAddress = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $sql = "INSERT INTO `activity_logs` 
                (`user_id`, `action`, `module`, `description`, `old_value`, `new_value`, `ip_address`, `user_agent`, `status`) 
                VALUES 
                ({$userId}, 
                 '{$this->db->escape($action)}',
                 '{$this->db->escape($module)}',
                 '{$this->db->escape($description)}',
                 '{$this->db->escape($oldValue)}',
                 '{$this->db->escape($newValue)}',
                 '{$ipAddress}',
                 '{$this->db->escape($userAgent)}',
                 '{$status}')";

        return $this->db->query($sql);
    }

    /**
     * ব্যবহারকারীর সকল লগ পান
     */
    public function getUserLogs($userId, $limit = 50, $offset = 0) {
        $sql = "SELECT * FROM `activity_logs` 
                WHERE `user_id` = {$userId}
                ORDER BY `created_at` DESC
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql);
    }

    /**
     * মডিউল অনুযায়ী লগ পান
     */
    public function getModuleLogs($module, $limit = 50, $offset = 0) {
        $sql = "SELECT * FROM `activity_logs` 
                WHERE `module` = '{$this->db->escape($module)}'
                ORDER BY `created_at` DESC
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql);
    }

    /**
     * একটি নির্দিষ্ট সময়ের মধ্যে লগ পান
     */
    public function getLogsByDateRange($startDate, $endDate, $limit = 100, $offset = 0) {
        $sql = "SELECT * FROM `activity_logs` 
                WHERE `created_at` BETWEEN '{$this->db->escape($startDate)}' AND '{$this->db->escape($endDate)}'
                ORDER BY `created_at` DESC
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql);
    }

    /**
     * সকল লগ পান (অ্যাডমিনের জন্য)
     */
    public function getAllLogs($limit = 100, $offset = 0) {
        $sql = "SELECT al.*, u.`username` FROM `activity_logs` al
                LEFT JOIN `users` u ON al.`user_id` = u.`id`
                ORDER BY al.`created_at` DESC
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql);
    }

    /**
     * একটি লগ পান (ID দ্বারা)
     */
    public function getLogById($logId) {
        $sql = "SELECT al.*, u.`username` FROM `activity_logs` al
                LEFT JOIN `users` u ON al.`user_id` = u.`id`
                WHERE al.`id` = {$logId}";
        return $this->db->fetchOne($sql);
    }

    /**
     * ব্যবহারকারী-বিশেষ অ্যাকশন লগ করুন (সহজ)
     */
    public function logUserAction($userId, $action, $description = '') {
        return $this->log($userId, $action, 'user', $description, '', '', 'success');
    }

    /**
     * পারমিশন সংক্রান্ত অ্যাকশন লগ করুন
     */
    public function logPermissionAction($userId, $action, $description = '', $oldValue = '', $newValue = '') {
        return $this->log($userId, $action, 'permission', $description, $oldValue, $newValue, 'success');
    }

    /**
     * মেনু সংক্রান্ত অ্যাকশন লগ করুন
     */
    public function logMenuAction($userId, $action, $description = '', $oldValue = '', $newValue = '') {
        return $this->log($userId, $action, 'menu', $description, $oldValue, $newValue, 'success');
    }

    /**
     * রোল সংক্রান্ত অ্যাকশন লগ করুন
     */
    public function logRoleAction($userId, $action, $description = '', $oldValue = '', $newValue = '') {
        return $this->log($userId, $action, 'role', $description, $oldValue, $newValue, 'success');
    }

    /**
     * ব্যর্থ অ্যাকশন লগ করুন (যেমন ব্যর্থ লগইন)
     */
    public function logFailedAction($userId, $action, $module, $description = '') {
        return $this->log($userId, $action, $module, $description, '', '', 'failed');
    }

    /**
     * সতর্কতা লগ করুন
     */
    public function logWarning($userId, $action, $module, $description = '') {
        return $this->log($userId, $action, $module, $description, '', '', 'warning');
    }

    /**
     * লগ সংখ্যা পান (মোট)
     */
    public function getLogCount() {
        $result = $this->db->fetchOne("SELECT COUNT(*) as `count` FROM `activity_logs`");
        return $result['count'] ?? 0;
    }

    /**
     * ব্যবহারকারীর লগ সংখ্যা পান
     */
    public function getUserLogCount($userId) {
        $result = $this->db->fetchOne("SELECT COUNT(*) as `count` FROM `activity_logs` WHERE `user_id` = {$userId}");
        return $result['count'] ?? 0;
    }

    /**
     * মডিউলের লগ সংখ্যা পান
     */
    public function getModuleLogCount($module) {
        $result = $this->db->fetchOne("SELECT COUNT(*) as `count` FROM `activity_logs` WHERE `module` = '{$this->db->escape($module)}'");
        return $result['count'] ?? 0;
    }

    /**
     * পুরানো লগ ডিলিট করুন (আর্কাইভ করার জন্য)
     */
    public function deleteOldLogs($days = 90) {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $sql = "DELETE FROM `activity_logs` WHERE `created_at` < '{$date}'";
        return $this->db->query($sql);
    }

    /**
     * ক্লায়েন্ট IP পান
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        }
        return $ip;
    }

    /**
     * লগ রিপোর্ট পান (মডিউল অনুযায়ী)
     */
    public function getLogReportByModule() {
        $sql = "SELECT `module`, COUNT(*) as `count`, 
                SUM(CASE WHEN `status` = 'success' THEN 1 ELSE 0 END) as `success_count`,
                SUM(CASE WHEN `status` = 'failed' THEN 1 ELSE 0 END) as `failed_count`,
                SUM(CASE WHEN `status` = 'warning' THEN 1 ELSE 0 END) as `warning_count`
                FROM `activity_logs`
                GROUP BY `module`
                ORDER BY `count` DESC";
        return $this->db->fetchAll($sql);
    }

    /**
     * লগ রিপোর্ট পান (অ্যাকশন অনুযায়ী)
     */
    public function getLogReportByAction() {
        $sql = "SELECT `action`, COUNT(*) as `count`
                FROM `activity_logs`
                GROUP BY `action`
                ORDER BY `count` DESC
                LIMIT 20";
        return $this->db->fetchAll($sql);
    }
}
?>
