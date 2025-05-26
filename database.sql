-- Tworzenie tabeli dla motocykli używanych
CREATE TABLE IF NOT EXISTS used_motorcycles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    mileage INT NOT NULL,
    engine_capacity INT NOT NULL,
    power INT NULL,
    color VARCHAR(50) NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT NULL,
    features TEXT NULL,
    registration_number VARCHAR(20) NULL,
    vin VARCHAR(50) NULL,
    condition ENUM('excellent', 'very_good', 'good', 'average', 'poor') NOT NULL,
    status ENUM('available', 'reserved', 'sold', 'hidden') DEFAULT 'available',
    featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tworzenie tabeli dla zdjęć motocykli
CREATE TABLE IF NOT EXISTS motorcycle_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    motorcycle_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_main BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (motorcycle_id) REFERENCES used_motorcycles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wstawianie danych motocykli
INSERT INTO used_motorcycles (
    title, brand, model, year, mileage, engine_capacity, power, color, 
    price, description, features, registration_number, vin, condition, status, featured
) VALUES
('Honda CBR 600RR', 'Honda', 'CBR 600RR', 2018, 25000, 600, 120, 'Czerwony',
29900.00, 'Pięknie utrzymany motocykl sportowy w doskonałym stanie technicznym. Regularnie serwisowany, wszystkie płyny wymienione. Brak śladów kolizji.', 
'ABS, Tryb jazdy sportowej, Elektroniczna regulacja zawieszenia, LED światła', 
'WWL 12345', 'JH2SC46A0MK123456', 'very_good', 'available', 1),

('BMW R 1250 GS', 'BMW', 'R 1250 GS', 2020, 15000, 1250, 136, 'Niebieski',
59900.00, 'Legendarny motocykl turystyczny w stanie idealnym. Pełna historia serwisowa, wszystkie przeglądy na czas.', 
'Dynamic ESA, Tryb jazdy Pro, Podgrzewane uchwyty, Centralny bagażnik', 
'WWL 23456', 'WB10A4100LZ123456', 'excellent', 'available', 1),

('Yamaha MT-07', 'Yamaha', 'MT-07', 2019, 12500, 700, 74, 'Czarny',
32900.00, 'Dynamiczny naked bike w świetnym stanie. Idealny do miasta i weekendowych wycieczek.', 
'Tryb jazdy A/B, LED światła, Lekka rama aluminiowa', 
'WWL 34567', 'JYARN06E0KA123456', 'very_good', 'available', 0),

('Ducati Panigale V4', 'Ducati', 'Panigale V4', 2021, 8000, 1103, 214, 'Czerwony',
89900.00, 'Prawdziwa maszyna wyścigowa do jazdy po drogach publicznych. Pełna elektronika, tryby jazdy, ślizgacz.', 
'Launch Control, Quick Shifter, Elektroniczna regulacja zawieszenia, Tryby jazdy', 
'WWL 45678', 'ZDM1UBB1XMB123456', 'excellent', 'reserved', 1),

('Kawasaki Z900', 'Kawasaki', 'Z900', 2020, 18500, 900, 125, 'Zielony',
42900.00, 'Mocny naked bike w dobrym stanie. Idealny do dynamicznej jazdy miejskiej i weekendowych wycieczek.', 
'Tryby jazdy, ABS, Traction Control, LED światła', 
'WWL 56789', 'JKAZXCJ11LA123456', 'good', 'available', 0),

('Harley-Davidson Sportster 883', 'Harley-Davidson', 'Sportster 883', 2017, 22000, 883, 50, 'Czarny',
38900.00, 'Klasyczny cruiser w dobrym stanie. Charakterystyczny dźwięk i styl.', 
'ABS, Podgrzewane uchwyty, Własny styl', 
'WWL 67890', '1HD1KBP10HB123456', 'good', 'available', 0),

