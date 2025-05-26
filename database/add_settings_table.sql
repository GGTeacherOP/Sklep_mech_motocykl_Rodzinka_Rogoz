-- Skrypt dodający tabelę ustawień sklepu
USE motoshop_db;

-- Sprawdzenie czy tabela już istnieje
SET @table_exists = 0;
SELECT COUNT(*) INTO @table_exists FROM information_schema.tables 
WHERE table_schema = 'motoshop_db' AND table_name = 'shop_settings';

-- Tworzenie tabeli tylko jeśli nie istnieje
SET @query = IF(@table_exists = 0,
    'CREATE TABLE shop_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        setting_group VARCHAR(50) NOT NULL DEFAULT "general",
        is_public TINYINT(1) NOT NULL DEFAULT 0,
        description TEXT,
        input_type ENUM("text", "textarea", "number", "email", "select", "checkbox", "color", "file", "date") DEFAULT "text",
        options TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )',
    'SELECT "Tabela shop_settings już istnieje" AS message'
);

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Dodanie indeksu dla lepszej wydajności
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists FROM information_schema.statistics
WHERE table_schema = 'motoshop_db' AND table_name = 'shop_settings' AND index_name = 'idx_setting_group';

SET @index_query = IF(@index_exists = 0,
    'CREATE INDEX idx_setting_group ON shop_settings(setting_group)',
    'SELECT "Indeks idx_setting_group już istnieje" AS message'
);

PREPARE stmt FROM @index_query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Dodanie domyślnych ustawień sklepu (tylko jeśli tabela była właśnie tworzona)
SET @insert_default_settings = IF(@table_exists = 0, 1, 0);

-- Ustawienia ogólne
SET @insert_general_settings = IF(@insert_default_settings = 1,
    'INSERT INTO shop_settings (setting_key, setting_value, setting_group, is_public, description, input_type) VALUES
    ("shop_name", "MotoShop", "general", 1, "Nazwa sklepu", "text"),
    ("shop_email", "kontakt@motoshop.pl", "general", 1, "Główny adres email sklepu", "email"),
    ("shop_phone", "+48 123 456 789", "general", 1, "Główny numer telefonu sklepu", "text"),
    ("shop_address", "ul. Motocyklowa 15, 00-001 Warszawa", "general", 1, "Adres sklepu", "textarea"),
    ("shop_working_hours", "Pon-Pt: 9:00-17:00, Sob: 10:00-14:00", "general", 1, "Godziny otwarcia sklepu", "text"),
    ("shop_description", "Sklep motocyklowy z najlepszymi częściami i akcesoriami", "general", 1, "Krótki opis sklepu", "textarea"),
    ("shop_logo", "assets/images/logo.png", "general", 1, "Logo sklepu", "file"),
    ("shop_favicon", "assets/images/favicon.ico", "general", 1, "Favicon sklepu", "file"),
    ("maintenance_mode", "0", "general", 0, "Tryb konserwacji (1 = włączony, 0 = wyłączony)", "checkbox"),
    ("maintenance_message", "Sklep jest w trakcie konserwacji. Przepraszamy za utrudnienia.", "general", 1, "Komunikat wyświetlany w trybie konserwacji", "textarea")',
    'SELECT "Ustawienia ogólne już istnieją" AS message'
);

PREPARE stmt FROM @insert_general_settings;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ustawienia SMTP i maili
SET @insert_email_settings = IF(@insert_default_settings = 1,
    'INSERT INTO shop_settings (setting_key, setting_value, setting_group, is_public, description, input_type) VALUES
    ("smtp_host", "smtp.example.com", "email", 0, "Host SMTP", "text"),
    ("smtp_port", "587", "email", 0, "Port SMTP", "number"),
    ("smtp_username", "user@example.com", "email", 0, "Nazwa użytkownika SMTP", "text"),
    ("smtp_password", "", "email", 0, "Hasło SMTP", "text"),
    ("smtp_encryption", "tls", "email", 0, "Szyfrowanie SMTP (tls, ssl)", "select"),
    ("email_sender_name", "MotoShop", "email", 0, "Nazwa nadawcy wiadomości email", "text"),
    ("order_notification_email", "zamowienia@motoshop.pl", "email", 0, "Email do powiadomień o zamówieniach", "email"),
    ("contact_form_email", "kontakt@motoshop.pl", "email", 0, "Email do formularza kontaktowego", "email")',
    'SELECT "Ustawienia email już istnieją" AS message'
);

