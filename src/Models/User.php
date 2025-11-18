<?php

namespace App\Models;

class User extends Model
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getAssignableDevs(): array
    {
        $stmt = $this->db->query("SELECT id, name, role FROM users WHERE role IN ('dev','admin') ORDER BY name");
        return $stmt->fetchAll();
    }
}
