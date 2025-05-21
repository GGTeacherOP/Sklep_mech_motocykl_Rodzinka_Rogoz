<?php
// Strona serwisu motocyklowego
$page_title = "Serwis Motocyklowy";
require_once 'includes/config.php';

// Obsługa formularza rezerwacji
$booking_success = false;
$booking_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_form'])) {
    $mechanic_id = (int)sanitize($_POST['mechanic_id']);
    $service_id = (int)sanitize($_POST['service_id']);
    $booking_date = sanitize($_POST['booking_date']);
    $booking_time = sanitize($_POST['booking_time']);
    $notes = sanitize($_POST['notes']);
    $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
    
    // Walidacja
    $errors = [];
    
    if (empty($mechanic_id)) {
        $errors[] = "Wybierz mechanika";
    }
    
    if (empty($service_id)) {
        $errors[] = "Wybierz rodzaj usługi";
    }
    
    if (empty($booking_date)) {
        $errors[] = "Wybierz datę wizyty";
    } else {
        // Sprawdź czy data nie jest z przeszłości
        $current_date = date('Y-m-d');
        if ($booking_date < $current_date) {
            $errors[] = "Data wizyty nie może być z przeszłości";
        }
    }
    
    if (empty($booking_time)) {
        $errors[] = "Wybierz godzinę wizyty";
    }
    
    if (empty($errors)) {
        // Sprawdź czy termin jest dostępny
        $check_availability = "SELECT id FROM service_bookings 
                             WHERE mechanic_id = $mechanic_id 
                             AND booking_date = '$booking_date' 
                             AND booking_time = '$booking_time'
                             AND status != 'cancelled'";
        
        $availability_result = $conn->query($check_availability);
        
        if ($availability_result && $availability_result->num_rows > 0) {
            $booking_error = "Wybrany termin jest już zajęty. Proszę wybrać inny termin.";
        } else {
            // Zapisanie rezerwacji w bazie danych
            $sql = "INSERT INTO service_bookings (user_id, mechanic_id, service_id, booking_date, booking_time, notes) 
                   VALUES (" . ($user_id ? $user_id : "NULL") . ", $mechanic_id, $service_id, '$booking_date', '$booking_time', '$notes')";
            
            if ($conn->query($sql) === TRUE) {
                $booking_success = true;
                
                // Jeśli użytkownik nie jest zalogowany, zapisujemy dane w sesji
                if (!$user_id) {
                    $_SESSION['booking_id'] = $conn->insert_id;
                }
            } else {
                $booking_error = "Wystąpił błąd podczas zapisywania rezerwacji: " . $conn->error;
            }
        }
    } else {
        $booking_error = implode("<br>", $errors);
    }
}

// Pobieranie mechaników
$mechanics_query = "SELECT * FROM mechanics WHERE status = 'active'";
$mechanics = [];
$mechanics_result = $conn->query($mechanics_query);

if ($mechanics_result && $mechanics_result->num_rows > 0) {
    while ($row = $mechanics_result->fetch_assoc()) {
        $mechanics[] = $row;
    }
}

// Pobieranie usług
$services_query = "SELECT * FROM services";
$services = [];
$services_result = $conn->query($services_query);

if ($services_result && $services_result->num_rows > 0) {
    while ($row = $services_result->fetch_assoc()) {
        $services[] = $row;
    }
}

// Domyślni mechanicy jeśli baza danych jest pusta
if (empty($mechanics)) {
    $mechanics = [
        [
            'id' => 1,
            'name' => 'Jan Kowalski',
            'specialization' => 'Honda, Yamaha',
            'experience' => 15,
            'rating' => 4.5,
            'description' => 'Doświadczony mechanik z 15-letnim stażem. Specjalizuje się w motocyklach japońskich.',
            'image_path' => 'uploads/mechanics/mechanic1.jpg'
        ],
        [
            'id' => 2,
            'name' => 'Piotr Nowak',
            'specialization' => 'BMW, Ducati',
            'experience' => 10,
            'rating' => 5.0,
            'description' => 'Ekspert w motocyklach europejskich. Certyfikowany mechanik BMW i Ducati.',
            'image_path' => 'uploads/mechanics/mechanic2.jpg'
        ],
        [
            'id' => 3,
            'name' => 'Anna Wiśniewska',
            'specialization' => 'Suzuki, Kawasaki',
            'experience' => 5,
            'rating' => 4.0,
            'description' => 'Młoda, ambitna mechanik z pasją do motocykli sportowych.',
            'image_path' => 'uploads/mechanics/mechanic3.jpg'
        ]
    ];
}

