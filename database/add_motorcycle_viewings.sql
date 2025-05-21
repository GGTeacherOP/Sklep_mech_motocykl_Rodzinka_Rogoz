-- Tabela rezerwacji oględzin motocykli używanych
CREATE TABLE IF NOT EXISTS motorcycle_viewings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    motorcycle_id INT NOT NULL,
    user_id INT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    message TEXT,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (motorcycle_id) REFERENCES used_motorcycles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Dodanie brakujących kolumn do tabeli used_motorcycles
ALTER TABLE used_motorcycles
ADD COLUMN IF NOT EXISTS power INT AFTER engine_capacity,
ADD COLUMN IF NOT EXISTS color VARCHAR(50) AFTER power,
ADD COLUMN IF NOT EXISTS features TEXT AFTER description,
ADD COLUMN IF NOT EXISTS registration_number VARCHAR(20) AFTER features,
ADD COLUMN IF NOT EXISTS vin VARCHAR(50) AFTER registration_number,
MODIFY COLUMN status ENUM('available', 'reserved', 'sold', 'hidden') DEFAULT 'available';