('Triumph Street Triple RS', 'Triumph', 'Street Triple RS', 2021, 9500, 765, 123, 'Biały',
45900.00, 'Sportowy naked bike w stanie idealnym. Pełna elektronika i zaawansowane systemy bezpieczeństwa.', 
'Quick Shifter, Tryby jazdy, Elektroniczna regulacja zawieszenia, LED światła', 
'WWL 78901', 'SMTT20VX5MB123456', 'excellent', 'available', 1),

('KTM 1290 Super Duke R', 'KTM', '1290 Super Duke R', 2020, 12000, 1301, 180, 'Pomarańczowy',
69900.00, 'Najmocniejszy naked bike w ofercie. Pełna elektronika i zaawansowane systemy bezpieczeństwa.', 
'Launch Control, Quick Shifter, Tryby jazdy, Cornering ABS', 
'WWL 89012', 'VBKEXM402LM123456', 'very_good', 'available', 1),

('Suzuki GSX-R1000', 'Suzuki', 'GSX-R1000', 2019, 16500, 999, 202, 'Niebieski',
54900.00, 'Legendarny superbike w świetnym stanie. Pełna elektronika i zaawansowane systemy bezpieczeństwa.', 
'Launch Control, Quick Shifter, Tryby jazdy, S-DMS', 
'WWL 90123', 'JS1GR7KA8K2101234', 'very_good', 'available', 0),

('Aprilia RSV4 1100', 'Aprilia', 'RSV4 1100', 2021, 7500, 1100, 217, 'Czarny',
79900.00, 'Włoski superbike w stanie idealnym. Pełna elektronika i zaawansowane systemy bezpieczeństwa.', 
'Launch Control, Quick Shifter, Tryby jazdy, APRC', 
'WWL 01234', 'ZAPRSV4A0MB123456', 'excellent', 'reserved', 1),

('Moto Guzzi V85 TT', 'Moto Guzzi', 'V85 TT', 2020, 14500, 853, 80, 'Żółty',
42900.00, 'Elegancki motocykl turystyczny w dobrym stanie. Idealny do długich podróży.', 
'Tryby jazdy, ABS, Traction Control, Centralny bagażnik', 
'WWL 12340', 'ZGUKDH0T0LM123456', 'good', 'available', 0),

('Indian Scout Bobber', 'Indian', 'Scout Bobber', 2019, 18500, 1133, 100, 'Czarny',
49900.00, 'Stylowy cruiser w świetnym stanie. Charakterystyczny wygląd i komfort jazdy.', 
'ABS, Tryby jazdy, LED światła, Własny styl', 
'WWL 23450', '54UDB30C0KB123456', 'very_good', 'available', 0);

-- Wstawianie zdjęć motocykli
INSERT INTO motorcycle_images (motorcycle_id, image_path, is_main) VALUES
(1, 'https://readdy.ai/api/search-image?query=Honda%20CBR%20600RR%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1', TRUE),
(2, 'https://readdy.ai/api/search-image?query=BMW%20R%201250%20GS%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1', TRUE),
(3, 'https://readdy.ai/api/search-image?query=Yamaha%20MT-07%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1', TRUE),
(4, 'https://readdy.ai/api/search-image?query=Ducati%20Panigale%20V4%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1', TRUE),
(5, 'https://readdy.ai/api/search-image?query=Kawasaki%20Z900%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1', TRUE),
(6, 'https://readdy.ai/api/search-image?query=Harley-Davidson%20Sportster%20883%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1', TRUE),
(7, 'https://readdy.ai/api/search-image?query=Triumph%20Street%20Triple%20RS%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1', TRUE),
(8, 'https://readdy.ai/api/search-image?query=KTM%201290%20Super%20Duke%20R%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1', TRUE),
(9, 'https://readdy.ai/api/search-image?query=Suzuki%20GSX-R1000%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1', TRUE),
(10, 'https://readdy.ai/api/search-image?query=Aprilia%20RSV4%201100%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1', TRUE),
(11, 'https://readdy.ai/api/search-image?query=Moto%20Guzzi%20V85%20TT%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1', TRUE),
(12, 'https://readdy.ai/api/search-image?query=Indian%20Scout%20Bobber%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1', TRUE);