// Domyślne usługi jeśli baza danych jest pusta
if (empty($services)) {
    $services = [
        [
            'id' => 1,
            'name' => 'Przegląd okresowy',
            'description' => 'Podstawowy przegląd motocykla zgodnie z zaleceniami producenta',
            'price' => 250.00,
            'duration' => 120
        ],
        [
            'id' => 2,
            'name' => 'Naprawa',
            'description' => 'Naprawa usterek mechanicznych',
            'price' => 300.00,
            'duration' => 180
        ],
        [
            'id' => 3,
            'name' => 'Diagnostyka',
            'description' => 'Pełna diagnostyka komputerowa',
            'price' => 150.00,
            'duration' => 60
        ],
        [
            'id' => 4,
            'name' => 'Konserwacja',
            'description' => 'Konserwacja i przygotowanie motocykla do sezonu lub zimowania',
            'price' => 200.00,
            'duration' => 90
        ]
    ];
}

// Wybrany mechanik (jeśli przekazano w URL)
$selected_mechanic = null;
if (isset($_GET['mechanic'])) {
    $mechanic_id = (int)$_GET['mechanic'];
    foreach ($mechanics as $mechanic) {
        if ($mechanic['id'] == $mechanic_id) {
            $selected_mechanic = $mechanic;
            break;
        }
    }
}

