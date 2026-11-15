CREATE TABLE IF NOT EXISTS subjects (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(30) NOT NULL UNIQUE,
 name VARCHAR(100) NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO subjects(code,name,active,sort_order) VALUES
('mathematiques','Mathématiques',1,10),
('physique','Physique',1,20);
INSERT IGNORE INTO admin_permissions(code,label) VALUES
('subjects.manage','Gérer les matières');
INSERT IGNORE INTO admin_group_permissions(group_id,permission_id)
SELECT g.id,p.id FROM admin_groups g CROSS JOIN admin_permissions p
WHERE g.name='Administrateurs' AND p.code='subjects.manage';