PREPARE stmt FROM @insert_email_settings;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ustawienia SEO
SET @insert_seo_settings = IF(@insert_default_settings = 1,
    'INSERT INTO shop_settings (setting_key, setting_value, setting_group, is_public, description, input_type) VALUES
    ("meta_title", "MotoShop - Sklep motocyklowy", "seo", 1, "Domyślny tytuł strony", "text"),
    ("meta_description", "Najlepszy sklep motocyklowy online. Oferujemy części, akcesoria i odzież motocyklową.", "seo", 1, "Domyślny opis meta", "textarea"),
    ("meta_keywords", "motocykle, części motocyklowe, akcesoria motocyklowe, kaski", "seo", 1, "Domyślne słowa kluczowe", "textarea"),
    ("google_analytics_id", "", "seo", 0, "ID Google Analytics", "text"),
    ("facebook_pixel_id", "", "seo", 0, "ID Facebook Pixel", "text")',
    'SELECT "Ustawienia SEO już istnieją" AS message'
);

PREPARE stmt FROM @insert_seo_settings;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ustawienia sprzedaży
SET @insert_sales_settings = IF(@insert_default_settings = 1,
    'INSERT INTO shop_settings (setting_key, setting_value, setting_group, is_public, description, input_type) VALUES
    ("currency", "PLN", "sales", 1, "Waluta sklepu", "text"),
    ("currency_symbol", "zł", "sales", 1, "Symbol waluty", "text"),
    ("vat_rate", "23", "sales", 1, "Podstawowa stawka VAT (%)", "number"),
    ("min_order_value", "0", "sales", 1, "Minimalna wartość zamówienia", "number"),
    ("free_shipping_threshold", "200", "sales", 1, "Wartość zamówienia dla darmowej dostawy", "number"),
    ("allow_guest_checkout", "1", "sales", 0, "Zezwalaj na zakupy bez rejestracji (1 = tak, 0 = nie)", "checkbox"),
    ("default_order_status", "pending", "sales", 0, "Domyślny status nowego zamówienia", "select"),
    ("stock_management", "1", "sales", 0, "Zarządzanie stanem magazynowym (1 = włączone, 0 = wyłączone)", "checkbox"),
    ("low_stock_threshold", "5", "sales", 0, "Próg niskiego stanu magazynowego", "number")',
    'SELECT "Ustawienia sprzedaży już istnieją" AS message'
);

PREPARE stmt FROM @insert_sales_settings;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ustawienia recenzji produktów
SET @insert_review_settings = IF(@insert_default_settings = 1,
    'INSERT INTO shop_settings (setting_key, setting_value, setting_group, is_public, description, input_type) VALUES
    ("enable_reviews", "1", "reviews", 0, "Włącz recenzje produktów (1 = włączone, 0 = wyłączone)", "checkbox"),
    ("reviews_require_approval", "1", "reviews", 0, "Wymagaj zatwierdzenia recenzji (1 = tak, 0 = nie)", "checkbox"),
    ("allow_guest_reviews", "1", "reviews", 0, "Zezwalaj na recenzje od niezalogowanych użytkowników (1 = tak, 0 = nie)", "checkbox"),
    ("show_customer_name", "1", "reviews", 0, "Pokaż imię klienta w recenzjach (1 = tak, 0 = nie)", "checkbox"),
    ("min_review_length", "10", "reviews", 0, "Minimalna długość treści recenzji", "number"),
    ("reviews_per_page", "10", "reviews", 0, "Liczba recenzji na stronę", "number")',
    'SELECT "Ustawienia recenzji już istnieją" AS message'
);

PREPARE stmt FROM @insert_review_settings;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ustawienia mediów społecznościowych
SET @insert_social_settings = IF(@insert_default_settings = 1,
    'INSERT INTO shop_settings (setting_key, setting_value, setting_group, is_public, description, input_type) VALUES
    ("facebook_url", "", "social", 1, "Link do profilu na Facebooku", "text"),
    ("instagram_url", "", "social", 1, "Link do profilu na Instagramie", "text"),
    ("youtube_url", "", "social", 1, "Link do kanału YouTube", "text"),
    ("twitter_url", "", "social", 1, "Link do profilu na Twitterze", "text"),
    ("linkedin_url", "", "social", 1, "Link do profilu na LinkedIn", "text"),
    ("enable_sharing", "1", "social", 0, "Włącz przyciski udostępniania (1 = włączone, 0 = wyłączone)", "checkbox")',
    'SELECT "Ustawienia mediów społecznościowych już istnieją" AS message'
);

PREPARE stmt FROM @insert_social_settings;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
