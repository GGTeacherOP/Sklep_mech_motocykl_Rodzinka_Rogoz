<?php
// Strona szczegółowa motocykla używanego
$page_title = "Motocykl używany";
require_once 'includes/config.php';

// Sprawdzenie czy przekazano ID motocykla
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Jeśli nie, przekieruj na listę motocykli
    header('Location: used-motorcycles.php');
    exit;
}

$motorcycle_id = (int)$_GET['id'];

// Pobieranie danych motocykla
$query = "SELECT * FROM used_motorcycles WHERE id = $motorcycle_id";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    // Jeśli motocykl nie istnieje, przekieruj na listę motocykli
    header('Location: used-motorcycles.php');
    exit;
}

$motorcycle = $result->fetch_assoc();

// Pobieranie zdjęć motocykla
$images_query = "SELECT * FROM motorcycle_images WHERE motorcycle_id = $motorcycle_id ORDER BY is_main DESC, id ASC";
$images_result = $conn->query($images_query);
$images = [];

if ($images_result && $images_result->num_rows > 0) {
    while ($image = $images_result->fetch_assoc()) {
        $images[] = $image;
    }
}

// Główne zdjęcie motocykla (do wyświetlenia jako pierwsze)
$main_image = !empty($images) ? $images[0]['image_path'] : 'assets/images/motorcycle-placeholder.jpg';

// Konwersja wartości stanu motocykla na tekst
$condition_text = '';
switch ($motorcycle['condition']) {
    case 'excellent': $condition_text = 'Doskonały'; break;
    case 'very_good': $condition_text = 'Bardzo dobry'; break;
    case 'good': $condition_text = 'Dobry'; break;
    case 'average': $condition_text = 'Średni'; break;
    case 'poor': $condition_text = 'Słaby'; break;
    default: $condition_text = 'Nieznany';
}

// Konwersja statusu na tekst i klasę CSS
$status_class = 'bg-green-500';
$status_text = 'Dostępny';

if ($motorcycle['status'] == 'reserved') {
    $status_class = 'bg-yellow-500';
    $status_text = 'Zarezerwowany';
} elseif ($motorcycle['status'] == 'sold') {
    $status_class = 'bg-red-500';
    $status_text = 'Sprzedany';
} elseif ($motorcycle['status'] == 'hidden') {
    $status_class = 'bg-gray-500';
    $status_text = 'Niedostępny';
}

// Formatowanie listy wyposażenia
$features = [];
if (!empty($motorcycle['features'])) {
    $features = preg_split('/[\r\n,]+/', $motorcycle['features']);
    $features = array_map('trim', $features);
    $features = array_filter($features);
}

// Obsługa formularza rezerwacji oględzin
$booking_success = false;
$booking_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['viewing_form'])) {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $date = sanitize($_POST['date']);
    $time = sanitize($_POST['time']);
    $message = sanitize($_POST['message']);
    $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
    
    // Walidacja
    $errors = [];
    
    if (empty($first_name)) {
        $errors[] = "Imię jest wymagane";
    }
    
    if (empty($last_name)) {
        $errors[] = "Nazwisko jest wymagane";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Poprawny adres email jest wymagany";
    }
    
    if (empty($phone)) {
        $errors[] = "Telefon jest wymagany";
    }
    
    if (empty($date)) {
        $errors[] = "Data oględzin jest wymagana";
    } else {
        // Sprawdź czy data nie jest z przeszłości
        $current_date = date('Y-m-d');
        if ($date < $current_date) {
            $errors[] = "Data oględzin nie może być z przeszłości";
        }
    }
    
    if (empty($time)) {
        $errors[] = "Godzina oględzin jest wymagana";
    }
    
    if ($motorcycle['status'] != 'available') {
        $errors[] = "Ten motocykl nie jest obecnie dostępny do oględzin";
    }
    
    if (empty($errors)) {
        // Sprawdź czy jest już taka rezerwacja
        $check_query = "SELECT id FROM motorcycle_viewings 
                       WHERE motorcycle_id = $motorcycle_id 
                       AND date = '$date' 
                       AND time = '$time'
                       AND status != 'cancelled'";
        
        $check_result = $conn->query($check_query);
        
        if ($check_result && $check_result->num_rows > 0) {
            $booking_error = "Wybrany termin jest już zajęty. Proszę wybrać inny termin.";
        } else {
            // Utwórz rezerwację
            $sql = "INSERT INTO motorcycle_viewings 
                   (motorcycle_id, user_id, first_name, last_name, email, phone, date, time, message, status) 
                   VALUES 
                   ($motorcycle_id, " . ($user_id ? $user_id : "NULL") . ", '$first_name', '$last_name', '$email', '$phone', '$date', '$time', '$message', 'pending')";
            
            if ($conn->query($sql) === TRUE) {
                $booking_success = true;
                
                // Zmień status motocykla na zarezerwowany, jeśli użytkownik wybrał opcję rezerwacji
                if (isset($_POST['want_to_reserve']) && $_POST['want_to_reserve'] == 1) {
                    $update_query = "UPDATE used_motorcycles SET status = 'reserved' WHERE id = $motorcycle_id";
                    $conn->query($update_query);
                    $status_class = 'bg-yellow-500';
                    $status_text = 'Zarezerwowany';
                    $motorcycle['status'] = 'reserved';
                }
                
                // Wyślij powiadomienie email (w rzeczywistym systemie)
                // sendEmail($email, 'Potwierdzenie rezerwacji oględzin', $message);
            } else {
                $booking_error = "Wystąpił błąd podczas rezerwacji oględzin: " . $conn->error;
            }
        }
    } else {
        $booking_error = implode("<br>", $errors);
    }
}

