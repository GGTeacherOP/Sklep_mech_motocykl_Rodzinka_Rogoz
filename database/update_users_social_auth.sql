-- Dodanie kolumn dla obsługi uwierzytelniania przez Google i Apple
ALTER TABLE users 
ADD COLUMN google_id VARCHAR(255) NULL AFTER password,
ADD COLUMN apple_id VARCHAR(255) NULL AFTER google_id;

-- Dodanie indeksów dla szybszego wyszukiwania
ALTER TABLE users 
ADD INDEX idx_google_id (google_id),
ADD INDEX idx_apple_id (apple_id);
