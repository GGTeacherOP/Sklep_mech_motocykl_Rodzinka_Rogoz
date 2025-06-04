<?php
// Plik obsługujący żądania AJAX do pobierania szczegółów rezerwacji serwisowej
session_start();

// Sprawdzenie, czy użytkownik jest zalogowany jako administrator
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Brak dostępu']);
    exit;
}

// Stała określająca, że jesteśmy w panelu administracyjnym
define('ADMIN_PANEL', true);

// Ścieżka do głównego katalogu
$base_path = dirname(dirname(__DIR__));
require_once $base_path . '/includes/config.php';

// Sprawdzenie czy przekazano ID rezerwacji
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Nieprawidłowe ID rezerwacji']);
    exit;
}

$booking_id = (int)$_GET['id'];

// Pobieranie szczegółów rezerwacji
$query = "SELECT sb.*, 
          m.name AS mechanic_name, m.specialization, m.experience, m.image_path AS mechanic_image,
          s.name AS service_name, s.description AS service_description, s.price, s.duration,
          u.email AS user_email, u.first_name, u.last_name, u.phone
          FROM service_bookings sb
          LEFT JOIN mechanics m ON sb.mechanic_id = m.id
          LEFT JOIN services s ON sb.service_id = s.id
          LEFT JOIN users u ON sb.user_id = u.id
          WHERE sb.id = $booking_id";

$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Rezerwacja nie została znaleziona']);
    exit;
}

$booking = $result->fetch_assoc();

// Formatowanie daty i czasu
$booking_date = new DateTime($booking['booking_date']);
$formatted_date = $booking_date->format('d.m.Y');

// Formatowanie czasu trwania usługi
$hours = floor($booking['duration'] / 60);
$minutes = $booking['duration'] % 60;
$duration_text = '';

if ($hours > 0) {
    $duration_text .= $hours . ' godz. ';
}

if ($minutes > 0 || $hours == 0) {
    $duration_text .= $minutes . ' min.';
}

// Status rezerwacji
$status_text = '';
$status_class = '';

switch ($booking['status']) {
    case 'pending':
        $status_text = 'Oczekująca';
        $status_class = 'bg-yellow-100 text-yellow-800';
        break;
    case 'confirmed':
        $status_text = 'Potwierdzona';
        $status_class = 'bg-blue-100 text-blue-800';
        break;
    case 'completed':
        $status_text = 'Zakończona';
        $status_class = 'bg-green-100 text-green-800';
        break;
    case 'cancelled':
        $status_text = 'Anulowana';
        $status_class = 'bg-red-100 text-red-800';
        break;
    default:
        $status_text = $booking['status'];
        $status_class = 'bg-gray-100 text-gray-800';
}

// Mechanik - zdjęcie
$mechanic_image = !empty($booking['mechanic_image']) 
    ? '../' . $booking['mechanic_image'] 
    : 'https://readdy.ai/api/search-image?query=Professional%20motorcycle%20mechanic%20in%20uniform%2C%20confident%20pose%2C%20clean%20background&width=100&height=100&seq=1&orientation=squarish';

// Tworzenie HTML z detalami rezerwacji
$html = '
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h3 class="text-lg font-semibold mb-3">Informacje o rezerwacji</h3>
            <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">ID rezerwacji:</span>
                    <span class="font-medium">' . $booking['id'] . '</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Data wizyty:</span>
                    <span class="font-medium">' . $formatted_date . '</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Godzina:</span>
                    <span class="font-medium">' . $booking['booking_time'] . '</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Status:</span>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $status_class . '">
                        ' . $status_text . '
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Data utworzenia:</span>
                    <span class="font-medium">' . date('d.m.Y H:i', strtotime($booking['created_at'])) . '</span>
                </div>
            </div>
        </div>
        
        <div>
            <h3 class="text-lg font-semibold mb-3">Informacje o kliencie</h3>
            <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Email:</span>
                    <span class="font-medium">' . ($booking['user_email'] ?: 'Gość (brak konta)') . '</span>
                </div>';

if (!empty($booking['first_name']) || !empty($booking['last_name'])) {
    $html .= '
                <div class="flex justify-between">
                    <span class="text-gray-600">Imię i nazwisko:</span>
                    <span class="font-medium">' . $booking['first_name'] . ' ' . $booking['last_name'] . '</span>
                </div>';
}

if (!empty($booking['phone'])) {
    $html .= '
                <div class="flex justify-between">
                    <span class="text-gray-600">Telefon:</span>
                    <span class="font-medium">' . $booking['phone'] . '</span>
                </div>';
}

$html .= '
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h3 class="text-lg font-semibold mb-3">Usługa</h3>
            <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Nazwa:</span>
                    <span class="font-medium">' . $booking['service_name'] . '</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Cena:</span>
                    <span class="font-medium">' . number_format($booking['price'], 2, ',', ' ') . ' zł</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Czas trwania:</span>
                    <span class="font-medium">' . $duration_text . '</span>
                </div>
                <div class="mt-2">
                    <span class="text-gray-600">Opis:</span>
                    <p class="text-sm mt-1">' . $booking['service_description'] . '</p>
                </div>
            </div>
        </div>
        
        <div>
            <h3 class="text-lg font-semibold mb-3">Mechanik</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center space-x-4 mb-4">
                    <img src="' . $mechanic_image . '" alt="' . $booking['mechanic_name'] . '" class="h-16 w-16 rounded-full object-cover">
                    <div>
                        <h4 class="font-medium">' . $booking['mechanic_name'] . '</h4>
                        <p class="text-sm text-gray-600">' . $booking['specialization'] . '</p>
                        <p class="text-xs text-gray-500">Doświadczenie: ' . $booking['experience'] . ' lat</p>
                    </div>
                </div>
            </div>
        </div>
    </div>';

if (!empty($booking['notes'])) {
    $html .= '
    <div>
        <h3 class="text-lg font-semibold mb-3">Uwagi klienta</h3>
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm">' . nl2br(htmlspecialchars($booking['notes'])) . '</p>
        </div>
    </div>';
}

$html .= '
</div>';

// Zwróć wynik jako JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'html' => $html
]);
