<?php
// Strona kontaktowa
$page_title = "Kontakt - MotoShop";
require_once 'includes/config.php';

// Zmienne do przechowywania danych formularza i komunikatów o błędach
$name = $email = $subject = $message = "";
$name_err = $email_err = $subject_err = $message_err = "";
$form_submitted = false;

// Przetwarzanie formularza po wysłaniu
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Walidacja imienia i nazwiska
    if (empty(trim($_POST["name"]))) {
        $name_err = "Proszę podać imię i nazwisko.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    // Walidacja adresu email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Proszę podać adres email.";
    } else {
        $email = trim($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Proszę podać prawidłowy adres email.";
        }
    }
    
    // Walidacja tematu
    if (empty(trim($_POST["subject"]))) {
        $subject_err = "Proszę wybrać temat.";
    } else {
        $subject = trim($_POST["subject"]);
    }
    
    // Walidacja wiadomości
    if (empty(trim($_POST["message"]))) {
        $message_err = "Proszę wpisać wiadomość.";
    } else {
        $message = trim($_POST["message"]);
    }
    
    // Jeśli nie ma błędów, przetwórz formularz
    if (empty($name_err) && empty($email_err) && empty($subject_err) && empty($message_err)) {
        // Tutaj można dodać kod do zapisania wiadomości w bazie danych
        // lub wysłania jej na adres email
        
        // Przykład wysyłania maila (odkomentuj, jeśli serwer jest skonfigurowany)
        /*
        $to = "kontakt@motoshop.pl";
        $subject_mail = "Wiadomość ze strony: " . $subject;
        $headers = "From: " . $email . "\r\n";
        $headers .= "Reply-To: " . $email . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $mail_content = "<p><strong>Imię i nazwisko:</strong> " . $name . "</p>";
        $mail_content .= "<p><strong>Email:</strong> " . $email . "</p>";
        $mail_content .= "<p><strong>Temat:</strong> " . $subject . "</p>";
        $mail_content .= "<p><strong>Wiadomość:</strong><br>" . nl2br($message) . "</p>";
        
        mail($to, $subject_mail, $mail_content, $headers);
        */
        
        // Oznaczamy, że formularz został wysłany pomyślnie
        $form_submitted = true;
        
        // Resetuj zmienne
        $name = $email = $subject = $message = "";
    }
}

// Dodajemy header strony
include 'includes/header.php';
?>

<!-- JavaScript for contact page -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to show confirmation modal
        function showConfirmationModal() {
            document.getElementById('confirmationModal').classList.remove('hidden');
            document.getElementById('confirmationModal').classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
        
        // Function to hide confirmation modal
        window.hideConfirmationModal = function() {
            document.getElementById('confirmationModal').classList.remove('flex');
            document.getElementById('confirmationModal').classList.add('hidden');
            document.body.style.overflow = '';
        };
        
        // Show confirmation modal if form was submitted successfully
        <?php if($form_submitted): ?>
            showConfirmationModal();
        <?php endif; ?>
    });
</script>

