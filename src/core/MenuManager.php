<?php
/**
 * MENU MANAGER CLASS
 * মেনু পরিচালনা ক্লাস - Tree Structure সাপোর্ট সহ
 */

class MenuManager {
    private $db = null;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * সমস্ত মেনু পান (হায়ারার্কি সহ)
     */
    public function getAllMenus($onlyActive = true) {
        $sql = "SELECT * FROM `menus`";
        if ($onlyActive) {
            $sql .= " WHERE `status` = 'active'";
        }
        $sql .= " ORDER BY `sort_order` ASC, `menu_name` ASC";
        $menus = $this->db->fetchAll($sql);
        
        // Tree structure তৈরি করুন
        return $this->buildTree($menus);
    }

    /**
     * ব্যবহারকারীর জন্য সাইডবার মেনু পান (অনুমতি চেক সহ)
     */
    public function getSidebarMenus($userId) {
        $permissionEngine = new PermissionEngine($userId);
        
        $sql = "SELECT * FROM `menus` WHERE `status` = 'active' ORDER BY `sort_order` ASC, `menu_name` ASC";
        $menus = $this->db->fetchAll($sql);
        
        // অনুমতি চেক করে ফিল্টার করুন
        $filteredMenus = [];
        foreach ($menus as $menu) {
            if ($menu['permission_id']) {
                $permResult = $this->db->fetchOne("SELECT `permission_code` FROM `permissions` WHERE `id` = {$menu['permission_id']}");
                if ($permResult && !$permissionEngine->hasPermission($permResult['permission_code'])) {
                    continue; // এই মেনু এড়িয়ে যান
                }
            }
            $filteredMenus[] = $menu;
        }
        
        return $this->buildTree($filteredMenus);
    }

    /**
     * Tree structure তৈরি করুন
     */
    private function buildTree($items, $parentId = null) {
        $tree = [];
        
        foreach ($items as $item) {
            if ($item['parent_id'] === $parentId) {
                $item['children'] = $this->buildTree($items, $item['id']);
                $tree[] = $item;
            }
        }
        
        return $tree;
    }

    /**
     * একটি মেনু পান (ID দ্বারা)
     */
    public function getMenuById($menuId) {
        $sql = "SELECT * FROM `menus` WHERE `id` = {$menuId}";
        return $this->db->fetchOne($sql);
    }

    /**
     * একটি মেনু পান (কোড দ্বারা)
     */
    public function getMenuByCode($menuCode) {
        $sql = "SELECT * FROM `menus` WHERE `menu_code` = '{$this->db->escape($menuCode)}'";
        return $this->db->fetchOne($sql);
    }

    /**
     * নতুন মেনু তৈরি করুন
     */
    public function createMenu($menuName, $menuCode, $route = null, $icon = null, $permissionId = null, $parentId = null, $sortOrder = 0) {
        // মেনু কোড ইতিমধ্যে বিদ্যমান কিনা চেক করুন
        $existing = $this->getMenuByCode($menuCode);
        if ($existing) {
            return ['success' => false, 'message' => 'এই মেনু কোড ইতিমধ্যে বিদ্যমান'];
        }

        $parentIdVal = $parentId ? $parentId : 'NULL';
        $permissionIdVal = $permissionId ? $permissionId : 'NULL';
        $routeVal = $route ? "'{$this->db->escape($route)}'" : 'NULL';
        $iconVal = $icon ? "'{$this->db->escape($icon)}'" : 'NULL';

        $sql = "INSERT INTO `menus` 
                (`menu_name`, `menu_code`, `parent_id`, `icon`, `route`, `permission_id`, `sort_order`) 
                VALUES 
                ('{$this->db->escape($menuName)}', 
                 '{$this->db->escape($menuCode)}', 
                 {$parentIdVal},
                 {$iconVal},
                 {$routeVal},
                 {$permissionIdVal},
                 {$sortOrder})";

        if ($this->db->query($sql)) {
            return ['success' => true, 'message' => 'মেনু সফলভাবে তৈরি হয়েছে', 'id' => $this->db->lastInsertId()];
        }

        return ['success' => false, 'message' => 'মেনু তৈরি ব্যর্থ: ' . $this->db->getLastError()];
    }

