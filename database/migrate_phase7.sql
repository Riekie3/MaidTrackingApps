-- Phase 7: Freelancer Marketplace
-- Run after migrate_phase4.sql, against a database that already has the
-- Phase 0-4 schema in place.
--
-- Adds a fourth login (freelancers) who represent themselves rather than
-- an agency, an Admin-managed services + locations catalog, and switches
-- bookings/reviews/incidents from a single housemaid_id to a polymorphic
-- provider_type + provider_id pair so either table can be referenced.
-- See the Platform Proposal §10 for the reasoning and the trade-off
-- against a simpler two-nullable-column approach.

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- New master data
-- ---------------------------------------------------------------------

CREATE TABLE services (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    UNIQUE KEY uq_services_name (name)
) ENGINE=InnoDB;

CREATE TABLE locations (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    state         VARCHAR(100) NOT NULL,
    UNIQUE KEY uq_locations_name (name)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Freelancer account + profile (own login, no agency_id — the account
-- IS the bookable profile, unlike a housemaid record an agency manages)
-- ---------------------------------------------------------------------

CREATE TABLE freelancers (
    id                       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Login
    email                    VARCHAR(190) NOT NULL,
    phone                    VARCHAR(30)  NOT NULL,
    password_hash            VARCHAR(255) NOT NULL,
    email_verified_at        DATETIME     NULL,
    phone_verified_at        DATETIME     NULL,

    -- Identity — same shape as housemaids
    full_name                VARCHAR(150) NOT NULL,
    photo_path               VARCHAR(255) NULL,
    date_of_birth            DATE         NULL,
    gender                   ENUM('female','male') NOT NULL DEFAULT 'female',
    nationality_country_id   INT UNSIGNED NULL,
    marital_status           ENUM('single','married','divorced','widowed') NULL,
    religion                 VARCHAR(50)  NULL,

    -- Documents (sensitive — same masking/encryption treatment as housemaids)
    passport_number          VARCHAR(50)  NULL,
    passport_expiry          DATE         NULL,
    work_permit_number       VARCHAR(50)  NULL,
    work_permit_expiry       DATE         NULL,
    national_id_number       VARCHAR(50)  NULL,

    -- Addresses
    home_address             TEXT         NULL,
    emergency_contact_name   VARCHAR(150) NULL,
    emergency_contact_phone  VARCHAR(30)  NULL,
    current_staying_address  TEXT         NULL,

    years_experience         TINYINT UNSIGNED NULL,

    -- Banking (restricted — same encryption tier as passport/ID, arguably
    -- more sensitive, not less)
    bank_name                VARCHAR(150) NULL,
    bank_account_holder      VARCHAR(150) NULL,
    bank_account_number      VARCHAR(100) NULL,

    -- Trust & status
    approval_status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    availability_status      ENUM('available','on_leave','blacklisted') NOT NULL DEFAULT 'available',
    avg_rating                DECIMAL(3,2) NULL,
    ratings_count             INT UNSIGNED NOT NULL DEFAULT 0,

    consent_given_at         DATETIME     NULL,

    submitted_at             DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by               INT UNSIGNED NULL,          -- admins.id
    reviewed_at                DATETIME    NULL,
    rejection_reason           VARCHAR(255) NULL,

    created_at                DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_freelancers_email (email),
    KEY idx_freelancers_approval (approval_status),
    KEY idx_freelancers_availability (availability_status),
    KEY idx_freelancers_nationality (nationality_country_id),
    KEY idx_freelancers_rating (avg_rating),
    CONSTRAINT fk_freelancers_nationality FOREIGN KEY (nationality_country_id) REFERENCES countries(id) ON DELETE SET NULL,
    CONSTRAINT fk_freelancers_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Note: availability_status here has no 'placed' state, unlike housemaids.
-- A freelancer takes date-ranged bookings, potentially several across
-- different dates — a single "placed" flag doesn't make sense the way it
-- does for a housemaid's one continuous live-in employment relationship.
-- Accepting a booking does NOT flip this field (see Booking model);
-- 'on_leave'/'blacklisted' stay manual, Admin/self-set only.

CREATE TABLE freelancer_documents (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    freelancer_id  INT UNSIGNED NOT NULL,
    doc_type       ENUM('passport','work_permit','national_id','police_clearance','medical_report','certification','other') NOT NULL,
    title          VARCHAR(150) NULL,
    file_path      VARCHAR(255) NOT NULL,
    issued_date    DATE         NULL,
    expiry_date    DATE         NULL,
    uploaded_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_freelancer_documents_freelancer (freelancer_id),
    KEY idx_freelancer_documents_expiry (expiry_date),
    CONSTRAINT fk_freelancer_documents_freelancer FOREIGN KEY (freelancer_id) REFERENCES freelancers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE freelancer_services (
    freelancer_id  INT UNSIGNED NOT NULL,
    service_id     INT UNSIGNED NOT NULL,
    price          DECIMAL(8,2) NOT NULL,
    price_unit     ENUM('hourly','daily','per_job') NOT NULL DEFAULT 'daily',
    PRIMARY KEY (freelancer_id, service_id),
    CONSTRAINT fk_fs_freelancer FOREIGN KEY (freelancer_id) REFERENCES freelancers(id) ON DELETE CASCADE,
    CONSTRAINT fk_fs_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE freelancer_locations (
    freelancer_id  INT UNSIGNED NOT NULL,
    location_id    INT UNSIGNED NOT NULL,
    PRIMARY KEY (freelancer_id, location_id),
    CONSTRAINT fk_fl_freelancer FOREIGN KEY (freelancer_id) REFERENCES freelancers(id) ON DELETE CASCADE,
    CONSTRAINT fk_fl_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Mirrors client_otps exactly — a freelancer verifies her own email+phone
-- (like a client does) in addition to needing Admin approval (like an
-- agency does), since there's no agency vouching for her identity first.
CREATE TABLE freelancer_otps (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    freelancer_id  INT UNSIGNED NOT NULL,
    channel        ENUM('email','phone') NOT NULL,
    code_hash      VARCHAR(255) NOT NULL,
    expires_at     DATETIME     NOT NULL,
    consumed_at    DATETIME     NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_freelancer_otps_freelancer (freelancer_id),
    CONSTRAINT fk_freelancer_otps_freelancer FOREIGN KEY (freelancer_id) REFERENCES freelancers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Polymorphic provider reference on bookings / reviews / incidents
-- ---------------------------------------------------------------------

ALTER TABLE bookings DROP FOREIGN KEY fk_bookings_housemaid;
ALTER TABLE bookings DROP FOREIGN KEY fk_bookings_agency;
ALTER TABLE bookings
    ADD COLUMN provider_type ENUM('housemaid','freelancer') NOT NULL DEFAULT 'housemaid' AFTER client_id;
ALTER TABLE bookings CHANGE COLUMN housemaid_id provider_id INT UNSIGNED NOT NULL;
ALTER TABLE bookings MODIFY COLUMN agency_id INT UNSIGNED NULL;
ALTER TABLE bookings ADD COLUMN service_id INT UNSIGNED NULL AFTER provider_id;
ALTER TABLE bookings
    ADD KEY idx_bookings_provider (provider_type, provider_id),
    ADD CONSTRAINT fk_bookings_agency FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_bookings_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL;
-- No native FK on (provider_type, provider_id) — MySQL can't express a
-- conditional FK to one of two tables. Enforced at the application layer
-- (Booking model), same trade-off named in the Proposal §10.
DROP INDEX idx_bookings_housemaid ON bookings;

ALTER TABLE reviews DROP FOREIGN KEY fk_reviews_housemaid;
ALTER TABLE reviews
    ADD COLUMN provider_type ENUM('housemaid','freelancer') NOT NULL DEFAULT 'housemaid' AFTER client_id;
ALTER TABLE reviews CHANGE COLUMN housemaid_id provider_id INT UNSIGNED NOT NULL;
ALTER TABLE reviews ADD KEY idx_reviews_provider (provider_type, provider_id);
DROP INDEX idx_reviews_housemaid ON reviews;

ALTER TABLE incidents DROP FOREIGN KEY fk_incidents_housemaid;
ALTER TABLE incidents
    ADD COLUMN provider_type ENUM('housemaid','freelancer') NOT NULL DEFAULT 'housemaid' AFTER id;
ALTER TABLE incidents CHANGE COLUMN housemaid_id provider_id INT UNSIGNED NOT NULL;
ALTER TABLE incidents ADD KEY idx_incidents_provider (provider_type, provider_id);
DROP INDEX idx_incidents_housemaid ON incidents;

SET FOREIGN_KEY_CHECKS = 1;
