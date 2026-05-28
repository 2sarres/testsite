-- Table pour les invitations d'admin
CREATE TABLE IF NOT EXISTS admin_invitations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    used BOOLEAN DEFAULT FALSE,
    used_at TIMESTAMP NULL,
    created_by INT NOT NULL,
    email_recipient VARCHAR(255),
    FOREIGN KEY (created_by) REFERENCES admins(id)
);

-- Ajout de colonne is_active à la table admins si elle n'existe pas
ALTER TABLE admins ADD COLUMN is_active BOOLEAN DEFAULT TRUE;

-- Table d'audit pour les suppressions et actions
CREATE TABLE IF NOT EXISTS admin_audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    action VARCHAR(50),
    admin_id INT,
    target_admin_id INT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (admin_id) REFERENCES admins(id),
    FOREIGN KEY (target_admin_id) REFERENCES admins(id)
);

-- Index pour les recherches rapides
CREATE INDEX idx_token ON admin_invitations(token);
CREATE INDEX idx_created_by ON admin_invitations(created_by);
CREATE INDEX idx_admin_active ON admins(is_active);
CREATE INDEX idx_audit_timestamp ON admin_audit_log(timestamp);
