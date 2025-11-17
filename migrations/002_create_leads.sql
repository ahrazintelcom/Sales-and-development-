CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(180) NOT NULL,
    website VARCHAR(255) DEFAULT NULL,
    industry VARCHAR(120) DEFAULT NULL,
    description TEXT NULL,
    country VARCHAR(80) DEFAULT NULL,
    state_province VARCHAR(120) DEFAULT NULL,
    city VARCHAR(120) DEFAULT NULL,
    company_size VARCHAR(50) DEFAULT NULL,
    contact_name VARCHAR(120) DEFAULT NULL,
    contact_email VARCHAR(160) DEFAULT NULL,
    lead_score INT DEFAULT 0,
    status ENUM('new','contacted','follow_up','locked_in','lost') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
