-- Dodanie kolumn do tabeli users dla resetowania hasła
ALTER TABLE users
ADD COLUMN reset_token VARCHAR(64) NULL,
ADD COLUMN reset_token_expires DATETIME NULL; 