include 'includes/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-center mb-8">Serwis Motocyklowy</h1>
    
    <?php if ($booking_success): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-8" role="alert">
        <p class="font-bold">Rezerwacja potwierdzona!</p>
        <p>Twoja wizyta została pomyślnie zarezerwowana. Skontaktujemy się z Tobą, aby potwierdzić szczegóły.</p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($booking_error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8" role="alert">
        <p class="font-bold">Wystąpił błąd!</p>
        <p><?php echo $booking_error; ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Lista mechaników -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
        <?php foreach ($mechanics as $mechanic): ?>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center mb-4">
                <?php if (!empty($mechanic['image_path'])): ?>
                    <img src="<?php echo $mechanic['image_path']; ?>" 
                         alt="<?php echo $mechanic['name']; ?>" 
                         class="w-20 h-20 rounded-full object-cover mr-4">
                <?php else: ?>
                    <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                        <i class="ri-user-line text-4xl text-gray-500"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <h3 class="text-xl font-bold"><?php echo $mechanic['name']; ?></h3>
                    <p class="text-gray-600">Specjalista <?php echo $mechanic['specialization']; ?></p>
                </div>
            </div>
            <div class="flex text-yellow-400 mb-4">
                <?php 
                $rating = $mechanic['rating'];
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $rating) {
                        echo '<i class="ri-star-fill"></i>';
                    } else if ($i - 0.5 <= $rating) {
                        echo '<i class="ri-star-half-fill"></i>';
                    } else {
                        echo '<i class="ri-star-line"></i>';
                    }
                }
                ?>
                <span class="text-gray-600 ml-2">(<?php echo $rating; ?>/5)</span>
            </div>
            <p class="text-gray-600 mb-4"><?php echo $mechanic['description']; ?></p>
            <button onclick="showBookingModal(<?php echo $mechanic['id']; ?>, '<?php echo $mechanic['name']; ?>')" class="w-full bg-primary text-white py-2 rounded-button hover:bg-opacity-90 transition">
                Umów wizytę
            </button>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Sekcja usług -->
    <section class="mb-16">
        <h2 class="text-2xl font-bold text-center mb-8">Nasze usługi</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($services as $service): ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-xl font-bold mb-2"><?php echo $service['name']; ?></h3>
                <p class="text-gray-600 mb-4"><?php echo $service['description']; ?></p>
                <div class="flex justify-between items-center">
                    <div>
                        <span class="text-primary font-bold text-xl"><?php echo number_format($service['price'], 2, ',', ' '); ?> zł</span>
                        <span class="text-gray-500 text-sm ml-2">Czas: ok. <?php echo $service['duration']; ?> min</span>
                    </div>
                    <button onclick="showBookingModalWithService(null, null, <?php echo $service['id']; ?>)" class="bg-primary text-white px-4 py-2 rounded-button hover:bg-opacity-90 transition">
                        Zarezerwuj
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Sekcja opinii -->
    <section class="mb-16">
        <h2 class="text-2xl font-bold text-center mb-8">Opinie naszych klientów</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            // Pobieranie opinii
            $reviews_query = "SELECT mr.*, u.first_name, u.last_name 
                              FROM mechanic_reviews mr 
                              LEFT JOIN users u ON mr.user_id = u.id 
                              WHERE mr.status = 'approved' 
                              ORDER BY mr.created_at DESC 
                              LIMIT 3";
            
            $reviews_result = $conn->query($reviews_query);
            $reviews = [];
            
            if ($reviews_result && $reviews_result->num_rows > 0) {
                while ($row = $reviews_result->fetch_assoc()) {
                    $reviews[] = $row;
                }
            }
            
            // Domyślne opinie jeśli baza danych jest pusta
            if (empty($reviews)) {
                $reviews = [
                    [
                        'name' => 'Marek Nowak',
                        'rating' => 5,
                        'comment' => 'Wzorowa obsługa i profesjonalizm. Mechanicy doskonale znają się na swojej pracy, a ceny są uczciwe. Polecam każdemu motocykliście!',
                        'service_date' => '2025-04-15'
                    ],
                    [
                        'name' => 'Alicja Kowalczyk',
                        'rating' => 4,
                        'comment' => 'Bardzo dobry serwis, szybko zdiagnozowali i naprawili problem z moim Ducati. Jedyna uwaga to trochę dłuższy czas oczekiwania na wolny termin.',
                        'service_date' => '2025-04-02'
                    ],
                    [
                        'name' => 'Tomasz Wiśniewski',
                        'rating' => 5,
                        'comment' => 'Oddałem swój motocykl w dobre ręce. Pani Anna doskonale poradziła sobie z nietypową usterką, której inni mechanicy nie potrafili naprawić. Zdecydowanie polecam!',
                        'service_date' => '2025-03-28'
                    ]
                ];
            }
            
            foreach ($reviews as $review):
                $review_name = isset($review['first_name']) ? $review['first_name'] . ' ' . $review['last_name'] : $review['name'];
            ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center mr-4">
                        <i class="ri-user-line text-gray-500"></i>
                    </div>
                    <div>
                        <h3 class="font-bold"><?php echo $review_name; ?></h3>
                        <p class="text-gray-500 text-sm">
                            <?php echo isset($review['service_date']) ? date('d.m.Y', strtotime($review['service_date'])) : ''; ?>
                        </p>
                    </div>
                </div>
                <div class="flex text-yellow-400 mb-3">
                    <?php 
                    $rating = $review['rating'];
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $rating) {
                            echo '<i class="ri-star-fill"></i>';
                        } else {
                            echo '<i class="ri-star-line"></i>';
                        }
                    }
                    ?>
                </div>
                <p class="text-gray-600"><?php echo $review['comment']; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8">
            <button onclick="showMoreReviews()" class="bg-white text-primary border border-primary px-6 py-2 rounded-button font-medium hover:bg-primary hover:text-white transition">
                Zobacz więcej opinii
            </button>
        </div>
    </section>

    <!-- Modal do umawiania wizyty -->
    <div id="bookingModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <h2 class="text-2xl font-bold mb-6">Umów wizytę</h2>
            <form id="bookingForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-4">
                <input type="hidden" name="booking_form" value="1">
                <input type="hidden" id="mechanic_id" name="mechanic_id" value="">
                
                <div>
                    <label class="block text-gray-700 mb-2">Mechanik</label>
                    <input type="text" id="mechanicName" readonly class="w-full p-2 border rounded bg-gray-100">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Rodzaj usługi</label>
                    <select id="service_id" name="service_id" required class="w-full p-2 border rounded">
                        <option value="">Wybierz usługę</option>
                        <?php foreach ($services as $service): ?>
                        <option value="<?php echo $service['id']; ?>"><?php echo $service['name']; ?> (<?php echo number_format($service['price'], 2, ',', ' '); ?> zł)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Data</label>
                    <input type="date" name="booking_date" required class="w-full p-2 border rounded"
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Godzina</label>
                    <select name="booking_time" required class="w-full p-2 border rounded">
                        <option value="">Wybierz godzinę</option>
                        <?php
                        // Generowanie godzin od 9:00 do 16:00
                        for ($hour = 9; $hour <= 16; $hour++) {
                            $time = sprintf("%02d:00", $hour);
                            echo "<option value=\"$time\">$time</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Uwagi</label>
                    <textarea name="notes" class="w-full p-2 border rounded" rows="3"></textarea>
                </div>
                
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-primary text-white py-2 rounded-button hover:bg-opacity-90 transition">
                        Potwierdź
                    </button>
                    <button type="button" onclick="hideBookingModal()" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-button hover:bg-gray-300 transition">
                        Anuluj
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal z wszystkimi opiniami -->
    <div id="reviewsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Wszystkie opinie</h2>
                <button onclick="hideMoreReviews()" class="text-gray-500 hover:text-gray-700">
                    <i class="ri-close-line text-2xl"></i>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php
                // Wyświetlanie wszystkich opinii (dodatkowe opinie dodane na sztywno)
                $all_reviews = $reviews;
                
                // Dodatkowe opinie
                $more_reviews = [
                    [
                        'name' => 'Piotr Kowalski',
                        'rating' => 5,
                        'comment' => 'Najlepszy serwis w mieście! Profesjonalna obsługa i bardzo dobra komunikacja. Pan Piotr świetnie poradził sobie z naprawą mojego BMW.',
                        'service_date' => '2025-03-10'
                    ],
                    [
                        'name' => 'Marta Jankowska',
                        'rating' => 4,
                        'comment' => 'Bardzo pozytywne doświadczenie z serwisem. Wszystko zostało wykonane zgodnie z ustaleniami, a motocykl działa jak nowy.',
                        'service_date' => '2025-02-28'
                    ],
                    [
                        'name' => 'Adam Nowicki',
                        'rating' => 5,
                        'comment' => 'Szczerze polecam ten serwis. Jan doskonale zna się na motocyklach Honda i Yamaha. Profesjonalnie i w dobrej cenie.',
                        'service_date' => '2025-02-15'
                    ],
                    [
                        'name' => 'Karolina Malinowska',
                        'rating' => 3,
                        'comment' => 'Usługa wykonana dobrze, ale trzeba było trochę poczekać. Ceny umiarkowane.',
                        'service_date' => '2025-01-20'
                    ]
                ];
                
                $all_reviews = array_merge($all_reviews, $more_reviews);
                
                foreach ($all_reviews as $review):
                    $review_name = isset($review['first_name']) ? $review['first_name'] . ' ' . $review['last_name'] : $review['name'];
                ?>
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center mr-4">
                            <i class="ri-user-line text-gray-500"></i>
                        </div>
                        <div>
                            <h3 class="font-bold"><?php echo $review_name; ?></h3>
                            <p class="text-gray-500 text-sm">
                                <?php echo isset($review['service_date']) ? date('d.m.Y', strtotime($review['service_date'])) : ''; ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex text-yellow-400 mb-3">
                        <?php 
                        $rating = $review['rating'];
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<i class="ri-star-fill"></i>';
                            } else {
                                echo '<i class="ri-star-line"></i>';
                            }
                        }
                        ?>
                    </div>
                    <p class="text-gray-600"><?php echo $review['comment']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Modal potwierdzenia -->
    <div id="confirmationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4 text-center">
            <div class="text-green-500 mb-4">
                <i class="ri-checkbox-circle-line text-5xl"></i>
            </div>
            <h2 class="text-2xl font-bold mb-4">Rezerwacja potwierdzona!</h2>
            <p class="text-gray-600 mb-6">Twoja wizyta została pomyślnie zarezerwowana. Skontaktujemy się z Tobą, aby potwierdzić szczegóły.</p>
            <button onclick="hideConfirmationModal()" class="bg-primary text-white py-2 px-6 rounded-button hover:bg-opacity-90 transition">
                OK
            </button>
        </div>
    </div>