<!-- Sekcja główna -->
<main class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-center mb-8">Kontakt</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-16">
        <!-- Formularz kontaktowy -->
        <div class="bg-white rounded-lg shadow-sm p-8">
            <h2 class="text-2xl font-bold mb-6">Napisz do nas</h2>
            <form id="contactForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Imię i nazwisko</label>
                        <input type="text" name="name" required value="<?php echo htmlspecialchars($name); ?>" class="w-full p-2 border rounded focus:outline-none focus:border-primary <?php echo !empty($name_err) ? 'border-red-500' : ''; ?>">
                        <?php if (!empty($name_err)): ?>
                            <span class="text-red-500 text-sm"><?php echo $name_err; ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($email); ?>" class="w-full p-2 border rounded focus:outline-none focus:border-primary <?php echo !empty($email_err) ? 'border-red-500' : ''; ?>">
                        <?php if (!empty($email_err)): ?>
                            <span class="text-red-500 text-sm"><?php echo $email_err; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <label class="block text-gray-700 mb-2">Temat</label>
                    <select name="subject" required class="w-full p-2 border rounded focus:outline-none focus:border-primary <?php echo !empty($subject_err) ? 'border-red-500' : ''; ?>">
                        <option value="" <?php echo empty($subject) ? 'selected' : ''; ?>>Wybierz temat</option>
                        <option value="sklep" <?php echo $subject == 'sklep' ? 'selected' : ''; ?>>Pytanie o sklep</option>
                        <option value="serwis" <?php echo $subject == 'serwis' ? 'selected' : ''; ?>>Pytanie o serwis</option>
                        <option value="motocykle" <?php echo $subject == 'motocykle' ? 'selected' : ''; ?>>Pytanie o motocykle używane</option>
                        <option value="inne" <?php echo $subject == 'inne' ? 'selected' : ''; ?>>Inne</option>
                    </select>
                    <?php if (!empty($subject_err)): ?>
                        <span class="text-red-500 text-sm"><?php echo $subject_err; ?></span>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-gray-700 mb-2">Wiadomość</label>
                    <textarea name="message" required rows="5" class="w-full p-2 border rounded focus:outline-none focus:border-primary <?php echo !empty($message_err) ? 'border-red-500' : ''; ?>"><?php echo htmlspecialchars($message); ?></textarea>
                    <?php if (!empty($message_err)): ?>
                        <span class="text-red-500 text-sm"><?php echo $message_err; ?></span>
                    <?php endif; ?>
                </div>
                <button type="submit" class="w-full bg-primary text-white py-3 rounded-button hover:bg-opacity-90 transition">
                    Wyślij wiadomość
                </button>
            </form>
        </div>

        <!-- Informacje kontaktowe -->
        <div class="space-y-8">
            <div class="bg-white rounded-lg shadow-sm p-8">
                <h2 class="text-2xl font-bold mb-6">Informacje kontaktowe</h2>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="w-10 h-10 flex items-center justify-center bg-primary/10 rounded-full mr-4">
                            <i class="ri-map-pin-line text-primary"></i>
                        </div>
                        <div>
                            <h3 class="font-bold mb-1">Adres</h3>
                            <p class="text-gray-600">ul. Motocyklowa 123</p>
                            <p class="text-gray-600">00-001 Warszawa</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="w-10 h-10 flex items-center justify-center bg-primary/10 rounded-full mr-4">
                            <i class="ri-phone-line text-primary"></i>
                        </div>
                        <div>
                            <h3 class="font-bold mb-1">Telefon</h3>
                            <p class="text-gray-600">+48 123 456 789</p>
                            <p class="text-gray-600">+48 987 654 321</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="w-10 h-10 flex items-center justify-center bg-primary/10 rounded-full mr-4">
                            <i class="ri-mail-line text-primary"></i>
                        </div>
                        <div>
                            <h3 class="font-bold mb-1">Email</h3>
                            <p class="text-gray-600">kontakt@motoshop.pl</p>
                            <p class="text-gray-600">serwis@motoshop.pl</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="w-10 h-10 flex items-center justify-center bg-primary/10 rounded-full mr-4">
                            <i class="ri-time-line text-primary"></i>
                        </div>
                        <div>
                            <h3 class="font-bold mb-1">Godziny otwarcia</h3>
                            <p class="text-gray-600">Poniedziałek - Piątek: 9:00 - 18:00</p>
                            <p class="text-gray-600">Sobota: 9:00 - 14:00</p>
                            <p class="text-gray-600">Niedziela: Zamknięte</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Media społecznościowe -->
            <div class="bg-white rounded-lg shadow-sm p-8">
                <h2 class="text-2xl font-bold mb-6">Media społecznościowe</h2>
                <div class="flex space-x-4">
                    <a href="https://www.facebook.com/groups/1310042155687488" target="_blank" class="w-12 h-12 flex items-center justify-center bg-primary/10 rounded-full hover:bg-primary/20 transition">
                        <i class="ri-facebook-fill text-primary text-xl"></i>
                    </a>
                    <a href="https://www.instagram.com/rogo_rek/?next=%2Findex.htm%2F" target="_blank" class="w-12 h-12 flex items-center justify-center bg-primary/10 rounded-full hover:bg-primary/20 transition">
                        <i class="ri-instagram-line text-primary text-xl"></i>
                    </a>
                    <a href="https://www.youtube.com/@GIGAHERTZ." target="_blank" class="w-12 h-12 flex items-center justify-center bg-primary/10 rounded-full hover:bg-primary/20 transition">
                        <i class="ri-youtube-line text-primary text-xl"></i>
                    </a>
                    <a href="#" class="w-12 h-12 flex items-center justify-center bg-primary/10 rounded-full hover:bg-primary/20 transition">
                        <i class="ri-messenger-line text-primary text-xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mapa -->
    <section class="mb-16">
        <h2 class="text-2xl font-bold text-center mb-8">Jak do nas trafić</h2>
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="aspect-w-16 aspect-h-9">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2443.650490016099!2d21.01222977677793!3d52.22967597195592!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x471ecc669a869f01%3A0x72f0be2a88ead3fc!2sWarszawa!5e0!3m2!1spl!2spl!4v1709912345678!5m2!1spl!2spl" 
                    class="w-full h-[400px] rounded-lg"
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </section>

    <!-- Modal potwierdzenia -->
    <div id="confirmationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-check-line text-green-500 text-3xl"></i>
            </div>
            <h2 class="text-2xl font-bold mb-4">Dziękujemy!</h2>
            <p class="text-gray-600 mb-6">Twoja wiadomość została wysłana. Skontaktujemy się z Tobą najszybciej jak to możliwe.</p>
            <button onclick="hideConfirmationModal()" class="bg-primary text-white py-2 px-6 rounded-button hover:bg-opacity-90 transition">
                Zamknij
            </button>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

