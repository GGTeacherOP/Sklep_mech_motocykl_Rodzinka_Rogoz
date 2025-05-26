<?php
// Włączenie wyświetlania błędów PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ścieżka do głównego katalogu
$base_path = dirname(__DIR__);
require_once $base_path . '/includes/config.php';

echo "<h1>Diagnostyka tabeli administratorów</h1>";

// Sprawdzenie czy tabela admins istnieje
$check_table_query = "SHOW TABLES LIKE 'admins'";
$table_result = $conn->query($check_table_query);
$admins_table_exists = ($table_result && $table_result->num_rows > 0);

if ($admins_table_exists) {
    echo "<p style='color: green;'>✅ Tabela 'admins' istnieje.</p>";
    
    // Sprawdzenie struktury tabeli
    $structure_query = "DESCRIBE admins";
    $structure_result = $conn->query($structure_query);
    
    if ($structure_result && $structure_result->num_rows > 0) {
        echo "<h2>Struktura tabeli 'admins':</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Pole</th><th>Typ</th><th>Null</th><th>Klucz</th><th>Domyślnie</th><th>Extra</th></tr>";
        
        while ($column = $structure_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Nie można odczytać struktury tabeli 'admins'.</p>";
    }
    
    // Sprawdzenie zawartości tabeli
    $content_query = "SELECT id, username, name, email, role, created_at FROM admins";
    $content_result = $conn->query($content_query);
    
    if ($content_result && $content_result->num_rows > 0) {
        echo "<h2>Zawartość tabeli 'admins':</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nazwa użytkownika</th><th>Imię i nazwisko</th><th>Email</th><th>Rola</th><th>Data utworzenia</th></tr>";
        
        while ($admin = $content_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $admin['id'] . "</td>";
            echo "<td>" . $admin['username'] . "</td>";
            echo "<td>" . $admin['name'] . "</td>";
            echo "<td>" . $admin['email'] . "</td>";
            echo "<td>" . $admin['role'] . "</td>";
            echo "<td>" . $admin['created_at'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "<p>Liczba administratorów: " . $content_result->num_rows . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Tabela 'admins' jest pusta lub nie można odczytać danych.</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Tabela 'admins' nie istnieje!</p>";
    echo "<p>Kliknij <a href='init_admin_table.php' style='color: blue;'>tutaj</a>, aby utworzyć tabelę i dodać przykładowych administratorów.</p>";
}

// Sprawdzenie czy tabela users istnieje
$check_users_query = "SHOW TABLES LIKE 'users'";
$users_result = $conn->query($check_users_query);
$users_table_exists = ($users_result && $users_result->num_rows > 0);

if ($users_table_exists) {
    echo "<h2>Tabela 'users':</h2>";
    echo "<p style='color: green;'>✅ Tabela 'users' istnieje.</p>";
    
    // Sprawdzenie, czy w users istnieje pole role
    $check_role_query = "SHOW COLUMNS FROM users LIKE 'role'";
    $role_result = $conn->query($check_role_query);
    $role_exists = ($role_result && $role_result->num_rows > 0);
    
    if ($role_exists) {
        echo "<p style='color: green;'>✅ Kolumna 'role' istnieje w tabeli 'users'.</p>";
        
        // Sprawdzenie administratorów w tabeli users
        $admin_users_query = "SELECT id, email, first_name, last_name, role FROM users WHERE role = 'admin' OR role = 'owner'";
        $admin_users_result = $conn->query($admin_users_query);
        
        if ($admin_users_result && $admin_users_result->num_rows > 0) {
            echo "<h3>Administratorzy w tabeli 'users':</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Email</th><th>Imię</th><th>Nazwisko</th><th>Rola</th></tr>";
            
            while ($admin_user = $admin_users_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $admin_user['id'] . "</td>";
                echo "<td>" . $admin_user['email'] . "</td>";
                echo "<td>" . $admin_user['first_name'] . "</td>";
                echo "<td>" . $admin_user['last_name'] . "</td>";
                echo "<td>" . $admin_user['role'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            echo "<p>Liczba administratorów w tabeli users: " . $admin_users_result->num_rows . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Brak administratorów w tabeli 'users'.</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Kolumna 'role' nie istnieje w tabeli 'users'.</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Tabela 'users' nie istnieje.</p>";
}

echo "<h2>Ścieżki do ważnych plików:</h2>";
echo "<ul>";
echo "<li><a href='init_admin_table.php'>init_admin_table.php</a> - Tworzenie tabeli administratorów</li>";
echo "<li><a href='admin_users.php'>admin_users.php</a> - Zarządzanie administratorami</li>";
echo "<li><a href='login.php'>login.php</a> - Logowanie do panelu</li>";
echo "</ul>";

echo "<p><a href='index.php'>Powrót do panelu administracyjnego</a></p>";
?>
