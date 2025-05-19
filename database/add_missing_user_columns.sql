-- Add missing columns to users table
ALTER TABLE users
ADD COLUMN address VARCHAR(255) NULL,
ADD COLUMN city VARCHAR(100) NULL,
ADD COLUMN postal_code VARCHAR(20) NULL;
