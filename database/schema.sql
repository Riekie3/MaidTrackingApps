-- MaidTrack database schema
-- Target: MySQL 5.7+ / MariaDB (XAMPP default, standard cPanel hosting)
-- Charset: utf8mb4 throughout for proper name/address support.
--
-- Read alongside the platform proposal, section 09 "Database shape",
-- for the reasoning behind each table.

CREATE DATABASE IF NOT EXISTS maidtrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE maidtrack;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Master data (admin-managed lists — §04 "Master data")
-- ---------------------------------------------------------------------

CREATE TABLE countries (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    iso_code      CHAR(2)      NOT NULL,
    UNIQUE KEY uq_countries_iso (iso_code)
) ENGINE=InnoDB;

CREATE TABLE skills (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    category      VARCHAR(50)  NULL,
    UNIQUE KEY uq_skills_name (name)
) ENGINE=InnoDB;

CREATE TABLE languages (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(50) NOT NULL,
    UNIQUE KEY uq_languages_name (name)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Accounts — three separate login tables, one shared session pattern
-- ---------------------------------------------------------------------

CREATE TABLE admins (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(150)    NOT NULL,
    email         VARCHAR(190)    NOT NULL,
    password_hash VARCHAR(255)    NOT NULL,
    is_active     TINYINT(1)      NOT NULL DEFAULT 1,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_admins_email (email)
) ENGINE=InnoDB;

CREATE TABLE agencies (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name          VARCHAR(190)    NOT NULL,
    registration_number   VARCHAR(100)    NOT NULL,        -- SSM / business license no.
    license_document_path VARCHAR(255)    NULL,
    contact_person         VARCHAR(150)   NOT NULL,
    email                 VARCHAR(190)    NOT NULL,
    phone                 VARCHAR(30)     NOT NULL,
    password_hash         VARCHAR(255)    NOT NULL,
    address               VARCHAR(255)    NULL,
    bio                   TEXT            NULL,             -- shown on public agency profile
    logo_path             VARCHAR(255)    NULL,
    approval_status       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by           INT UNSIGNED    NULL,              -- admins.id
    reviewed_at           DATETIME        NULL,
    rejection_reason      VARCHAR(255)    NULL,
    created_at            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_agencies_email (email),
    KEY idx_agencies_status (approval_status),
    CONSTRAINT fk_agencies_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE clients (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(150)    NOT NULL,
    email               VARCHAR(190)    NOT NULL,
    phone               VARCHAR(30)     NOT NULL,
    password_hash       VARCHAR(255)    NOT NULL,
    address              VARCHAR(255)   NULL,
    email_verified_at   DATETIME        NULL,
    phone_verified_at   DATETIME        NULL,
    status              ENUM('pending_verification','verified','suspended') NOT NULL DEFAULT 'pending_verification',
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_clients_email (email),
    KEY idx_clients_status (status)
) ENGINE=InnoDB;

CREATE TABLE client_otps (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id     INT UNSIGNED    NOT NULL,
    channel       ENUM('email','phone') NOT NULL,
    code_hash     VARCHAR(255)    NOT NULL,
    expires_at    DATETIME        NOT NULL,
    consumed_at   DATETIME        NULL,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_client_otps_client (client_id),
    CONSTRAINT fk_client_otps_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Housemaid record — §03 "The housemaid record"
-- ---------------------------------------------------------------------

CREATE TABLE housemaids (
    id                       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agency_id                INT UNSIGNED NOT NULL,

    -- Identity
    full_name                VARCHAR(150) NOT NULL,
    photo_path               VARCHAR(255) NULL,
    date_of_birth             DATE        NULL,
    gender                   ENUM('female','male') NOT NULL DEFAULT 'female',
    nationality_country_id   INT UNSIGNED NULL,
    marital_status           ENUM('single','married','divorced','widowed') NULL,
    religion                 VARCHAR(50)  NULL,

    -- Documents (sensitive — masked in the app layer outside agency/admin/booked-client view)
    passport_number          VARCHAR(50)  NULL,
    passport_expiry          DATE         NULL,
    work_permit_number       VARCHAR(50)  NULL,
    work_permit_expiry       DATE         NULL,
    national_id_number       VARCHAR(50)  NULL,

    -- Addresses
    home_address              TEXT        NULL,             -- country of origin
    emergency_contact_name   VARCHAR(150) NULL,
    emergency_contact_phone  VARCHAR(30)  NULL,
    current_staying_address  TEXT         NULL,              -- agency dorm or with employer

    -- Experience
    years_experience         TINYINT UNSIGNED NULL,

    -- Trust & status
    approval_status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    availability_status      ENUM('available','placed','on_leave','blacklisted') NOT NULL DEFAULT 'available',
    avg_rating                DECIMAL(3,2) NULL,
    ratings_count             INT UNSIGNED NOT NULL DEFAULT 0,

    -- PDPA consent (captured by the agency on her behalf at submission)
    consent_given_at         DATETIME     NULL,

    submitted_at              DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by                INT UNSIGNED NULL,             -- admins.id
    reviewed_at                DATETIME    NULL,
    rejection_reason           VARCHAR(255) NULL,

    created_at                DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_housemaids_agency (agency_id),
    KEY idx_housemaids_approval (approval_status),
    KEY idx_housemaids_availability (availability_status),
    KEY idx_housemaids_nationality (nationality_country_id),
    KEY idx_housemaids_rating (avg_rating),
    CONSTRAINT fk_housemaids_agency FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE,
    CONSTRAINT fk_housemaids_nationality FOREIGN KEY (nationality_country_id) REFERENCES countries(id) ON DELETE SET NULL,
    CONSTRAINT fk_housemaids_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Scans/certificates — kept as rows, not columns, so any document type
-- (passport, work permit, police clearance, medical report, certification)
-- can be added without a schema change.
CREATE TABLE housemaid_documents (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    housemaid_id  INT UNSIGNED NOT NULL,
    doc_type      ENUM('passport','work_permit','national_id','police_clearance','medical_report','certification','other') NOT NULL,
    title         VARCHAR(150) NULL,               -- e.g. certification name
    file_path     VARCHAR(255) NOT NULL,
    issued_date   DATE         NULL,
    expiry_date   DATE         NULL,
    uploaded_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_housemaid_documents_housemaid (housemaid_id),
    KEY idx_housemaid_documents_expiry (expiry_date),
    CONSTRAINT fk_housemaid_documents_housemaid FOREIGN KEY (housemaid_id) REFERENCES housemaids(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE housemaid_skills (
    housemaid_id  INT UNSIGNED NOT NULL,
    skill_id      INT UNSIGNED NOT NULL,
    PRIMARY KEY (housemaid_id, skill_id),
    CONSTRAINT fk_hs_housemaid FOREIGN KEY (housemaid_id) REFERENCES housemaids(id) ON DELETE CASCADE,
    CONSTRAINT fk_hs_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE housemaid_languages (
    housemaid_id  INT UNSIGNED NOT NULL,
    language_id   INT UNSIGNED NOT NULL,
    PRIMARY KEY (housemaid_id, language_id),
    CONSTRAINT fk_hl_housemaid FOREIGN KEY (housemaid_id) REFERENCES housemaids(id) ON DELETE CASCADE,
    CONSTRAINT fk_hl_language FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE housemaid_work_countries (
    housemaid_id  INT UNSIGNED NOT NULL,
    country_id    INT UNSIGNED NOT NULL,
    PRIMARY KEY (housemaid_id, country_id),
    CONSTRAINT fk_hwc_housemaid FOREIGN KEY (housemaid_id) REFERENCES housemaids(id) ON DELETE CASCADE,
    CONSTRAINT fk_hwc_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE employment_history (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    housemaid_id        INT UNSIGNED NOT NULL,
    employer_client_id  INT UNSIGNED NULL,          -- set when the employer is a MaidTrack client
    employer_name       VARCHAR(150) NULL,          -- free text when the placement predates the platform
    agency_id_at_time   INT UNSIGNED NULL,
    start_date           DATE        NULL,
    end_date              DATE       NULL,
    reason_for_leaving    VARCHAR(255) NULL,
    created_at            DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_employment_history_housemaid (housemaid_id),
    CONSTRAINT fk_eh_housemaid FOREIGN KEY (housemaid_id) REFERENCES housemaids(id) ON DELETE CASCADE,
    CONSTRAINT fk_eh_client FOREIGN KEY (employer_client_id) REFERENCES clients(id) ON DELETE SET NULL,
    CONSTRAINT fk_eh_agency FOREIGN KEY (agency_id_at_time) REFERENCES agencies(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Marketplace — bookings, reviews, incidents
-- ---------------------------------------------------------------------

CREATE TABLE bookings (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id           INT UNSIGNED NOT NULL,
    housemaid_id        INT UNSIGNED NOT NULL,
    agency_id           INT UNSIGNED NOT NULL,
    status              ENUM('requested','accepted','declined','active','completed','cancelled') NOT NULL DEFAULT 'requested',
    start_date           DATE        NULL,
    end_date              DATE       NULL,
    contract_reference    VARCHAR(100) NULL,
    created_at            DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_bookings_client (client_id),
    KEY idx_bookings_housemaid (housemaid_id),
    KEY idx_bookings_agency (agency_id),
    KEY idx_bookings_status (status),
    CONSTRAINT fk_bookings_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_housemaid FOREIGN KEY (housemaid_id) REFERENCES housemaids(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_agency FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reviews (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id            INT UNSIGNED NOT NULL,
    client_id             INT UNSIGNED NOT NULL,
    housemaid_id          INT UNSIGNED NOT NULL,
    rating_reliability     TINYINT UNSIGNED NOT NULL,   -- 1-5
    rating_skill            TINYINT UNSIGNED NOT NULL,
    rating_hygiene           TINYINT UNSIGNED NOT NULL,
    rating_communication      TINYINT UNSIGNED NOT NULL,
    comment                TEXT NULL,
    agency_response         TEXT NULL,
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reviews_booking (booking_id),          -- one review per completed booking
    KEY idx_reviews_housemaid (housemaid_id),
    CONSTRAINT fk_reviews_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_housemaid FOREIGN KEY (housemaid_id) REFERENCES housemaids(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE incidents (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    housemaid_id      INT UNSIGNED NOT NULL,
    reported_by_type  ENUM('agency','client','admin') NOT NULL,
    reported_by_id    INT UNSIGNED NOT NULL,
    incident_type     ENUM('ran_away','theft','abuse_allegation','contract_breach','other') NOT NULL,
    description        TEXT NOT NULL,
    evidence_path      VARCHAR(255) NULL,
    status             ENUM('reported','under_review','verified','dismissed') NOT NULL DEFAULT 'reported',
    reviewed_by         INT UNSIGNED NULL,          -- admins.id
    reviewed_at          DATETIME NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_incidents_housemaid (housemaid_id),
    KEY idx_incidents_status (status),
    CONSTRAINT fk_incidents_housemaid FOREIGN KEY (housemaid_id) REFERENCES housemaids(id) ON DELETE CASCADE,
    CONSTRAINT fk_incidents_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Audit log — every state-changing action, including bulk approvals
-- ---------------------------------------------------------------------

CREATE TABLE audit_log (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_type    ENUM('admin','agency','client','system') NOT NULL,
    actor_id      INT UNSIGNED NULL,
    action        VARCHAR(100) NOT NULL,        -- e.g. 'housemaid.approve', 'agency.reject'
    entity_type   VARCHAR(50)  NOT NULL,        -- e.g. 'housemaid', 'agency', 'incident'
    entity_id     INT UNSIGNED NULL,
    meta          JSON         NULL,            -- e.g. bulk batch id, rejection reason
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_log_entity (entity_type, entity_id),
    KEY idx_audit_log_actor (actor_type, actor_id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
