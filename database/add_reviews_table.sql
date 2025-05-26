-- Skrypt dodający tabelę recenzji produktów
USE motoshop_db;

-- Sprawdzenie czy tabela już istnieje
SET @table_exists = 0;
SELECT COUNT(*) INTO @table_exists FROM information_schema.tables 
WHERE table_schema = 'motoshop_db' AND table_name = 'product_reviews';

-- Tworzenie tabeli tylko jeśli nie istnieje
SET @query = IF(@table_exists = 0,
    'CREATE TABLE product_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT,
        rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
        title VARCHAR(255),
        content TEXT,
        status ENUM("pending", "approved", "rejected") DEFAULT "pending",
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )',
    'SELECT "Tabela product_reviews już istnieje" AS message'
);

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Dodanie indeksów dla lepszej wydajności
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists FROM information_schema.statistics
WHERE table_schema = 'motoshop_db' AND table_name = 'product_reviews' AND index_name = 'idx_product_reviews_product';

SET @index_query = IF(@index_exists = 0,
    'CREATE INDEX idx_product_reviews_product ON product_reviews(product_id)',
    'SELECT "Indeks idx_product_reviews_product już istnieje" AS message'
);

PREPARE stmt FROM @index_query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Dodanie indeksu dla użytkowników
SET @user_index_exists = 0;
SELECT COUNT(*) INTO @user_index_exists FROM information_schema.statistics
WHERE table_schema = 'motoshop_db' AND table_name = 'product_reviews' AND index_name = 'idx_product_reviews_user';

SET @user_index_query = IF(@user_index_exists = 0,
    'CREATE INDEX idx_product_reviews_user ON product_reviews(user_id)',
    'SELECT "Indeks idx_product_reviews_user już istnieje" AS message'
);

PREPARE stmt FROM @user_index_query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Dodanie przykładowych recenzji
INSERT INTO product_reviews (product_id, user_id, rating, title, content, status)
SELECT 
    p.id,
    (SELECT id FROM users ORDER BY RAND() LIMIT 1),
    FLOOR(RAND() * 5) + 1,
    CASE 
        WHEN FLOOR(RAND() * 5) + 1 = 5 THEN 'Doskonały produkt, polecam!'
        WHEN FLOOR(RAND() * 5) + 1 = 4 THEN 'Bardzo dobry stosunek jakości do ceny'
        WHEN FLOOR(RAND() * 5) + 1 = 3 THEN 'Przeciętny produkt, spełnia swoje zadanie'
        WHEN FLOOR(RAND() * 5) + 1 = 2 THEN 'Słaba jakość wykonania'
        ELSE 'Nie polecam, lepiej dopłacić do lepszego modelu'
    END,
    CASE 
        WHEN FLOOR(RAND() * 5) + 1 >= 4 THEN 'Jestem bardzo zadowolony z zakupu. Produkt spełnia wszystkie moje oczekiwania i jest wart swojej ceny. Polecam wszystkim motocyklistom.'
        WHEN FLOOR(RAND() * 5) + 1 = 3 THEN 'Produkt jest OK, ale spodziewałem się czegoś lepszego za tę cenę. Jakość wykonania jest przeciętna, ale spełnia swoje zadanie.'
        ELSE 'Niestety produkt nie spełnił moich oczekiwań. Jakość wykonania pozostawia wiele do życzenia, a cena jest zdecydowanie za wysoka.'
    END,
    'approved'
FROM products p
WHERE p.id <= 10
LIMIT 20;
