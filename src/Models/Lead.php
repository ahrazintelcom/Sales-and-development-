<?php

namespace App\Models;

use PDO;

class Lead extends Model
{
    public function all(array $filters = []): array
    {
        $query = 'SELECT * FROM leads WHERE 1=1';
        $params = [];
        if (!empty($filters['country'])) {
            $query .= ' AND country = :country';
            $params['country'] = $filters['country'];
        }
        if (!empty($filters['industry'])) {
            $query .= ' AND industry = :industry';
            $params['industry'] = $filters['industry'];
        }
        if (!empty($filters['company_size'])) {
            $query .= ' AND company_size = :company_size';
            $params['company_size'] = $filters['company_size'];
        }
        if (!empty($filters['city'])) {
            $query .= ' AND city LIKE :city';
            $params['city'] = '%' . $filters['city'] . '%';
        }
        $query .= ' ORDER BY created_at DESC';
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM leads WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO leads (company_name, website, industry, description, country, state_province, city, company_size, contact_name, contact_email, lead_score, status, created_at, updated_at) VALUES (:company_name, :website, :industry, :description, :country, :state_province, :city, :company_size, :contact_name, :contact_email, :lead_score, :status, NOW(), NOW())');
        $stmt->execute([
            'company_name' => $data['company_name'],
            'website' => $data['website'] ?? '',
            'industry' => $data['industry'] ?? '',
            'description' => $data['description'] ?? null,
            'country' => $data['country'] ?? '',
            'state_province' => $data['state_province'] ?? '',
            'city' => $data['city'] ?? '',
            'company_size' => $data['company_size'] ?? '',
            'contact_name' => $data['contact_name'] ?? '',
            'contact_email' => $data['contact_email'] ?? '',
            'lead_score' => $data['lead_score'] ?? 0,
            'status' => $data['status'] ?? 'new',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE leads SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function updateLeadScore(int $id, int $score): void
    {
        $stmt = $this->db->prepare('UPDATE leads SET lead_score = :score WHERE id = :id');
        $stmt->execute(['score' => $score, 'id' => $id]);
    }
}
