<?php

namespace App\Models;

class ProjectTask extends Model
{
    public function getByProject(int $projectId): array
    {
        $stmt = $this->db->prepare('SELECT project_tasks.*, users.name AS assigned_name FROM project_tasks LEFT JOIN users ON users.id = project_tasks.assigned_to_user_id WHERE project_id = :project_id ORDER BY project_tasks.created_at ASC');
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO project_tasks (project_id, title, description, status, assigned_to_user_id, created_at, updated_at) VALUES (:project_id, :title, :description, :status, :assigned_to_user_id, NOW(), NOW())');
        $stmt->execute([
            'project_id' => $data['project_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'status' => $data['status'] ?? 'todo',
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateStatus(int $taskId, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE project_tasks SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $taskId]);
    }
}
