CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    project_name VARCHAR(200) NOT NULL,
    app_type VARCHAR(120) DEFAULT NULL,
    status ENUM('backlog','in_progress','qa','done') DEFAULT 'backlog',
    spec_summary MEDIUMTEXT NULL,
    tech_notes MEDIUMTEXT NULL,
    github_repo_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_project_lead FOREIGN KEY (lead_id) REFERENCES leads(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
