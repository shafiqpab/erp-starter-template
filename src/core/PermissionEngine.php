<?php
/**
 * PERMISSION ENGINE CLASS
 * অনুমতি ইঞ্জিন - অনুমতি যাচাই এবং নিয়ন্ত্রণের কেন্দ্রীয় ক্লাস
 * 
 * অনুমতি যাচাইকরণের ক্রম:
 * 1. DENY সর্বোচ্চ অগ্রাধিকার (User DENY = কোনো অ্যাক্সেস নেই)
 * 2. USER ALLOW ভূমিকা ALLOW কে ওভাররাইড করে
 * 3. ভূমিকা অনুমতি ডিফল্ট বেস
 */

class PermissionEngine {
    private $db = null;
    private $userId = null;
    private $userPermissions = [];
    private $userRole = null;
    private $cacheEnabled = true;
    private $cacheTTL = 3600; // 1 hour

    public function __construct($userId = null) {
        $this->db = Database::getInstance();
        $this->userId = $userId;
        if ($this->userId) {
            $this->loadUserPermissions();
        }
    }

    /**
     * ব্যবহারকারীর সমস্ত অনুমতি লোড করুন
     * (ক্যাশ থেকে বা ডাটাবেস থেকে)
     */
    private function loadUserPermissions() {
        // প্রথমে ক্যাশ থেকে চেষ্টা করুন
        if ($this->cacheEnabled) {
            $cachedPermissions = $this->getPermissionsFromCache();
            if ($cachedPermissions !== false) {
                $this->userPermissions = $cachedPermissions;
                return;
            }
        }

        // ডাটাবেস থেকে লোড করুন
        $permissions = [];

        // ধাপ ১: ব্যবহারকারীর ভূমিকা পান
        $roleQuery = "SELECT `role_id` FROM `users` WHERE `id` = {$this->userId}";
        $roleResult = $this->db->fetchOne($roleQuery);
        $roleId = $roleResult['role_id'] ?? null;
        $this->userRole = $roleId;

        // ধাপ ২: ভূমিকার অনুমতি পান
        if ($roleId) {
            $rolePermQuery = "
                SELECT p.`permission_code`, p.`id` 
                FROM `permissions` p
                INNER JOIN `role_permissions` rp ON p.`id` = rp.`permission_id`
                WHERE rp.`role_id` = {$roleId} AND p.`status` = 'active'
            ";
            $rolePerms = $this->db->fetchAll($rolePermQuery);
            foreach ($rolePerms as $perm) {
                $permissions[$perm['permission_code']] = [
                    'id' => $perm['id'],
                    'source' => 'role',
                    'allowed' => true
                ];
            }
        }

        // ধাপ ৩: ব্যবহারকারী ওভাররাইড অনুমতি প্রয়োগ করুন
        $overrideQuery = "
            SELECT p.`permission_code`, upo.`override_type` 
            FROM `user_permission_overrides` upo
            INNER JOIN `permissions` p ON upo.`permission_id` = p.`id`
            WHERE upo.`user_id` = {$this->userId}
        ";
        $overrides = $this->db->fetchAll($overrideQuery);
        foreach ($overrides as $override) {
            if ($override['override_type'] === 'deny') {
                // DENY সর্বোচ্চ অগ্রাধিকার
                $permissions[$override['permission_code']] = [
                    'source' => 'user_override',
                    'allowed' => false
                ];
            } else if ($override['override_type'] === 'allow') {
                // USER ALLOW ভূমিকা ALLOW কে ওভাররাইড করে
                $permissions[$override['permission_code']] = [
                    'source' => 'user_override',
                    'allowed' => true
                ];
            }
        }

        $this->userPermissions = $permissions;

        // ক্যাশে সেভ করুন
        if ($this->cacheEnabled) {
            $this->cachePermissions($permissions);
        }
    }

