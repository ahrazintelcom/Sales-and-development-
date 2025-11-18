<?php

namespace App\Models;

class Project extends Model
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO projects (lead_id, project_name, app_type, status, spec_summary, tech_notes, github_repo_url, created_at, updated_at) VALUES (:lead_id, :project_name, :app_type, :status, :spec_summary, :tech_notes, :github_repo_url, NOW(), NOW())');
        $stmt->execute([
            'lead_id' => $data['lead_id'],
            'project_name' => $data['project_name'],
            'app_type' => $data['app_type'],
            'status' => $data['status'] ?? 'backlog',
            'spec_summary' => $data['spec_summary'] ?? '',
            'tech_notes' => $data['tech_notes'] ?? '',
            'github_repo_url' => $data['github_repo_url'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT projects.*, leads.company_name FROM projects JOIN leads ON leads.id = projects.lead_id ORDER BY projects.created_at DESC');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT projects.*, leads.company_name, leads.industry, leads.website, leads.country, leads.city, leads.state_province, leads.company_size FROM projects JOIN leads ON leads.id = projects.lead_id WHERE projects.id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE projects SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function updateSpec(int $id, array $data): void
    {
        $stmt = $this->db->prepare('UPDATE projects SET spec_summary = :spec_summary, tech_notes = :tech_notes, github_repo_url = :github_repo_url, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'spec_summary' => $data['spec_summary'] ?? '',
            'tech_notes' => $data['tech_notes'] ?? '',
            'github_repo_url' => $data['github_repo_url'] ?? null,
            'id' => $id,
        ]);
    }
}
