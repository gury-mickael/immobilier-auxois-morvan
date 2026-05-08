INSERT INTO cms_admin_users (name, email, password_hash, role, is_active)
VALUES
  ('Mickael Gury', 'mickael.gury@iadfrance.fr', '<hash-bcrypt-mickael>', 'admin', 1),
  ('Marion Roulier', 'marion.roullier@iadfrance.fr', '<hash-bcrypt-marion>', 'admin', 1)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  password_hash = VALUES(password_hash),
  role = VALUES(role),
  is_active = VALUES(is_active);