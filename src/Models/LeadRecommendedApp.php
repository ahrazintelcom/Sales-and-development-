<?php

namespace App\Models;

class LeadRecommendedApp extends Model
{
    public function getByLead(int $leadId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM lead_recommended_apps WHERE lead_id = :lead_id');
        $stmt->execute(['lead_id' => $leadId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO lead_recommended_apps (lead_id, app_type, app_name, description, key_features, benefits, price_min, price_max, created_at, updated_at) VALUES (:lead_id, :app_type, :app_name, :description, :key_features, :benefits, :price_min, :price_max, NOW(), NOW())');
        $stmt->execute([
            'lead_id' => $data['lead_id'],
            'app_type' => $data['app_type'],
            'app_name' => $data['app_name'],
            'description' => $data['description'],
            'key_features' => json_encode($data['key_features']),
            'benefits' => json_encode($data['benefits']),
            'price_min' => $data['price_min'],
            'price_max' => $data['price_max'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteByLead(int $leadId): void
    {
        $stmt = $this->db->prepare('DELETE FROM lead_recommended_apps WHERE lead_id = :lead_id');
        $stmt->execute(['lead_id' => $leadId]);
    }
}
