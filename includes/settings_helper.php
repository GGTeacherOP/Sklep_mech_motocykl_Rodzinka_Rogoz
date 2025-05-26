<?php
/**
 * Helper do obsługi ustawień sklepu
 * Plik zawiera funkcje do pobierania i zarządzania ustawieniami z bazy danych
 */

/**
 * Pobiera wartość ustawienia z bazy danych
 * 
 * @param string $key Klucz ustawienia
 * @param mixed $default Domyślna wartość, jeśli ustawienie nie istnieje
 * @param bool $force_refresh Wymuś odświeżenie z bazy danych zamiast korzystać z pamięci podręcznej
 * @return mixed Wartość ustawienia lub wartość domyślna
 */
function get_setting($key, $default = null, $force_refresh = false) {
    global $conn;
    static $settings_cache = [];
    
    // Jeśli wymuszone odświeżenie lub klucz nie istnieje w pamięci podręcznej
    if ($force_refresh || !isset($settings_cache[$key])) {
        if (!$conn) {
            return $default;
        }
        
        // Sprawdź, czy tabela ustawień istnieje
        $check_table_query = "SHOW TABLES LIKE 'shop_settings'";
        $table_exists = $conn->query($check_table_query)->num_rows > 0;
        
        if (!$table_exists) {
            return $default;
        }
        
        // Pobierz ustawienie z bazy danych
        $query = "SELECT setting_value FROM shop_settings WHERE setting_key = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $settings_cache[$key] = $row['setting_value'];
        } else {
            $settings_cache[$key] = $default;
        }
    }
    
    return $settings_cache[$key];
}

/**
 * Pobiera wszystkie ustawienia z określonej grupy
 * 
 * @param string $group Grupa ustawień
 * @param bool $only_public Pobierz tylko publiczne ustawienia
 * @return array Tablica ustawień
 */
function get_settings_group($group, $only_public = false) {
    global $conn;
    
    if (!$conn) {
        return [];
    }
    
    // Sprawdź, czy tabela ustawień istnieje
    $check_table_query = "SHOW TABLES LIKE 'shop_settings'";
    $table_exists = $conn->query($check_table_query)->num_rows > 0;
    
    if (!$table_exists) {
        return [];
    }
    
    // Budowa zapytania
    $query = "SELECT setting_key, setting_value FROM shop_settings WHERE setting_group = ?";
    
    if ($only_public) {
        $query .= " AND is_public = 1";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $group);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $settings = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    return $settings;
}

/**
 * Aktualizuje wartość ustawienia w bazie danych
 * 
 * @param string $key Klucz ustawienia
 * @param mixed $value Nowa wartość
 * @return bool Czy operacja się powiodła
 */
function update_setting($key, $value) {
    global $conn;
    
    if (!$conn) {
        return false;
    }
    
    // Sprawdź, czy tabela ustawień istnieje
    $check_table_query = "SHOW TABLES LIKE 'shop_settings'";
    $table_exists = $conn->query($check_table_query)->num_rows > 0;
    
    if (!$table_exists) {
        return false;
    }
    
    // Sprawdź, czy ustawienie istnieje
    $check_query = "SELECT id FROM shop_settings WHERE setting_key = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $key);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result && $check_result->num_rows > 0) {
        // Aktualizacja istniejącego ustawienia
        $update_query = "UPDATE shop_settings SET setting_value = ? WHERE setting_key = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ss", $value, $key);
        return $update_stmt->execute();
    } else {
        // Ustawienie nie istnieje
        return false;
    }
}

/**
 * Sprawdza, czy funkcjonalność jest włączona na podstawie ustawienia
 * 
 * @param string $key Klucz ustawienia
 * @param bool $default Domyślna wartość, jeśli ustawienie nie istnieje
 * @return bool Czy funkcjonalność jest włączona
 */
function is_feature_enabled($key, $default = false) {
    $value = get_setting($key, $default ? '1' : '0');
    return $value == '1';
}