    /**
     * চেক করুন ব্যবহারকারীর একটি নির্দিষ্ট অনুমতি আছে কিনা
     */
    public function hasPermission($permissionCode) {
        if (!$this->userId) {
            return false;
        }

        if (empty($this->userPermissions)) {
            return false;
        }

        if (!isset($this->userPermissions[$permissionCode])) {
            return false;
        }

        $perm = $this->userPermissions[$permissionCode];
        return $perm['allowed'] === true;
    }

    /**
     * চেক করুন ব্যবহারকারীর একাধিক অনুমতির যেকোনো একটি আছে কিনা
     */
    public function hasAnyPermission(array $permissionCodes) {
        foreach ($permissionCodes as $code) {
            if ($this->hasPermission($code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * চেক করুন ব্যবহারকারীর সমস্ত অনুমতি আছে কিনা
     */
    public function hasAllPermissions(array $permissionCodes) {
        foreach ($permissionCodes as $code) {
            if (!$this->hasPermission($code)) {
                return false;
            }
        }
        return true;
    }

    /**
     * সমস্ত ব্যবহারকারীর অনুমতি পান
     */
    public function getAllPermissions() {
        return $this->userPermissions;
    }

    /**
     * সমস্ত অনুমতি কোড পান যা ব্যবহারকারীর আছে
     */
    public function getPermissionCodes() {
        $codes = [];
        foreach ($this->userPermissions as $code => $perm) {
            if ($perm['allowed'] === true) {
                $codes[] = $code;
            }
        }
        return $codes;
    }

    /**
     * ক্যাশ থেকে অনুমতি পান
     */
    private function getPermissionsFromCache() {
        $sql = "SELECT `permissions_json`, `expires_at` FROM `permission_cache` 
                WHERE `user_id` = {$this->userId}";
        $result = $this->db->fetchOne($sql);

        if (!$result) {
            return false;
        }

        // ক্যাশ এক্সপায়ার হয়েছে কিনা চেক করুন
        if ($result['expires_at'] && strtotime($result['expires_at']) < time()) {
            return false;
        }

        return json_decode($result['permissions_json'], true);
    }

    /**
     * অনুমতি ক্যাশে সেভ করুন
     */
    private function cachePermissions($permissions) {
        $json = json_encode($permissions);
        $expiresAt = date('Y-m-d H:i:s', time() + $this->cacheTTL);

        $sql = "INSERT INTO `permission_cache` 
                (`user_id`, `permissions_json`, `expires_at`) 
                VALUES ({$this->userId}, '{$this->db->escape($json)}', '{$expiresAt}')
                ON DUPLICATE KEY UPDATE 
                `permissions_json` = VALUES(`permissions_json`),
                `expires_at` = VALUES(`expires_at`)";

        $this->db->query($sql);
    }

    /**
     * ব্যবহারকারীর ক্যাশ পরিষ্কার করুন
     */
    public function clearCache() {
        $sql = "DELETE FROM `permission_cache` WHERE `user_id` = {$this->userId}";
        $this->db->query($sql);
        $this->userPermissions = [];
    }

    /**
     * ক্যাশিং সক্ষম/নিষ্ক্রিয় করুন
     */
    public function setCacheEnabled($enabled = true) {
        $this->cacheEnabled = $enabled;
    }

    /**
     * ক্যাশ TTL সেট করুন (সেকেন্ডে)
     */
    public function setCacheTTL($seconds) {
        $this->cacheTTL = $seconds;
    }

    /**
     * ব্যবহারকারীর ভূমিকা ID পান
     */
    public function getUserRoleId() {
        return $this->userRole;
    }

    /**
     * ডিবাগিং তথ্য পান
     */
    public function getDebugInfo() {
        return [
            'user_id' => $this->userId,
            'user_role_id' => $this->userRole,
            'total_permissions' => count($this->userPermissions),
            'allowed_permissions' => count(array_filter($this->userPermissions, function($p) {
                return $p['allowed'] === true;
            })),
            'denied_permissions' => count(array_filter($this->userPermissions, function($p) {
                return $p['allowed'] === false;
            })),
            'permissions' => $this->userPermissions
        ];
    }
}
?>
