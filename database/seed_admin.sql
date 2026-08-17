-- Seeds the first Admin account. Change this password after first login —
-- there's no "change password" screen yet (Phase 4 hardening), so for now
-- do it directly in phpMyAdmin: UPDATE admins SET password_hash = ... .
-- Real login values are tracked in CREDENTIALS.md (gitignored, not this file).

USE maidtrack;

INSERT INTO admins (name, email, password_hash, is_active)
VALUES ('Platform Admin', 'admin@maidtrack.local', '$2y$10$Bo.PDTsJeGmEtr2nvKFQWu/48rPFn.jNJx7zZzZ0RBDuV61av89P2', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);