</main>

<?php
// Przygotowanie danych dla JavaScript
$js_data = '';
if ($selected_mechanic) {
    $mechanic_data = json_encode(['id' => $selected_mechanic['id'], 'name' => $selected_mechanic['name']]);
    $js_data .= "var mechanicData = {$mechanic_data};\n";
    $js_data .= "showBookingModal(mechanicData.id, mechanicData.name);\n";
}

if ($booking_success) {
    $js_data .= "showConfirmationModal();\n";
}

// Dodatkowy JS dla strony serwisowej
$extra_js = <<<'EOT'
<script>
    // Funkcja do wyświetlania modalu rezerwacji
    function showBookingModal(mechanicId, mechanicName) {
        document.getElementById('mechanic_id').value = mechanicId;
        document.getElementById('mechanicName').value = mechanicName;
        document.getElementById('bookingModal').classList.remove('hidden');
        document.getElementById('bookingModal').classList.add('flex');
    }
    
    // Funkcja do wyświetlania modalu rezerwacji z wybraną usługą
    function showBookingModalWithService(mechanicId, mechanicName, serviceId) {
        if (mechanicId) {
            document.getElementById('mechanic_id').value = mechanicId;
            document.getElementById('mechanicName').value = mechanicName;
        } else {
            // Jeśli nie wybrano mechanika, użytkownik sam wybierze z listy
            document.getElementById('mechanic_id').value = '';
            document.getElementById('mechanicName').value = 'Wybierz mechanika';
        }
        
        if (serviceId) {
            document.getElementById('service_id').value = serviceId;
        }
        
        document.getElementById('bookingModal').classList.remove('hidden');
        document.getElementById('bookingModal').classList.add('flex');
    }
    
    // Funkcja do ukrywania modalu rezerwacji
    function hideBookingModal() {
        document.getElementById('bookingModal').classList.remove('flex');
        document.getElementById('bookingModal').classList.add('hidden');
    }
    
    // Funkcja do wyświetlania wszystkich opinii
    function showMoreReviews() {
        document.getElementById('reviewsModal').classList.remove('hidden');
        document.getElementById('reviewsModal').classList.add('flex');
    }
    
    // Funkcja do ukrywania modalu z opiniami
    function hideMoreReviews() {
        document.getElementById('reviewsModal').classList.remove('flex');
        document.getElementById('reviewsModal').classList.add('hidden');
    }
    
    // Funkcja do wyświetlania modalu potwierdzenia
    function showConfirmationModal() {
        document.getElementById('confirmationModal').classList.remove('hidden');
        document.getElementById('confirmationModal').classList.add('flex');
    }
    
    // Funkcja do ukrywania modalu potwierdzenia
    function hideConfirmationModal() {
        document.getElementById('confirmationModal').classList.remove('flex');
        document.getElementById('confirmationModal').classList.add('hidden');
    }

    // Automatyczne wyświetlanie modalu mechanika z URL
    document.addEventListener('DOMContentLoaded', function() {
        // JS data will be added outside the heredoc
    });
</script>
EOT;

// Add dynamic JS data outside the heredoc
$extra_js .= "<script>\n";
$extra_js .= "document.addEventListener('DOMContentLoaded', function() {\n";
$extra_js .= $js_data;
$extra_js .= "});\n";
$extra_js .= "</script>\n";

include 'includes/footer.php';
?>
