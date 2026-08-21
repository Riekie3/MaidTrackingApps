-- Phase 7 starter master data — services + locations, per Proposal §10.
-- Run after migrate_phase7.sql.

USE maidtrack;

INSERT INTO services (name) VALUES
    ('Full-Day Housekeeping'),
    ('Part-Time Cleaning (Hourly)'),
    ('Deep Cleaning'),
    ('Cooking Service'),
    ('Babysitting / Childcare'),
    ('Elderly Care'),
    ('Pet Care'),
    ('Laundry & Ironing'),
    ('Move-In / Move-Out Cleaning'),
    ('Event Cleaning (One-Off)')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO locations (name, state) VALUES
    -- Federal Territories
    ('Kuala Lumpur', 'Federal Territory'),
    ('Putrajaya', 'Federal Territory'),
    ('Labuan', 'Federal Territory'),
    -- Selangor
    ('Shah Alam', 'Selangor'),
    ('Petaling Jaya', 'Selangor'),
    ('Subang Jaya', 'Selangor'),
    ('Klang', 'Selangor'),
    ('Kajang', 'Selangor'),
    ('Ampang', 'Selangor'),
    ('Cyberjaya', 'Selangor'),
    ('Puchong', 'Selangor'),
    ('Rawang', 'Selangor'),
    ('Semenyih', 'Selangor'),
    ('Sepang', 'Selangor'),
    ('Selayang', 'Selangor'),
    ('Banting', 'Selangor'),
    ('Kuala Selangor', 'Selangor'),
    -- Johor
    ('Johor Bahru', 'Johor'),
    ('Batu Pahat', 'Johor'),
    ('Muar', 'Johor'),
    ('Kluang', 'Johor'),
    ('Segamat', 'Johor'),
    ('Pontian', 'Johor'),
    ('Kulai', 'Johor'),
    ('Pasir Gudang', 'Johor'),
    ('Kota Tinggi', 'Johor'),
    ('Mersing', 'Johor'),
    -- Penang
    ('George Town', 'Penang'),
    ('Bukit Mertajam', 'Penang'),
    ('Butterworth', 'Penang'),
    ('Bayan Lepas', 'Penang'),
    ('Nibong Tebal', 'Penang'),
    -- Perak
    ('Ipoh', 'Perak'),
    ('Taiping', 'Perak'),
    ('Teluk Intan', 'Perak'),
    ('Sitiawan', 'Perak'),
    ('Batu Gajah', 'Perak'),
    ('Kampar', 'Perak'),
    ('Lumut', 'Perak'),
    -- Negeri Sembilan
    ('Seremban', 'Negeri Sembilan'),
    ('Port Dickson', 'Negeri Sembilan'),
    ('Nilai', 'Negeri Sembilan'),
    ('Bahau', 'Negeri Sembilan'),
    ('Kuala Pilah', 'Negeri Sembilan'),
    -- Melaka
    ('Melaka City', 'Melaka'),
    ('Alor Gajah', 'Melaka'),
    ('Jasin', 'Melaka'),
    -- Kedah
    ('Alor Setar', 'Kedah'),
    ('Sungai Petani', 'Kedah'),
    ('Kulim', 'Kedah'),
    ('Jitra', 'Kedah'),
    ('Langkawi', 'Kedah'),
    -- Pahang
    ('Kuantan', 'Pahang'),
    ('Temerloh', 'Pahang'),
    ('Bentong', 'Pahang'),
    ('Raub', 'Pahang'),
    ('Cameron Highlands', 'Pahang'),
    ('Jerantut', 'Pahang'),
    -- Terengganu
    ('Kuala Terengganu', 'Terengganu'),
    ('Kemaman', 'Terengganu'),
    ('Dungun', 'Terengganu'),
    -- Kelantan
    ('Kota Bharu', 'Kelantan'),
    ('Pasir Mas', 'Kelantan'),
    ('Tanah Merah', 'Kelantan'),
    -- Perlis
    ('Kangar', 'Perlis'),
    ('Arau', 'Perlis'),
    -- Sabah
    ('Kota Kinabalu', 'Sabah'),
    ('Sandakan', 'Sabah'),
    ('Tawau', 'Sabah'),
    ('Lahad Datu', 'Sabah'),
    ('Keningau', 'Sabah'),
    -- Sarawak
    ('Kuching', 'Sarawak'),
    ('Miri', 'Sarawak'),
    ('Sibu', 'Sarawak'),
    ('Bintulu', 'Sarawak'),
    ('Limbang', 'Sarawak')
ON DUPLICATE KEY UPDATE state = VALUES(state);
