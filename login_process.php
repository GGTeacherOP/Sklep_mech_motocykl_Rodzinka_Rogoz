<?php
session_start();
require_once 'includes/config.php';

// Ustawienie nagłówka odpowiedzi jako JSON
header('Content-Type: application/json');

// Sprawdzenie czy żądanie jest metodą POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Niedozwolona metoda żądania']);
    exit;
}

// Sprawdzenie czy jest to standardowe logowanie czy logowanie przez social media
if (isset($_POST['email']) && isset($_POST['password'])) {
    // Standardowe logowanie przez formularz
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    // Walidacja
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Proszę wypełnić wszystkie pola']);
        exit;
    }
    
    // Sprawdzenie czy użytkownik istnieje
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Użytkownik nie znaleziony
        echo json_encode(['success' => false, 'message' => 'Nieprawidłowy email lub hasło']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Weryfikacja hasła
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Nieprawidłowy email lub hasło']);
        exit;
    }
    
    // Ustawienie sesji użytkownika
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['logged_in'] = true;
    
    // Sprawdzenie roli użytkownika
    if ($user['role'] === 'admin') {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_name'] = $user['first_name'] . ' ' . $user['last_name'];
        
        echo json_encode([
            'success' => true, 
            'message' => 'Zalogowano pomyślnie', 
            'redirect' => 'admin/index.php',
            'role' => 'admin'
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'message' => 'Zalogowano pomyślnie', 
            'redirect' => 'account.html',
            'role' => 'user'
        ]);
    }
    
    exit;
}

// Pobranie danych z żądania
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Sprawdzenie wymaganych pól
if (!isset($data['provider']) || !isset($data['id_token'])) {
    echo json_encode(['success' => false, 'message' => 'Brakujące dane logowania']);
    exit;
}

$provider = $data['provider'];
$id_token = $data['id_token'];

// Obsługa różnych dostawców uwierzytelniania
switch ($provider) {
    case 'google':
        handleGoogleSignIn($id_token, $conn);
        break;
    case 'apple':
        handleAppleSignIn($id_token, $conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Nieznany dostawca uwierzytelniania']);
        exit;
}

/**
 * Obsługa logowania przez Google
 * 
 * @param string $id_token Token ID z Google
 * @param mysqli $conn Połączenie z bazą danych
 */
function handleGoogleSignIn($id_token, $conn) {
    // W produkcyjnym systemie należy zweryfikować token z Google API
    // https://developers.google.com/identity/sign-in/web/backend-auth
    
    try {
        // Najpierw próbujemy zdekodować token JWT bez weryfikacji (tylko w celach demonstracyjnych)
        // W prawdziwej implementacji należy użyć Google API do weryfikacji
        $token_parts = explode('.', $id_token);
        if (count($token_parts) !== 3) {
            throw new Exception("Nieprawidłowy format tokena");
        }
        
        // Dekodowanie części payload tokena
        $payload = json_decode(base64_decode(str_replace(
            ['-', '_'],
            ['+', '/'],
            $token_parts[1]
        )), true);
        
        if (!$payload) {
            throw new Exception("Nie można zdekodować tokena");
        }
        
        // Pobranie danych użytkownika z tokena
        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? null;
        $google_id = $payload['sub'] ?? null;
        
        if (!$email || !$google_id) {
            throw new Exception("Brak wymaganych danych w tokenie");
        }
        
        // Sprawdzenie czy użytkownik już istnieje w bazie
        $check_query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Użytkownik istnieje - aktualizujemy google_id jeśli nie był wcześniej ustawiony
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            
            if (empty($user['google_id'])) {
                $update_query = "UPDATE users SET google_id = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("si", $google_id, $user_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
        } else {
            // Użytkownik nie istnieje - tworzymy nowego
            $insert_query = "INSERT INTO users (email, first_name, last_name, google_id, created_at) VALUES (?, ?, ?, ?, NOW())";
            
            // Rozdziel imię i nazwisko, jeśli są dostępne
            $first_name = $name;
            $last_name = '';
            
            if ($name && strpos($name, ' ') !== false) {
                $name_parts = explode(' ', $name, 2);
                $first_name = $name_parts[0];
                $last_name = $name_parts[1];
            }
            
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ssss", $email, $first_name, $last_name, $google_id);
            $insert_stmt->execute();
            $user_id = $insert_stmt->insert_id;
            $insert_stmt->close();
        }
        
        // Ustawienie sesji użytkownika
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $name;
        $_SESSION['logged_in'] = true;
        
        // Sprawdzenie roli użytkownika
        $role_query = "SELECT role FROM users WHERE id = ?";
        $role_stmt = $conn->prepare($role_query);
        $role_stmt->bind_param("i", $user_id);
        $role_stmt->execute();
        $role_result = $role_stmt->get_result();
        $user_role = $role_result->fetch_assoc()['role'];
        
        if ($user_role === 'admin') {
            $_SESSION['admin_id'] = $user_id;
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_name'] = $name;
            
            echo json_encode([
                'success' => true, 
                'message' => 'Zalogowano pomyślnie przez Google', 
                'redirect' => 'admin/index.php',
                'role' => 'admin'
            ]);
        } else {
            echo json_encode([
                'success' => true, 
                'message' => 'Zalogowano pomyślnie przez Google', 
                'redirect' => 'account.html',
                'role' => 'user'
            ]);
        }
    } catch (Exception $e) {
        error_log('Google Sign-In error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Błąd uwierzytelniania: ' . $e->getMessage()]);
    }
}

