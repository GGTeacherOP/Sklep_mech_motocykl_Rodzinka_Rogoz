<?php
// Skrypt do łączenia plików dokumentacji i generowania PDF

// Odczytaj zawartość trzech plików
$part1 = file_get_contents('dokumentacja_projektu.html');
$part2 = file_get_contents('dokumentacja_projektu_2.html');
$part3 = file_get_contents('dokumentacja_projektu_3.html');

// Znajdź </body> w pierwszym pliku i zastąp pozostałą treścią
$part1 = str_replace('</body>', '', $part1);
$part1 = str_replace('</html>', '', $part1);

// Ekstrahuj zawartość body z drugiego pliku
preg_match('/<body>(.*)<\/body>/s', $part2, $matches);
$body2 = isset($matches[1]) ? $matches[1] : '';

// Ekstrahuj zawartość body z trzeciego pliku
preg_match('/<body>(.*)<\/body>/s', $part3, $matches);
$body3 = isset($matches[1]) ? $matches[1] : '';

// Złącz wszystko razem
$fullDocument = $part1 . $body2 . $body3 . '</body></html>';

// Zapisz pełny dokument
file_put_contents('dokumentacja_pelna.html', $fullDocument);

// Przekieruj użytkownika do pełnego dokumentu
header('Location: dokumentacja_pelna.html');
