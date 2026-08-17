-- Starter master data — admin can add/edit more later via Master Data (§04).
-- Countries lean toward Malaysia's common FDW source countries first.

USE maidtrack;

INSERT INTO countries (name, iso_code) VALUES
    ('Malaysia', 'MY'),
    ('Indonesia', 'ID'),
    ('Philippines', 'PH'),
    ('Myanmar', 'MM'),
    ('Cambodia', 'KH'),
    ('Sri Lanka', 'LK'),
    ('India', 'IN'),
    ('Nepal', 'NP'),
    ('Vietnam', 'VN'),
    ('Thailand', 'TH'),
    ('Bangladesh', 'BD')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO skills (name, category) VALUES
    ('General Housekeeping', 'household'),
    ('Deep Cleaning', 'household'),
    ('Cooking - Local Malaysian', 'cooking'),
    ('Cooking - Chinese', 'cooking'),
    ('Cooking - Indian', 'cooking'),
    ('Cooking - Western', 'cooking'),
    ('Childcare - Infant', 'care'),
    ('Childcare - School-age', 'care'),
    ('Elderly Care', 'care'),
    ('Patient / Nursing Care', 'care'),
    ('Pet Care', 'care'),
    ('Driving', 'other'),
    ('Ironing', 'household'),
    ('Laundry', 'household')
ON DUPLICATE KEY UPDATE category = VALUES(category);

INSERT INTO languages (name) VALUES
    ('Bahasa Malaysia'),
    ('English'),
    ('Mandarin'),
    ('Tamil'),
    ('Bahasa Indonesia'),
    ('Tagalog'),
    ('Burmese'),
    ('Khmer'),
    ('Sinhala'),
    ('Nepali')
ON DUPLICATE KEY UPDATE name = VALUES(name);
