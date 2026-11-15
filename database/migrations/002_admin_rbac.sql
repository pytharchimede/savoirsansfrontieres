CREATE TABLE IF NOT EXISTS admin_permissions (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(80) NOT NULL UNIQUE,
 label VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS admin_groups (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL UNIQUE,
 description VARCHAR(255) NOT NULL DEFAULT '',
 is_system TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS admin_group_permissions (
 group_id INT UNSIGNED NOT NULL,
 permission_id INT UNSIGNED NOT NULL,
 PRIMARY KEY(group_id,permission_id),
 FOREIGN KEY(group_id) REFERENCES admin_groups(id) ON DELETE CASCADE,
 FOREIGN KEY(permission_id) REFERENCES admin_permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS admin_users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(80) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 last_login_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS admin_user_groups (
 user_id INT UNSIGNED NOT NULL,
 group_id INT UNSIGNED NOT NULL,
 PRIMARY KEY(user_id,group_id),
 FOREIGN KEY(user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
 FOREIGN KEY(group_id) REFERENCES admin_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS admin_login_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(80) NOT NULL,
 ip_hash CHAR(64) NOT NULL,
 attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_attempts(username,attempted_at),
 INDEX idx_attempts_ip(ip_hash,attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO admin_permissions(code,label) VALUES
('registrations.view','Consulter les inscriptions'),
('registrations.export','Exporter les inscriptions'),
('users.manage','Gérer les utilisateurs'),
('groups.manage','Gérer les groupes et permissions');
INSERT IGNORE INTO admin_groups(name,description,is_system) VALUES
('Administrateurs','Accès complet',1),
('Gestionnaires','Consulter et exporter les inscriptions',1),
('Lecteurs','Consulter les inscriptions uniquement',1);
INSERT IGNORE INTO admin_group_permissions(group_id,permission_id)
SELECT g.id,p.id FROM admin_groups g CROSS JOIN admin_permissions p
WHERE g.name='Administrateurs';
INSERT IGNORE INTO admin_group_permissions(group_id,permission_id)
SELECT g.id,p.id FROM admin_groups g CROSS JOIN admin_permissions p
WHERE g.name='Gestionnaires' AND p.code IN ('registrations.view','registrations.export');
INSERT IGNORE INTO admin_group_permissions(group_id,permission_id)
SELECT g.id,p.id FROM admin_groups g CROSS JOIN admin_permissions p
WHERE g.name='Lecteurs' AND p.code='registrations.view';
