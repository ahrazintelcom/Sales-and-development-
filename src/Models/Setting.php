<?php

namespace App\Models;

class Setting extends Model
{
    public function get(string $key, $default = null)
    {
        $stmt = $this->db->prepare('SELECT value FROM settings WHERE `key` = :key');
        $stmt->execute(['key' => $key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->db->prepare('INSERT INTO settings (`key`, `value`) VALUES (:key, :value) ON DUPLICATE KEY UPDATE value = VALUES(value)');
        $stmt->execute(['key' => $key, 'value' => $value]);
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM settings');
        return $stmt->fetchAll();
    }
}