include 'includes/header.php';
?>

<main>
    <!-- Okruszki -->
    <div class="bg-gray-100 py-2">
        <div class="container mx-auto px-4">
            <div class="text-sm text-gray-600">
                <a href="index.html" class="hover:text-primary">Strona główna</a> &raquo; 
                <a href="used-motorcycles.php" class="hover:text-primary">Motocykle używane</a> &raquo; 
                <span class="text-gray-800"><?php echo $motorcycle['title']; ?></span>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <?php if ($booking_success): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-8" role="alert">
            <p class="font-bold">Rezerwacja potwierdzona!</p>
            <p>Twoje oględziny zostały pomyślnie zarezerwowane. Skontaktujemy się z Tobą, aby potwierdzić szczegóły.</p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($booking_error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8" role="alert">
            <p class="font-bold">Wystąpił błąd!</p>
            <p><?php echo $booking_error; ?></p>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Galeria zdjęć -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <div class="relative overflow-hidden rounded-lg mb-4">
                        <div class="absolute top-3 right-3 <?php echo $status_class; ?> text-white text-xs font-semibold px-2 py-1 rounded z-10">
                            <?php echo $status_text; ?>
                        </div>
                        <img id="mainImage" src="<?php echo $main_image; ?>" alt="<?php echo $motorcycle['title']; ?>" class="w-full h-96 object-cover rounded-lg">
                    </div>
                    
                    <?php if (count($images) > 1): ?>
                    <div class="grid grid-cols-4 md:grid-cols-6 gap-2">
                        <?php foreach ($images as $index => $image): ?>
                        <div class="cursor-pointer">
                            <img src="<?php echo $image['image_path']; ?>" 
                                 alt="Miniatura" 
                                 onclick="changeMainImage('<?php echo $image['image_path']; ?>')" 
                                 class="w-full h-20 object-cover rounded-lg hover:opacity-80 transition <?php echo $index === 0 ? 'border-2 border-primary' : ''; ?>"
                                 data-index="<?php echo $index; ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Opis motocykla -->
                <div class="bg-white rounded-lg shadow-sm p-6 mt-6">
                    <h2 class="text-2xl font-bold mb-4">Opis</h2>
                    <div class="prose text-gray-700">
                        <?php echo nl2br(htmlspecialchars($motorcycle['description'])); ?>
                    </div>
                </div>
                
                <?php if (!empty($features)): ?>
                <!-- Wyposażenie i cechy -->
                <div class="bg-white rounded-lg shadow-sm p-6 mt-6">
                    <h2 class="text-2xl font-bold mb-4">Wyposażenie i cechy</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <?php foreach ($features as $feature): ?>
                        <div class="flex items-start">
                            <div class="text-primary mr-2"><i class="ri-checkbox-circle-fill"></i></div>
                            <div><?php echo htmlspecialchars($feature); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Informacje o motocyklu i formularz kontaktowy -->
            <div>
                <!-- Informacje o motocyklu -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h1 class="text-2xl font-bold mb-2"><?php echo $motorcycle['title']; ?></h1>
                    <div class="text-3xl font-bold text-primary mb-6"><?php echo number_format($motorcycle['price'], 0, ',', ' '); ?> zł</div>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Marka:</span>
                            <span class="font-medium"><?php echo $motorcycle['brand']; ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Model:</span>
                            <span class="font-medium"><?php echo $motorcycle['model']; ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Rok produkcji:</span>
                            <span class="font-medium"><?php echo $motorcycle['year']; ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Przebieg:</span>
                            <span class="font-medium"><?php echo number_format($motorcycle['mileage'], 0, ',', ' '); ?> km</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Pojemność silnika:</span>
                            <span class="font-medium"><?php echo $motorcycle['engine_capacity']; ?> cm³</span>
                        </div>
                        <?php if (!empty($motorcycle['power'])): ?>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Moc silnika:</span>
                            <span class="font-medium"><?php echo $motorcycle['power']; ?> KM</span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($motorcycle['color'])): ?>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Kolor:</span>
                            <span class="font-medium"><?php echo $motorcycle['color']; ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Stan:</span>
                            <span class="font-medium"><?php echo $condition_text; ?></span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-gray-600">Status:</span>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?> text-white"><?php echo $status_text; ?></span>
                        </div>
                    </div>
                    
                    <?php if ($motorcycle['status'] == 'available'): ?>
                    <div class="mt-6">
                        <button id="viewingBtn" class="w-full bg-primary text-white py-3 px-4 rounded-lg font-medium hover:bg-opacity-90 transition flex items-center justify-center">
                            <i class="ri-calendar-line mr-2"></i> Umów się na oględziny
                        </button>
                    </div>
                    <?php elseif ($motorcycle['status'] == 'reserved'): ?>
                    <div class="mt-6 bg-yellow-100 text-yellow-800 p-4 rounded-lg">
                        <p class="text-center">Ten motocykl jest obecnie zarezerwowany. Możesz skontaktować się z nami, aby uzyskać więcej informacji.</p>
                    </div>
                    <?php elseif ($motorcycle['status'] == 'sold'): ?>
                    <div class="mt-6 bg-red-100 text-red-800 p-4 rounded-lg">
                        <p class="text-center">Ten motocykl został już sprzedany. Zapraszamy do zapoznania się z innymi ofertami.</p>
                    </div>
                    <?php else: ?>
                    <div class="mt-6 bg-gray-100 text-gray-800 p-4 rounded-lg">
                        <p class="text-center">Ten motocykl jest obecnie niedostępny.</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Kontakt -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold mb-4">Kontakt</h2>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="w-10 h-10 flex items-center justify-center bg-primary/10 rounded-full mr-3">
                                <i class="ri-phone-line text-primary"></i>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">Telefon</p>
                                <p class="font-medium">+48 123 456 789</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-10 h-10 flex items-center justify-center bg-primary/10 rounded-full mr-3">
                                <i class="ri-mail-line text-primary"></i>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">Email</p>
                                <p class="font-medium">motocykle@motoshop.pl</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-10 h-10 flex items-center justify-center bg-primary/10 rounded-full mr-3">
                                <i class="ri-map-pin-line text-primary"></i>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">Adres</p>
                                <p class="font-medium">ul. Motocyklowa 123, 00-000 Warszawa</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal rezerwacji oględzin -->
    <div id="viewingModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
            <h2 class="text-2xl font-bold mb-6">Umów się na oględziny</h2>
            <form id="viewingForm" method="POST" action="" class="space-y-4">
                <input type="hidden" name="viewing_form" value="1">
                <input type="hidden" name="motorcycle_id" value="<?php echo $motorcycle_id; ?>">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">Imię *</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo $_SESSION['user_first_name'] ?? ''; ?>" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Nazwisko *</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo $_SESSION['user_last_name'] ?? ''; ?>" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" id="email" name="email" value="<?php echo $_SESSION['user_email'] ?? ''; ?>" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefon *</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo $_SESSION['user_phone'] ?? ''; ?>" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Data oględzin *</label>
                        <input type="date" id="date" name="date" min="<?php echo date('Y-m-d'); ?>" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                    </div>
                    <div>
                        <label for="time" class="block text-sm font-medium text-gray-700 mb-1">Godzina *</label>
                        <select id="time" name="time" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                            <option value="">Wybierz godzinę</option>
                            <option value="10:00">10:00</option>
                            <option value="11:00">11:00</option>
                            <option value="12:00">12:00</option>
                            <option value="13:00">13:00</option>
                            <option value="14:00">14:00</option>
                            <option value="15:00">15:00</option>
                            <option value="16:00">16:00</option>
                            <option value="17:00">17:00</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Wiadomość</label>
                    <textarea id="message" name="message" rows="3" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"></textarea>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" id="want_to_reserve" name="want_to_reserve" value="1" class="h-5 w-5 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="want_to_reserve" class="ml-2 block text-sm text-gray-700">
                        Chcę zarezerwować ten motocykl
                    </label>
                </div>
                
                <div class="text-xs text-gray-500 mb-4">
                    <p>* - pola wymagane</p>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="hideViewingModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition">
                        Anuluj
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg font-medium hover:bg-opacity-90 transition">
                        Zarezerwuj oględziny
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
// Funkcja do zmiany głównego zdjęcia
function changeMainImage(src) {
    const mainImage = document.getElementById('mainImage');
    mainImage.src = src;
    
    // Zmiana aktywnej miniatury
    const thumbnails = document.querySelectorAll('[data-index]');
    thumbnails.forEach(thumb => {
        if (thumb.src === src) {
            thumb.classList.add('border-2', 'border-primary');
        } else {
            thumb.classList.remove('border-2', 'border-primary');
        }
    });
}

// Obsługa modalu rezerwacji oględzin
function showViewingModal() {
    const modal = document.getElementById('viewingModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideViewingModal() {
    const modal = document.getElementById('viewingModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Inicjalizacja
document.addEventListener('DOMContentLoaded', function() {
    // Przycisk umówienia oględzin
    const viewingBtn = document.getElementById('viewingBtn');
    if (viewingBtn) {
        viewingBtn.addEventListener('click', showViewingModal);
    }
    
    // Zamykanie modalu po kliknięciu poza nim
    const modal = document.getElementById('viewingModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                hideViewingModal();
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
