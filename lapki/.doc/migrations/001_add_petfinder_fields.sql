-- Migration: Add Petfinder.com compatible fields
-- Date: 2025-10-08
-- Description: Додати відсутні поля для повної сумісності з Petfinder API

-- Animals table: add address fields and URL
ALTER TABLE wp_lapki_animals
ADD COLUMN IF NOT EXISTS address1 VARCHAR(255) NULL COMMENT 'Вулиця, будинок' AFTER contact_phone,
ADD COLUMN IF NOT EXISTS address2 VARCHAR(255) NULL COMMENT 'Додаткова адреса (квартира, під\'їзд)' AFTER address1,
ADD COLUMN IF NOT EXISTS url VARCHAR(500) NULL COMMENT 'Посилання на Petfinder або власний сайт' AFTER updated_at;

-- Organizations table: add mission, adoption policy, social media
ALTER TABLE wp_lapki_organizations
ADD COLUMN IF NOT EXISTS hours TEXT NULL COMMENT 'Графік роботи (JSON або текст)' AFTER website,
ADD COLUMN IF NOT EXISTS mission_statement TEXT NULL COMMENT 'Місія організації' AFTER hours,
ADD COLUMN IF NOT EXISTS adoption_policy TEXT NULL COMMENT 'Політика усиновлення' AFTER mission_statement,
ADD COLUMN IF NOT EXISTS adoption_url VARCHAR(500) NULL COMMENT 'URL для подачі заявки на усиновлення' AFTER adoption_policy,
ADD COLUMN IF NOT EXISTS social_media JSON NULL COMMENT 'Соціальні мережі (facebook, instagram, twitter, youtube)' AFTER adoption_url,
ADD COLUMN IF NOT EXISTS url VARCHAR(500) NULL COMMENT 'Посилання на Petfinder профіль' AFTER updated_at;

-- Verify changes
SELECT 'Animals table updated' AS status,
       COUNT(*) AS new_fields_count
FROM information_schema.COLUMNS
WHERE TABLE_NAME = 'wp_lapki_animals'
AND COLUMN_NAME IN ('address1', 'address2', 'url');

SELECT 'Organizations table updated' AS status,
       COUNT(*) AS new_fields_count
FROM information_schema.COLUMNS
WHERE TABLE_NAME = 'wp_lapki_organizations'
AND COLUMN_NAME IN ('hours', 'mission_statement', 'adoption_policy', 'adoption_url', 'social_media', 'url');
