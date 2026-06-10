<?php
/**
 * ROLE MANAGER CLASS
 * ভূমিকা পরিচালনা ক্লাস
 */

class RoleManager {
    private $db = null;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * সমস্ত ভূমিকা পান
     */
    public function getAllRoles($onlyActive = true) {
        $sql = "SELECT * FROM `roles`";
        if ($onlyActive) {
            $sql .= " WHERE `status` = 'active'";
        }
        $sql .= " ORDER BY `role_name` ASC";
        return $this->db->fetchAll($sql);
    }

    /**
     * একটি ভূমিকা পান (ID দ্বারা)
     */
    public function getRoleById($roleId) {
        $sql = "SELECT * FROM `roles` WHERE `id` = {$roleId}";
        return $this->db->fetchOne($sql);
    }

    /**
     * একটি ভূমিকা পান (কোড দ্বারা)
     */
    public function getRoleByCode($roleCode) {
        $sql = "SELECT * FROM `roles` WHERE `role_code` = '{$this->db->escape($roleCode)}'";
        return $this->db->fetchOne($sql);
    }

    /**
     * নতুন ভূমিকা তৈরি করুন
     */
    public function createRole($roleName, $roleCode, $description = '', $status = 'active') {
        // ভূমিকা কোড ইতিমধ্যে বিদ্যমান কিনা চেক করুন
        $existing = $this->getRoleByCode($roleCode);
        if ($existing) {
            return ['success' => false, 'message' => 'এই ভূমিকা কোড ইতিমধ্যে বিদ্যমান'];
        }

        $sql = "INSERT INTO `roles` 
                (`role_name`, `role_code`, `description`, `status`) 
                VALUES 
                ('{$this->db->escape($roleName)}', 
                 '{$this->db->escape($roleCode)}', 
                 '{$this->db->escape($description)}', 
                 '{$status}')";

        if ($this->db->query($sql)) {
            return ['success' => true, 'message' => 'ভূমিকা সফলভাবে তৈরি হয়েছে', 'id' => $this->db->lastInsertId()];
        }

        return ['success' => false, 'message' => 'ভূমিকা তৈরি ব্যর্থ: ' . $this->db->getLastError()];
    }

    /**
     * ভূমিকা আপডেট করুন
     */
    public function updateRole($roleId, $roleName, $description, $status) {
        $sql = "UPDATE `roles` 
                SET `role_name` = '{$this->db->escape($roleName)}',
                    `description` = '{$this->db->escape($description)}',
                    `status` = '{$status}'
                WHERE `id` = {$roleId}";

        if ($this->db->query($sql)) {
            return ['success' => true, 'message' => 'ভূমিকা সফলভাবে আপডেট হয়েছে'];
        }

        return ['success' => false, 'message' => 'ভূমিকা আপডেট ব্যর্থ: ' . $this->db->getLastError()];
    }

    /**
     * ভূমিকা ডিলিট করুন
     */
    public function deleteRole($roleId) {
        // চেক করুন এই ভূমিকা কোনো ব্যবহারকারীর কাছে আছে কিনা
        $usersCount = $this->db->fetchOne("SELECT COUNT(*) as `count` FROM `users` WHERE `role_id` = {$roleId}");
        if ($usersCount['count'] > 0) {
            return ['success' => false, 'message' => 'এই ভূমিকা ' . $usersCount['count'] . ' জন ব্যবহারকারী ব্যবহার করছেন। প্রথমে তাদের অন্য ভূমিকায় স্থানান্তর করুন।'];
        }

        $sql = "DELETE FROM `roles` WHERE `id` = {$roleId}";

        if ($this->db->query($sql)) {
            return ['success' => true, 'message' => 'ভূমিকা সফলভাবে ডিলিট হয়েছে'];
        }

        return ['success' => false, 'message' => 'ভূমিকা ডিলিট ব্যর্থ: ' . $this->db->getLastError()];
    }

    /**
     * ভূমিকায় অনুমতি যোগ করুন
     */
    public function assignPermissionToRole($roleId, $permissionId) {
        // ইতিমধ্যে বিদ্যমান কিনা চেক করুন
        $existing = $this->db->fetchOne("SELECT * FROM `role_permissions` WHERE `role_id` = {$roleId} AND `permission_id` = {$permissionId}");
        if ($existing) {
            return ['success' => false, 'message' => 'এই অনুমতি ইতিমধ্যে এই ভূমিকায় আছে'];
        }

        $sql = "INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ({$roleId}, {$permissionId})";

        if ($this->db->query($sql)) {
            return ['success' => true, 'message' => 'অনুমতি যোগ হয়েছে'];
        }

        return ['success' => false, 'message' => 'অনুমতি যোগ ব্যর্থ'];
    }

    /**
     * ভূমিকা থেকে অনুমতি সরান
     */
    public function removePermissionFromRole($roleId, $permissionId) {
        $sql = "DELETE FROM `role_permissions` WHERE `role_id` = {$roleId} AND `permission_id` = {$permissionId}";

        if ($this->db->query($sql)) {
            return ['success' => true, 'message' => 'অনুমতি সরানো হয়েছে'];
        }

        return ['success' => false, 'message' => 'অনুমতি সরানো ব্যর্থ'];
    }

    /**
     * ভূমিকার সমস্ত অনুমতি পান
     */
    public function getRolePermissions($roleId) {
        $sql = "SELECT p.* FROM `permissions` p
                INNER JOIN `role_permissions` rp ON p.`id` = rp.`permission_id`
                WHERE rp.`role_id` = {$roleId}";
        return $this->db->fetchAll($sql);
    }

    /**
     * ভূমিকার অনুমতি IDs পান (সহজ অ্যারে)
     */
    public function getRolePermissionIds($roleId) {
        $permissions = $this->getRolePermissions($roleId);
        $ids = [];
        foreach ($permissions as $perm) {
            $ids[] = $perm['id'];
        }
        return $ids;
    }
}
?>
