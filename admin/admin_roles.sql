-- Dodanie kolumny role do tabeli admins (jeśli nie istnieje)
ALTER TABLE admins ADD COLUMN IF NOT EXISTS role ENUM('admin', 'mechanic', 'owner') NOT NULL DEFAULT 'admin';

-- Aktualizacja istniejących administratorów (opcjonalnie)
-- UPDATE admins SET role = 'admin' WHERE role IS NULL;

-- Indeks dla szybszego wyszukiwania po roli
ALTER TABLE admins ADD INDEX idx_role (role);

-- Przykładowi użytkownicy z różnymi rolami (opcjonalnie - odkomentuj jeśli chcesz dodać testowych użytkowników)
/*
INSERT INTO admins (username, password, name, email, role) VALUES 
('admin', '$2y$10$your_hashed_password', 'Administrator', 'admin@example.com', 'admin'),
('mechanic', '$2y$10$your_hashed_password', 'Jan Kowalski', 'mechanic@example.com', 'mechanic'),
('owner', '$2y$10$your_hashed_password', 'Anna Nowak', 'owner@example.com', 'owner');
*/
