<?php
// Plik obsługujący żądania AJAX do pobierania szczegółów rezerwacji oględzin motocykla
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

$viewing_id = (int)$_GET['id'];

// Pobieranie szczegółów rezerwacji
$query = "SELECT mv.*, 
          um.title AS motorcycle_title, um.brand, um.model, um.year, um.engine_capacity, um.price, um.status AS motorcycle_status,
          (SELECT image_path FROM motorcycle_images WHERE motorcycle_id = um.id AND is_main = 1 LIMIT 1) AS motorcycle_image,
          u.email AS user_email
          FROM motorcycle_viewings mv
          LEFT JOIN used_motorcycles um ON mv.motorcycle_id = um.id
          LEFT JOIN users u ON mv.user_id = u.id
          WHERE mv.id = $viewing_id";

$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Rezerwacja nie została znaleziona']);
    exit;
}

$viewing = $result->fetch_assoc();

// Formatowanie daty i czasu
$viewing_date = new DateTime($viewing['date']);
$formatted_date = $viewing_date->format('d.m.Y');
$created_at = new DateTime($viewing['created_at']);
$formatted_created_at = $created_at->format('d.m.Y H:i');

// Status rezerwacji
$status_text = '';
$status_class = '';

switch ($viewing['status']) {
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
        $status_text = $viewing['status'];
        $status_class = 'bg-gray-100 text-gray-800';
}

// Status motocykla
$motorcycle_status_text = '';
$motorcycle_status_class = '';

switch ($viewing['motorcycle_status']) {
    case 'available':
        $motorcycle_status_text = 'Dostępny';
        $motorcycle_status_class = 'bg-green-100 text-green-800';
        break;
    case 'reserved':
        $motorcycle_status_text = 'Zarezerwowany';
        $motorcycle_status_class = 'bg-yellow-100 text-yellow-800';
        break;
    case 'sold':
        $motorcycle_status_text = 'Sprzedany';
        $motorcycle_status_class = 'bg-red-100 text-red-800';
        break;
    case 'hidden':
        $motorcycle_status_text = 'Ukryty';
        $motorcycle_status_class = 'bg-gray-100 text-gray-800';
        break;
    default:
        $motorcycle_status_text = $viewing['motorcycle_status'];
        $motorcycle_status_class = 'bg-gray-100 text-gray-800';
}

// Zdjęcie motocykla
$motorcycle_image = !empty($viewing['motorcycle_image']) 
    ? '../' . $viewing['motorcycle_image'] 
    : 'https://readdy.ai/api/search-image?query=Motorcycle%20silhouette&width=100&height=100&orientation=squarish';

// Tworzenie HTML z detalami rezerwacji
$html = '
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h3 class="text-lg font-semibold mb-3">Informacje o rezerwacji</h3>
            <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">ID rezerwacji:</span>
                    <span class="font-medium">' . $viewing['id'] . '</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Data oględzin:</span>
                    <span class="font-medium">' . $formatted_date . '</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Godzina:</span>
                    <span class="font-medium">' . $viewing['time'] . '</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Status:</span>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $status_class . '">
                        ' . $status_text . '
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Data utworzenia:</span>
                    <span class="font-medium">' . $formatted_created_at . '</span>
                </div>
            </div>
        </div>
        
        <div>
            <h3 class="text-lg font-semibold mb-3">Informacje o kliencie</h3>
            <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Imię i nazwisko:</span>
                    <span class="font-medium">' . $viewing['first_name'] . ' ' . $viewing['last_name'] . '</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Email:</span>
                    <span class="font-medium">' . $viewing['email'] . '</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Telefon:</span>
                    <span class="font-medium">' . $viewing['phone'] . '</span>
                </div>';

if ($viewing['user_id']) {
    $html .= '
                <div class="flex justify-between">
                    <span class="text-gray-600">Konto użytkownika:</span>
                    <span class="font-medium">Tak (ID: ' . $viewing['user_id'] . ')</span>
                </div>';
} else {
    $html .= '
                <div class="flex justify-between">
                    <span class="text-gray-600">Konto użytkownika:</span>
                    <span class="font-medium">Nie (gość)</span>
                </div>';
}

$html .= '
            </div>
        </div>
    </div>
    
    <div>
        <h3 class="text-lg font-semibold mb-3">Informacje o motocyklu</h3>
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-start space-x-4 mb-4">
                <img src="' . $motorcycle_image . '" alt="' . $viewing['motorcycle_title'] . '" class="h-20 w-32 object-cover rounded">
                <div class="flex-1">
                    <h4 class="font-medium">
                        <a href="../motorcycle.php?id=' . $viewing['motorcycle_id'] . '" target="_blank" class="hover:text-blue-600">
                            ' . $viewing['motorcycle_title'] . '
                        </a>
                    </h4>
                    <p class="text-sm text-gray-600">' . $viewing['brand'] . ' ' . $viewing['model'] . ' (' . $viewing['year'] . ')</p>
                    <p class="text-sm font-medium mt-1">' . number_format($viewing['price'], 0, ',', ' ') . ' zł</p>
                    <div class="mt-2 flex items-center">
                        <span class="text-xs text-gray-600 mr-2">Status motocykla:</span>
                        <span class="px-2 py-0.5 text-xs rounded ' . $motorcycle_status_class . '">
                            ' . $motorcycle_status_text . '
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>';

if (!empty($viewing['message'])) {
    $html .= '
    <div>
        <h3 class="text-lg font-semibold mb-3">Wiadomość od klienta</h3>
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm">' . nl2br(htmlspecialchars($viewing['message'])) . '</p>
        </div>
    </div>';
}

// Dodanie przycisków akcji
$html .= '
    <div class="pt-4 border-t flex flex-wrap justify-end gap-2">
        <button onclick="changeStatus(' . $viewing['id'] . ', \'' . $viewing['status'] . '\')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            Zmień status
        </button>';

// Przycisk kontaktu
$html .= '
        <a href="mailto:' . $viewing['email'] . '?subject=Rezerwacja%20oględzin%20motocykla%20' . $viewing['motorcycle_title'] . '" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
            Kontakt email
        </a>
    </div>
</div>';

// Zwróć wynik jako JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'html' => $html
]);
