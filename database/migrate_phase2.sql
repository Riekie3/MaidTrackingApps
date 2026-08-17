-- Phase 2 schema addition: client-facing booking requests carry a short
-- note (dates + context) that wasn't scoped into the original bookings
-- table. Run this once, after schema.sql, before using the client portal.

USE maidtrack;

ALTER TABLE bookings ADD COLUMN notes TEXT NULL AFTER contract_reference;
