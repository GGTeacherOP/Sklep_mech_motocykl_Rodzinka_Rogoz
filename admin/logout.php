<?php
session_start();

// Usunięcie wszystkich zmiennych sesji
$_SESSION = array();

// Zniszczenie sesji
if (session_destroy()) {
    // Przekierowanie do strony logowania
    header("Location: login.php");
    exit;
} else {
    echo "Wystąpił błąd podczas wylogowywania. <a href='login.php'>Wróć do logowania</a>";
}
?>
