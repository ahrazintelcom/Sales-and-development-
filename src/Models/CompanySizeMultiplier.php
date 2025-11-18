<?php

namespace App\Models;

class CompanySizeMultiplier extends Model
{
    public function all(): array
    {
        return $this->db->query('SELECT * FROM company_size_multipliers ORDER BY size_category ASC')->fetchAll();
    }

    public function set(string $category, float $multiplier): void
    {
        $stmt = $this->db->prepare('INSERT INTO company_size_multipliers (size_category, multiplier, created_at, updated_at) VALUES (:size_category, :multiplier, NOW(), NOW()) ON DUPLICATE KEY UPDATE multiplier = :multiplier, updated_at = NOW()');
        $stmt->execute(['size_category' => $category, 'multiplier' => $multiplier]);
    }

    public function getMultiplier(string $category): float
    {
        $stmt = $this->db->prepare('SELECT multiplier FROM company_size_multipliers WHERE size_category = :size_category');
        $stmt->execute(['size_category' => $category]);
        $value = $stmt->fetchColumn();
        return $value ? (float)$value : 1.0;
    }
}
