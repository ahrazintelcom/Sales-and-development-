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
    public function updateLeadScore(int $id, int $score): void
    {
        $stmt = $this->db->prepare('UPDATE leads SET lead_score = :score WHERE id = :id');
        $stmt->execute(['score' => $score, 'id' => $id]);
    }

    public function updateAiData(int $id, array $data): void
    {
        $stmt = $this->db->prepare('UPDATE leads SET 
            ai_app_type = :ai_app_type,
            ai_insight_summary = :ai_insight_summary,
            ai_app_concept = :ai_app_concept,
            ai_app_features = :ai_app_features,
            ai_app_benefits = :ai_app_benefits,
            ai_price_min = :ai_price_min,
            ai_price_max = :ai_price_max,
            ai_call_script = :ai_call_script,
            ai_email_script = :ai_email_script,
            ai_talking_points = :ai_talking_points,
            ai_full_proposal = :ai_full_proposal,
            ai_last_generated_at = :ai_last_generated_at,
            updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'ai_app_type' => $data['ai_app_type'] ?? null,
            'ai_insight_summary' => $data['ai_insight_summary'] ?? null,
            'ai_app_concept' => $data['ai_app_concept'] ?? null,
            'ai_app_features' => $data['ai_app_features'] ?? null,
            'ai_app_benefits' => $data['ai_app_benefits'] ?? null,
            'ai_price_min' => $data['ai_price_min'] ?? null,
            'ai_price_max' => $data['ai_price_max'] ?? null,
            'ai_call_script' => $data['ai_call_script'] ?? null,
            'ai_email_script' => $data['ai_email_script'] ?? null,
            'ai_talking_points' => $data['ai_talking_points'] ?? null,
            'ai_full_proposal' => $data['ai_full_proposal'] ?? null,
            'ai_last_generated_at' => $data['ai_last_generated_at'] ?? null,
            'id' => $id,
        ]);
    }

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

    public function existsByCompanyOrWebsite(?string $companyName, ?string $website): bool
    {
        $conditions = [];
        $params = [];
        if ($companyName) {
            $conditions[] = 'LOWER(company_name) = LOWER(:company_name)';
            $params['company_name'] = $companyName;
        }
        if ($website) {
            $conditions[] = 'LOWER(website) = LOWER(:website)';
            $params['website'] = $website;
        }
        if (empty($conditions)) {
            return false;
        }
        $query = 'SELECT COUNT(*) FROM leads WHERE ' . implode(' OR ', $conditions);
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
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