-- Dodawanie nowych motocykli do tabeli used_motorcycles
INSERT INTO used_motorcycles (
    title, brand, model, year, mileage, engine_capacity, power, color, 
    price, description, features, registration_number, vin, `condition`, status, featured
) VALUES
('Triumph Street Triple RS', 'Triumph', 'Street Triple RS', 2021, 9500, 765, 123, 'Biały',
45900.00, 'Sportowy naked bike w stanie idealnym. Pełna elektronika i zaawansowane systemy bezpieczeństwa.', 
'Quick Shifter, Tryby jazdy, Elektroniczna regulacja zawieszenia, LED światła', 
'WWL 78901', 'SMTT20VX5MB123456', 'excellent', 'available', 1),

('KTM 1290 Super Duke R', 'KTM', '1290 Super Duke R', 2020, 12000, 1301, 180, 'Pomarańczowy',
69900.00, 'Najmocniejszy naked bike w ofercie. Pełna elektronika i zaawansowane systemy bezpieczeństwa.', 
'Launch Control, Quick Shifter, Tryby jazdy, Cornering ABS', 
'WWL 89012', 'VBKEXM402LM123456', 'very_good', 'available', 1),

('Suzuki GSX-R1000', 'Suzuki', 'GSX-R1000', 2019, 16500, 999, 202, 'Niebieski',
54900.00, 'Legendarny superbike w świetnym stanie. Pełna elektronika i zaawansowane systemy bezpieczeństwa.', 
'Launch Control, Quick Shifter, Tryby jazdy, S-DMS', 
'WWL 90123', 'JS1GR7KA8K2101234', 'very_good', 'available', 0),

('Aprilia RSV4 1100', 'Aprilia', 'RSV4 1100', 2021, 7500, 1100, 217, 'Czarny',
79900.00, 'Włoski superbike w stanie idealnym. Pełna elektronika i zaawansowane systemy bezpieczeństwa.', 
'Launch Control, Quick Shifter, Tryby jazdy, APRC', 
'WWL 01234', 'ZAPRSV4A0MB123456', 'excellent', 'reserved', 1),

('Moto Guzzi V85 TT', 'Moto Guzzi', 'V85 TT', 2020, 14500, 853, 80, 'Żółty',
42900.00, 'Elegancki motocykl turystyczny w dobrym stanie. Idealny do długich podróży.', 
'Tryby jazdy, ABS, Traction Control, Centralny bagażnik', 
'WWL 12340', 'ZGUKDH0T0LM123456', 'good', 'available', 0),

('Indian Scout Bobber', 'Indian', 'Scout Bobber', 2019, 18500, 1133, 100, 'Czarny',
49900.00, 'Stylowy cruiser w świetnym stanie. Charakterystyczny wygląd i komfort jazdy.', 
'ABS, Tryby jazdy, LED światła, Własny styl', 
'WWL 23450', '54UDB30C0KB123456', 'very_good', 'available', 0);

-- Dodawanie zdjęć dla nowych motocykli
INSERT INTO motorcycle_images (motorcycle_id, image_path, is_main) VALUES
(4, 'https://readdy.ai/api/search-image?query=Triumph%20Street%20Triple%20RS%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1', TRUE),
(5, 'https://readdy.ai/api/search-image?query=KTM%201290%20Super%20Duke%20R%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1', TRUE),
(6, 'https://readdy.ai/api/search-image?query=Suzuki%20GSX-R1000%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1', TRUE),
(7, 'https://readdy.ai/api/search-image?query=Aprilia%20RSV4%201100%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1', TRUE),
(8, 'https://readdy.ai/api/search-image?query=Moto%20Guzzi%20V85%20TT%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1', TRUE),
(9, 'https://readdy.ai/api/search-image?query=Indian%20Scout%20Bobber%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1', TRUE); 