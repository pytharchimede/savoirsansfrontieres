-- 005: public pricing, managed through the administration dashboard.
CREATE TABLE IF NOT EXISTS site_pricing (
 code VARCHAR(30) NOT NULL PRIMARY KEY,
 amount_cents INT UNSIGNED NOT NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO site_pricing(code,amount_cents) VALUES
 ('hourly',1250),
 ('monthly',10000);
INSERT IGNORE INTO admin_permissions(code,label) VALUES
 ('pricing.manage','Gérer les tarifs publics');
INSERT IGNORE INTO admin_group_permissions(group_id,permission_id)
SELECT g.id,p.id FROM admin_groups g CROSS JOIN admin_permissions p
WHERE g.name='Administrateurs' AND p.code='pricing.manage';
