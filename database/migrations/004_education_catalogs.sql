CREATE TABLE IF NOT EXISTS education_subjects (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(30) NOT NULL UNIQUE,
 label VARCHAR(120) NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS education_levels (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(100) NOT NULL UNIQUE,
 label VARCHAR(120) NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO education_subjects(code,label,sort_order) VALUES ('mathematiques','Mathématiques',10),('physique','Physique',20);
INSERT IGNORE INTO education_levels(code,label,sort_order) VALUES ('6e','6e',10),('5e','5e',20),('4e','4e',30),('3e','3e',40),('seconde','Seconde',50),('premiere','Première',60),('terminale','Terminale',70),('licence-1','Licence 1',80),('licence-2','Licence 2',90),('licence-3','Licence 3',100),('master-1','Master 1',110),('master-2','Master 2',120);
INSERT IGNORE INTO admin_permissions(code,label) VALUES ('catalogs.manage','Gérer les matières et niveaux');
INSERT IGNORE INTO admin_group_permissions(group_id,permission_id)
SELECT g.id,p.id FROM admin_groups g CROSS JOIN admin_permissions p WHERE g.name='Administrateurs' AND p.code='catalogs.manage';