    /**
     * মেনু আপডেট করুন
     */
    public function updateMenu($menuId, $menuName, $route, $icon, $permissionId, $parentId, $sortOrder, $status) {
        $parentIdVal = $parentId ? $parentId : 'NULL';
        $permissionIdVal = $permissionId ? $permissionId : 'NULL';
        $routeVal = $route ? "'{$this->db->escape($route)}'" : 'NULL';
        $iconVal = $icon ? "'{$this->db->escape($icon)}'" : 'NULL';

        $sql = "UPDATE `menus` 
                SET `menu_name` = '{$this->db->escape($menuName)}',
                    `route` = {$routeVal},
                    `icon` = {$iconVal},
                    `permission_id` = {$permissionIdVal},
                    `parent_id` = {$parentIdVal},
                    `sort_order` = {$sortOrder},
                    `status` = '{$status}'
                WHERE `id` = {$menuId}";

        if ($this->db->query($sql)) {
            return ['success' => true, 'message' => 'মেনু সফলভাবে আপডেট হয়েছে'];
        }

        return ['success' => false, 'message' => 'মেনু আপডেট ব্যর্থ: ' . $this->db->getLastError()];
    }

    /**
     * মেনু ডিলিট করুন
     */
    public function deleteMenu($menuId) {
        // চেক করুন এই মেনুর কোনো চাইল্ড আছে কিনা
        $childCount = $this->db->fetchOne("SELECT COUNT(*) as `count` FROM `menus` WHERE `parent_id` = {$menuId}");
        if ($childCount['count'] > 0) {
            return ['success' => false, 'message' => 'এই মেনুর ' . $childCount['count'] . 'টি সাব-মেনু আছে। প্রথমে সেগুলি ডিলিট করুন।'];
        }

        $sql = "DELETE FROM `menus` WHERE `id` = {$menuId}";

        if ($this->db->query($sql)) {
            return ['success' => true, 'message' => 'মেনু সফলভাবে ডিলিট হয়েছে'];
        }

        return ['success' => false, 'message' => 'মেনু ডিলিট ব্যর্থ: ' . $this->db->getLastError()];
    }

    /**
     * HTML মেনু রেন্ডার করুন (সাইডবার)
     */
    public function renderSidebarHTML($menus, $activeRoute = '') {
        $html = '<ul class="menu-list">';
        
        foreach ($menus as $menu) {
            $isActive = ($menu['route'] === $activeRoute) ? 'active' : '';
            $hasChildren = !empty($menu['children']);
            
            $html .= '<li class="menu-item ' . $isActive . '">';
            
            if ($hasChildren) {
                // প্যারেন্ট মেনু (ড্রপডাউন)
                $html .= '<a href="javascript:void(0)" class="menu-link dropdown-toggle">';
                if ($menu['icon']) {
                    $html .= '<i class="' . htmlspecialchars($menu['icon']) . '"></i>';
                }
                $html .= '<span class="menu-text">' . htmlspecialchars($menu['menu_name']) . '</span>';
                $html .= '<i class="dropdown-icon fas fa-chevron-down"></i>';
                $html .= '</a>';
                
                // সাব-মেনু
                $html .= $this->renderSidebarHTML($menu['children'], $activeRoute);
            } else {
                // চাইল্ড মেনু (লিঙ্ক)
                $html .= '<a href="' . htmlspecialchars($menu['route'] ?? '#') . '" class="menu-link">';
                if ($menu['icon']) {
                    $html .= '<i class="' . htmlspecialchars($menu['icon']) . '"></i>';
                }
                $html .= '<span class="menu-text">' . htmlspecialchars($menu['menu_name']) . '</span>';
                $html .= '</a>';
            }
            
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        
        return $html;
    }

    /**
     * সমস্ত উপলব্ধ মেনু পান (ম্যানেজমেন্টের জন্য)
     */
    public function getAllMenusFlat() {
        $sql = "SELECT * FROM `menus` ORDER BY `sort_order` ASC";
        return $this->db->fetchAll($sql);
    }

    /**
     * রুট পান যেকোনো মেনু দ্বারা
     */
    public function getRouteByMenuCode($menuCode) {
        $menu = $this->getMenuByCode($menuCode);
        return $menu ? $menu['route'] : null;
    }
}
?>
