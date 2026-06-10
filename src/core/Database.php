<?php
/**
 * DATABASE CONNECTION CLASS
 * ডাটাবেস সংযোগ ক্লাস
 */

class Database {
    private static $instance = null;
    private $connection = null;
    private $lastError = null;

    private function __construct() {
        try {
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME
            );

            // চেক করুন সংযোগ সফল হয়েছে কিনা
            if ($this->connection->connect_error) {
                throw new Exception('ডাটাবেস সংযোগ ব্যর্থ: ' . $this->connection->connect_error);
            }

            // চ্যারসেট সেট করুন
            $this->connection->set_charset(DB_CHARSET);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            die('Database Error: ' . $this->lastError);
        }
    }

    /**
     * সিঙ্গেলটন ইনস্ট্যান্স পান
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * সংযোগ অবজেক্ট ফেরত দিন
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * কোয়েরি চালান (SELECT)
     */
    public function query($sql) {
        $result = $this->connection->query($sql);
        if (!$result) {
            $this->lastError = $this->connection->error;
            return false;
        }
        return $result;
    }

    /**
     * প্রস্তুত স্টেটমেন্ট তৈরি করুন (নিরাপদ)
     */
    public function prepare($sql) {
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            $this->lastError = $this->connection->error;
            return false;
        }
        return $stmt;
    }

    /**
     * সমস্ত ফলাফল ফেরত দিন (Array)
     */
    public function fetchAll($sql) {
        $result = $this->query($sql);
        if (!$result) return false;
        
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * একটি সারি ফেরত দিন
     */
    public function fetchOne($sql) {
        $result = $this->query($sql);
        if (!$result) return false;
        return $result->fetch_assoc();
    }

    /**
     * শেষ ইনসার্টেড আইডি পান
     */
    public function lastInsertId() {
        return $this->connection->insert_id;
    }

    /**
     * প্রভাবিত সারির সংখ্যা পান
     */
    public function affectedRows() {
        return $this->connection->affected_rows;
    }

    /**
     * শেষ ত্রুটি বার্তা পান
     */
    public function getLastError() {
        return $this->lastError;
    }

    /**
     * ট্রানজ্যাকশন শুরু করুন
     */
    public function beginTransaction() {
        return $this->connection->begin_transaction();
    }

    /**
     * ট্রানজ্যাকশন কমিট করুন
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * ট্রানজ্যাকশন রোলব্যাক করুন
     */
    public function rollback() {
        return $this->connection->rollback();
    }

    /**
     * স্ট্রিং এস্কেপ করুন (নিরাপত্তা)
     */
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
}
?>
