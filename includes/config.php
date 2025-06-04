<?php
// Inicjowanie sesji
if (!isset($_SESSION)) {
    session_start();
}

// Konfiguracja bazy danych
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'motoshop_db');

// Połączenie z bazą danych
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Sprawdzenie połączenia
if ($conn->connect_error) {
    die("Błąd połączenia z bazą danych: " . $conn->connect_error);
}

// Ustawienie kodowania znaków
$conn->set_charset("utf8mb4");

// Funkcja zabezpieczająca dane wejściowe
function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

// Funkcja generująca slug
function generateSlug($string) {
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    $string = preg_replace('/[^a-zA-Z0-9]/', '-', $string);
    $string = strtolower(trim($string, '-'));
    $string = preg_replace('/-+/', '-', $string);
    return $string;
}

// Funkcja sprawdzająca czy użytkownik jest zalogowany
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Funkcja sprawdzająca czy użytkownik jest administratorem
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

// Funkcja przekierowująca do innej strony
function redirect($url) {
    header("Location: $url");
    exit();
}

// Funkcja wyświetlająca komunikaty
function setMessage($message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

// Funkcja wyświetlająca komunikaty
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'info';
        $alertClass = 'bg-blue-100 text-blue-800';
        
        if ($type == 'success') {
            $alertClass = 'bg-green-100 text-green-800';
        } elseif ($type == 'error') {
            $alertClass = 'bg-red-100 text-red-800';
        } elseif ($type == 'warning') {
            $alertClass = 'bg-yellow-100 text-yellow-800';
        }
        
        echo "<div class='p-4 mb-4 rounded-lg $alertClass'>";
        echo $_SESSION['message'];
        echo "</div>";
        
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}

// Funkcja do pobierania głównego zdjęcia motocykla
function get_main_image($motorcycle_id) {
    global $conn;
    $query = "SELECT image_path FROM motorcycle_images WHERE motorcycle_id = ? AND is_main = 1 LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $motorcycle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['image_path'];
    }
    
    return 'assets/images/motorcycle-placeholder.jpg';
}

// Sesja jest już zainicjowana na początku pliku
?>
