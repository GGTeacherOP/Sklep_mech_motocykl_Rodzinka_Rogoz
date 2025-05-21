<?php
// Inicjowanie sesji
if (!isset($_SESSION)) {
    session_start();
}

// Konfiguracja bazy danych
$host = 'localhost';
$db_name = 'motoshop_db';
$username = 'root';
$password = ''; // W XAMPP domyślnie puste hasło

// Połączenie z bazą danych
$conn = new mysqli($host, $username, $password, $db_name);

// Sprawdzenie połączenia
if ($conn->connect_error) {
    die("Błąd połączenia z bazą danych: " . $conn->connect_error);
}

// Ustawienie kodowania znaków
$conn->set_charset("utf8mb4");

// Funkcja zabezpieczająca dane wejściowe
function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(htmlspecialchars(trim($data)));
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

// Sesja jest już zainicjowana na początku pliku
?>
