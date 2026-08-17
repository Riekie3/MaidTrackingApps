-- Phase 4 (security hardening) schema additions.
-- Run after migrate_phase2.sql.

USE maidtrack;

-- PDPA consent capture for clients (housemaid intake already had this
-- from Phase 1 — clients never got the equivalent checkbox).
ALTER TABLE clients ADD COLUMN consent_given_at DATETIME NULL AFTER address;

-- Login rate-limiting: a row per failed attempt, keyed by the email that
-- was tried. Old rows just age out of the lockout window — no cleanup
-- job needed at this scale.
CREATE TABLE login_attempts (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier   VARCHAR(190) NOT NULL,
    ip_address   VARCHAR(45)  NULL,
    attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_login_attempts_identifier (identifier, attempted_at)
) ENGINE=InnoDB;

-- Passport/national ID are now encrypted at rest (AES-256-GCM, see
-- config/secrets.php + encrypt_field()/decrypt_field() in functions.php).
-- The stored value is base64(iv . tag . ciphertext), which runs longer
-- than the original plaintext — VARCHAR(50) isn't enough room.
ALTER TABLE housemaids MODIFY passport_number VARCHAR(255) NULL;
ALTER TABLE housemaids MODIFY national_id_number VARCHAR(255) NULL;