/**
 * Obsługa logowania przez Apple
 * 
 * @param string $id_token Token ID z Apple
 * @param mysqli $conn Połączenie z bazą danych
 */
function handleAppleSignIn($id_token, $conn) {
    // W produkcyjnym systemie należy zweryfikować token z Apple API
    // https://developer.apple.com/documentation/sign_in_with_apple/sign_in_with_apple_rest_api/verifying_a_user
    
    try {
        // Najpierw próbujemy zdekodować token JWT bez weryfikacji (tylko w celach demonstracyjnych)
        // W prawdziwej implementacji należy użyć odpowiednich narzędzi kryptograficznych do weryfikacji
        $token_parts = explode('.', $id_token);
        if (count($token_parts) !== 3) {
            throw new Exception("Nieprawidłowy format tokena");
        }
        
        // Dekodowanie części payload tokena
        $payload = json_decode(base64_decode(str_replace(
            ['-', '_'],
            ['+', '/'],
            $token_parts[1]
        )), true);
        
        if (!$payload) {
            throw new Exception("Nie można zdekodować tokena");
        }
        
        // Pobranie danych użytkownika z tokena
        $email = $payload['email'] ?? null;
        $apple_id = $payload['sub'] ?? null;
        // Apple może nie udostępniać emaila przy każdym logowaniu, tylko przy pierwszym
        
        if (!$apple_id) {
            throw new Exception("Brak wymaganych danych w tokenie");
        }
        
        // Sprawdzenie czy użytkownik już istnieje w bazie (najpierw przez apple_id)
        $check_query = "SELECT * FROM users WHERE apple_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $apple_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0 && $email) {
            // Sprawdź też przez email, jeśli nie znaleziono po apple_id
            $check_query = "SELECT * FROM users WHERE email = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
        if ($result->num_rows > 0) {
            // Użytkownik istnieje - aktualizujemy apple_id jeśli nie było wcześniej ustawione
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            $user_email = $user['email'];
            
            if (empty($user['apple_id'])) {
                $update_query = "UPDATE users SET apple_id = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("si", $apple_id, $user_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
        } else {
            // Użytkownik nie istnieje - tworzymy nowego jeśli mamy email
            if (!$email) {
                throw new Exception("Brak adresu email w tokenie. Nie można utworzyć konta.");
            }
            
            $first_name = '';
            $last_name = '';
            
            // Sprawdzamy, czy mamy dane imienia/nazwiska w dedykowanym polu užytkownika
            if (isset($payload['name']) && isset($payload['name']['firstName'])) {
                $first_name = $payload['name']['firstName'];
                $last_name = $payload['name']['lastName'] ?? '';
            }
            
            $insert_query = "INSERT INTO users (email, first_name, last_name, apple_id, created_at) VALUES (?, ?, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ssss", $email, $first_name, $last_name, $apple_id);
            $insert_stmt->execute();
            $user_id = $insert_stmt->insert_id;
            $user_email = $email;
            $insert_stmt->close();
        }
        
        // Ustawienie sesji użytkownika
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_email'] = $user_email;
        $_SESSION['logged_in'] = true;
        
        // Sprawdzenie roli użytkownika
        $role_query = "SELECT role, first_name, last_name FROM users WHERE id = ?";
        $role_stmt = $conn->prepare($role_query);
        $role_stmt->bind_param("i", $user_id);
        $role_stmt->execute();
        $role_result = $role_stmt->get_result();
        $user_data = $role_result->fetch_assoc();
        $user_role = $user_data['role'];
        $user_name = $user_data['first_name'] . ' ' . $user_data['last_name'];
        $_SESSION['user_name'] = $user_name;
        
        if ($user_role === 'admin') {
            $_SESSION['admin_id'] = $user_id;
            $_SESSION['admin_email'] = $user_email;
            $_SESSION['admin_name'] = $user_name;
            
            echo json_encode([
                'success' => true, 
                'message' => 'Zalogowano pomyślnie przez Apple', 
                'redirect' => 'admin/index.php',
                'role' => 'admin'
            ]);
        } else {
            echo json_encode([
                'success' => true, 
                'message' => 'Zalogowano pomyślnie przez Apple', 
                'redirect' => 'account.html',
                'role' => 'user'
            ]);
        }
    } catch (Exception $e) {
        error_log('Apple Sign-In error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Błąd uwierzytelniania: ' . $e->getMessage()]);
    }
}
?>
