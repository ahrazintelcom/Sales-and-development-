<?php

namespace App\Models;

class PricingRule extends Model
{
    public function all(): array
    {
        return $this->db->query('SELECT * FROM pricing_rules ORDER BY app_type ASC')->fetchAll();
    }

    public function set(string $appType, float $basePrice): void
    {
        $stmt = $this->db->prepare('INSERT INTO pricing_rules (app_type, base_price, created_at, updated_at) VALUES (:app_type, :base_price, NOW(), NOW()) ON DUPLICATE KEY UPDATE base_price = :base_price, updated_at = NOW()');
        $stmt->execute(['app_type' => $appType, 'base_price' => $basePrice]);
    }
}
