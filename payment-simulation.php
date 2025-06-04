<?php
$page_title = "Płatność | MotoShop";
require_once 'includes/config.php';

// Sprawdzanie czy ID zamówienia zostało przekazane
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    header("Location: index.php");
    exit;
}

$order_id = (int)$_GET['order_id'];

// Pobieranie danych zamówienia
$order_query = "SELECT * FROM orders WHERE id = $order_id";
$order_result = $conn->query($order_query);

if (!$order_result || $order_result->num_rows === 0) {
    header("Location: index.php");
    exit;
}

$order = $order_result->fetch_assoc();

// Obsługa płatności
$payment_status = '';
$payment_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'];
    $action = $_POST['action'] ?? '';
    
    if ($action === 'simulate_payment') {
        // Symulacja udanej płatności
        $payment_status = 'success';
        $payment_message = 'Płatność została zrealizowana pomyślnie.';
        
        // Aktualizacja statusu zamówienia
        $update_query = "UPDATE orders SET status = 'processing' WHERE id = $order_id";
        $conn->query($update_query);
        
        // Przekierowanie do strony potwierdzenia
        header("Location: order-confirmation.php?order_id=$order_id&payment=success");
        exit;
    }
}

include 'includes/header.php';
?>

<main>
    <div class="bg-gray-50 min-h-screen py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-2xl mx-auto">
                <!-- Logo i nagłówek -->
                <div class="text-center mb-8">
                    <div class="inline-block p-4 bg-white rounded-lg shadow-sm mb-4">
                        <span class="text-3xl font-['Pacifico'] text-primary">MotoShop</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Bezpieczna płatność</h1>
                    <p class="text-gray-600">Zamówienie nr: <?php echo $order['order_number']; ?></p>
                </div>

                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <!-- Pasek postępu -->
                    <div class="bg-primary h-1"></div>
                    
                    <div class="p-8">
                        <!-- Podsumowanie zamówienia -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-8">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-600">Kwota do zapłaty:</span>
                                <span class="text-2xl font-bold text-gray-900"><?php echo number_format($order['total_amount'], 2, ',', ' '); ?> zł</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="ri-shield-check-line mr-2"></i>
                                <span>Bezpieczna płatność SSL</span>
                            </div>
                        </div>

                        <!-- Modal Apple Pay -->
                        <div id="apple-pay-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
                            <div class="bg-white rounded-[20px] w-full max-w-[340px] mx-4 overflow-hidden">
                                <!-- Nagłówek Apple Pay -->
                                <div class="bg-black text-white p-4 flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="ri-apple-fill text-2xl mr-2"></i>
                                        <span class="font-medium">Apple Pay</span>
                                    </div>
                                    <button type="button" class="text-white hover:text-gray-300" onclick="closeApplePayModal()">
                                        <i class="ri-close-line text-xl"></i>
                                    </button>
                                </div>
                                
                                <!-- Zawartość modalu -->
                                <div class="p-6">
                                    <!-- Karta płatnicza -->
                                    <div class="bg-gradient-to-b from-[#1d1d1f] to-[#2d2d2f] rounded-[12px] p-4 mb-6 text-white">
                                        <div class="flex justify-between items-start mb-8">
                                            <div>
                                                <div class="text-sm opacity-80 mb-1">Karta</div>
                                                <div class="text-lg font-medium">•••• 4242</div>
                                            </div>
                                            <i class="ri-bank-card-2-line text-2xl"></i>
                                        </div>
                                        <div class="flex justify-between items-end">
                                            <div>
                                                <div class="text-sm opacity-80 mb-1">Właściciel</div>
                                                <div class="text-base">JAN KOWALSKI</div>
                                            </div>
                                            <div class="text-sm opacity-80">Ważna do 12/25</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Szczegóły płatności -->
                                    <div class="space-y-4 mb-6">
                                        <div class="flex justify-between items-center">
                                            <span class="text-[15px] text-[#1d1d1f]">MotoShop</span>
                                            <span class="text-[17px] font-medium text-[#1d1d1f]"><?php echo number_format($order['total_amount'], 2, ',', ' '); ?> zł</span>
                                        </div>
                                        <div class="text-[13px] text-[#86868b]">
                                            Zamówienie nr: <?php echo $order['order_number']; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Przycisk potwierdzenia -->
                                    <button type="button" id="confirm-apple-pay" class="w-full bg-black text-white py-3 rounded-full font-medium hover:bg-[#2d2d2f] transition-colors">
                                        Zapłać <?php echo number_format($order['total_amount'], 2, ',', ' '); ?> zł
                                    </button>

                                    <!-- Informacja o bezpieczeństwie -->
                                    <div class="flex items-center justify-center text-[13px] text-[#86868b] mt-4">
                                        <i class="ri-shield-check-line mr-1"></i>
                                        <span>Bezpieczna płatność</span>
                                    </div>

                                    <!-- Animacja Face ID -->
                                    <div id="face-id-animation" class="hidden">
                                        <div class="relative w-24 h-24 mx-auto mt-6">
                                            <!-- Zewnętrzny okrąg -->
                                            <div class="absolute inset-0 rounded-full border-2 border-[#007aff] animate-pulse"></div>
                                            <!-- Wewnętrzny okrąg -->
                                            <div class="absolute inset-2 rounded-full border-2 border-[#007aff] animate-pulse" style="animation-delay: 0.5s"></div>
                                            <!-- Ikona Face ID -->
                                            <div class="absolute inset-0 flex items-center justify-center">
                                                <div class="relative w-16 h-16">
                                                    <!-- Punkty skanowania -->
                                                    <div class="absolute top-0 left-1/2 w-1 h-1 bg-[#007aff] rounded-full animate-scan-dot" style="animation-delay: 0s"></div>
                                                    <div class="absolute top-1/2 right-0 w-1 h-1 bg-[#007aff] rounded-full animate-scan-dot" style="animation-delay: 0.2s"></div>
                                                    <div class="absolute bottom-0 left-1/2 w-1 h-1 bg-[#007aff] rounded-full animate-scan-dot" style="animation-delay: 0.4s"></div>
                                                    <div class="absolute top-1/2 left-0 w-1 h-1 bg-[#007aff] rounded-full animate-scan-dot" style="animation-delay: 0.6s"></div>
                                                    <!-- Linie skanowania -->
                                                    <div class="absolute inset-0 border border-[#007aff] rounded-full animate-scan-circle"></div>
                                                </div>
                                            </div>
                                            <!-- Linia skanowania -->
                                            <div class="absolute inset-x-0 top-1/2 h-0.5 bg-[#007aff] animate-scan"></div>
                                        </div>
                                        <p class="text-center text-[15px] text-[#1d1d1f] mt-4">Potwierdź płatność</p>
                                    </div>

                                    <!-- Animacja potwierdzenia -->
                                    <div id="success-animation" class="hidden">
                                        <div class="relative w-24 h-24 mx-auto mt-6">
                                            <!-- Okrąg potwierdzenia -->
                                            <div class="absolute inset-0 rounded-full border-2 border-[#34c759] animate-success-circle"></div>
                                            <!-- Znacznik potwierdzenia -->
                                            <div class="absolute inset-0 flex items-center justify-center">
                                                <i class="ri-check-line text-4xl text-[#34c759] animate-success-check"></i>
                                            </div>
                                        </div>
                                        <p class="text-center text-[15px] text-[#1d1d1f] mt-4">Płatność potwierdzona</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Google Pay -->
                        <div id="google-pay-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
                            <div class="bg-white rounded-[28px] w-full max-w-[340px] mx-4 overflow-hidden">
                                <!-- Nagłówek Google Pay -->
                                <div class="bg-white p-4 flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-[#4285F4] rounded-full flex items-center justify-center mr-2">
                                            <i class="ri-google-fill text-xl text-white"></i>
                                        </div>
                                        <span class="font-medium text-[#202124]">Google Pay</span>
                                    </div>
                                    <button type="button" class="text-[#5f6368] hover:text-[#202124]" onclick="closeGooglePayModal()">
                                        <i class="ri-close-line text-xl"></i>
                                    </button>
                                </div>
                                
                                <!-- Zawartość modalu -->
                                <div class="p-6">
                                    <!-- Karta płatnicza -->
                                    <div class="bg-gradient-to-r from-[#4285F4] to-[#34A853] rounded-[16px] p-4 mb-6 text-white">
                                        <div class="flex justify-between items-start mb-8">
                                            <div>
                                                <div class="text-sm opacity-80 mb-1">Karta</div>
                                                <div class="text-lg font-medium">•••• 4242</div>
                                            </div>
                                            <i class="ri-bank-card-2-line text-2xl"></i>
                                        </div>
                                        <div class="flex justify-between items-end">
                                            <div>
                                                <div class="text-sm opacity-80 mb-1">Właściciel</div>
                                                <div class="text-base">JAN KOWALSKI</div>
                                            </div>
                                            <div class="text-sm opacity-80">Ważna do 12/25</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Szczegóły płatności -->
                                    <div class="space-y-4 mb-6">
                                        <div class="flex justify-between items-center">
                                            <span class="text-[15px] text-[#202124]">MotoShop</span>
                                            <span class="text-[17px] font-medium text-[#202124]"><?php echo number_format($order['total_amount'], 2, ',', ' '); ?> zł</span>
                                        </div>
                                        <div class="text-[13px] text-[#5f6368]">
                                            Zamówienie nr: <?php echo $order['order_number']; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Przycisk potwierdzenia -->
                                    <button type="button" id="confirm-google-pay" class="w-full bg-[#4285F4] text-white py-3 rounded-full font-medium hover:bg-[#3367d6] transition-colors">
                                        Zapłać <?php echo number_format($order['total_amount'], 2, ',', ' '); ?> zł
                                    </button>

                                    <!-- Informacja o bezpieczeństwie -->
                                    <div class="flex items-center justify-center text-[13px] text-[#5f6368] mt-4">
                                        <i class="ri-shield-check-line mr-1"></i>
                                        <span>Bezpieczna płatność</span>
                                    </div>

                                    <!-- Animacja płatności -->
                                    <div id="google-pay-animation" class="hidden">
                                        <div class="w-full h-1 bg-[#4285F4] rounded-full animate-scan"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($payment_status === 'success'): ?>
                        <div class="bg-green-50 text-green-800 rounded-lg p-4 mb-6 flex items-center">
                            <i class="ri-checkbox-circle-fill text-xl mr-2"></i>
                            <?php echo $payment_message; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="space-y-6">
                            <?php if ($order['payment_method'] === 'online'): ?>
                            <!-- Symulacja płatności Przelewy24 -->
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <div class="bg-gray-50 p-4 border-b border-gray-200">
                                    <h2 class="text-lg font-semibold text-gray-900">Płatność online (Przelewy24)</h2>
                                </div>
                                <div class="p-6 space-y-4">
                                    <!-- Płatności mobilne -->
                                    <div class="mb-6">
                                        <label class="block text-sm font-medium text-gray-700 mb-3">Płatności mobilne:</label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <button type="button" class="mobile-pay-option flex items-center justify-center border border-gray-200 rounded-lg p-3 hover:border-primary hover:bg-gray-50 transition" data-pay="google">
                                                <div class="flex items-center">
                                                    <i class="ri-google-fill text-2xl text-gray-600 mr-2"></i>
                                                    <span class="text-sm font-medium">Google Pay</span>
                                                </div>
                                            </button>
                                            <button type="button" class="mobile-pay-option flex items-center justify-center border border-gray-200 rounded-lg p-3 hover:border-primary hover:bg-gray-50 transition" data-pay="apple">
                                                <div class="flex items-center">
                                                    <i class="ri-apple-fill text-2xl text-gray-600 mr-2"></i>
                                                    <span class="text-sm font-medium">Apple Pay</span>
                                                </div>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Separator -->
                                    <div class="relative my-6">
                                        <div class="absolute inset-0 flex items-center">
                                            <div class="w-full border-t border-gray-200"></div>
                                        </div>
                                        <div class="relative flex justify-center text-sm">
                                            <span class="px-2 bg-white text-gray-500">lub wybierz bank</span>
                                        </div>
                                    </div>

                                    <div class="mb-6">
                                        <label class="block text-sm font-medium text-gray-700 mb-3">Wybierz swój bank:</label>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                            <div class="bank-option cursor-pointer border border-gray-200 rounded-lg p-3 hover:border-primary hover:bg-gray-50 transition" data-bank="mbank">
                                                <div class="flex items-center">
                                                    <i class="ri-bank-line text-xl text-gray-600 mr-2"></i>
                                                    <span class="text-sm font-medium">mBank</span>
                                                </div>
                                            </div>
                                            <div class="bank-option cursor-pointer border border-gray-200 rounded-lg p-3 hover:border-primary hover:bg-gray-50 transition" data-bank="pko">
                                                <div class="flex items-center">
                                                    <i class="ri-bank-line text-xl text-gray-600 mr-2"></i>
                                                    <span class="text-sm font-medium">PKO BP</span>
                                                </div>
                                            </div>
                                            <div class="bank-option cursor-pointer border border-gray-200 rounded-lg p-3 hover:border-primary hover:bg-gray-50 transition" data-bank="ing">
                                                <div class="flex items-center">
                                                    <i class="ri-bank-line text-xl text-gray-600 mr-2"></i>
                                                    <span class="text-sm font-medium">ING</span>
                                                </div>
                                            </div>
                                            <div class="bank-option cursor-pointer border border-gray-200 rounded-lg p-3 hover:border-primary hover:bg-gray-50 transition" data-bank="santander">
                                                <div class="flex items-center">
                                                    <i class="ri-bank-line text-xl text-gray-600 mr-2"></i>
                                                    <span class="text-sm font-medium">Santander</span>
                                                </div>
                                            </div>
                                            <div class="bank-option cursor-pointer border border-gray-200 rounded-lg p-3 hover:border-primary hover:bg-gray-50 transition" data-bank="millennium">
                                                <div class="flex items-center">
                                                    <i class="ri-bank-line text-xl text-gray-600 mr-2"></i>
                                                    <span class="text-sm font-medium">Millennium</span>
                                                </div>
                                            </div>
                                            <div class="bank-option cursor-pointer border border-gray-200 rounded-lg p-3 hover:border-primary hover:bg-gray-50 transition" data-bank="alior">
                                                <div class="flex items-center">
                                                    <i class="ri-bank-line text-xl text-gray-600 mr-2"></i>
                                                    <span class="text-sm font-medium">Alior</span>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" id="bank-select" name="bank" value="">
                                    </div>
                                    
                                    <div id="payment-loading" class="hidden">
                                        <div class="flex flex-col items-center justify-center p-8">
                                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mb-4"></div>
                                            <p class="text-gray-600">Przetwarzanie płatności...</p>
                                        </div>
                                    </div>
                                    
                                    <form method="post" id="payment-form" class="mt-6">
                                        <input type="hidden" name="payment_method" value="online">
                                        <input type="hidden" name="action" value="simulate_payment">
                                        <button type="submit" id="payment-button" class="w-full bg-primary text-white py-4 px-4 rounded-lg font-medium hover:bg-opacity-90 transition flex items-center justify-center">
                                            <i class="ri-bank-card-line mr-2"></i>
                                            Zapłać <?php echo number_format($order['total_amount'], 2, ',', ' '); ?> zł
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <?php elseif ($order['payment_method'] === 'card'): ?>
                            <!-- Symulacja płatności kartą -->
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <div class="bg-gray-50 p-4 border-b border-gray-200">
                                    <h2 class="text-lg font-semibold text-gray-900">Płatność kartą</h2>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Numer karty</label>
                                            <div class="relative">
                                                <input type="text" id="card-number" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary pl-10" 
                                                       placeholder="1234 5678 9012 3456" maxlength="19" pattern="[0-9\s]{13,19}">
                                                <i class="ri-bank-card-line absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                            </div>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Data ważności</label>
                                                <div class="relative">
                                                    <input type="text" id="expiry-date" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary pl-10" 
                                                           placeholder="MM/RR" maxlength="5" pattern="(0[1-9]|1[0-2])\/([0-9]{2})">
                                                    <i class="ri-calendar-line absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Kod CVV</label>
                                                <div class="relative">
                                                    <input type="text" id="cvv" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary pl-10" 
                                                           placeholder="123" maxlength="3" pattern="[0-9]{3}">
                                                    <i class="ri-lock-line absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div id="payment-loading" class="hidden">
                                        <div class="flex flex-col items-center justify-center p-8">
                                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mb-4"></div>
                                            <p class="text-gray-600">Weryfikacja karty...</p>
                                        </div>
                                    </div>
                                    
                                    <form method="post" id="payment-form" class="mt-6">
                                        <input type="hidden" name="payment_method" value="card">
                                        <input type="hidden" name="action" value="simulate_payment">
                                        <button type="submit" id="payment-button" class="w-full bg-primary text-white py-4 px-4 rounded-lg font-medium hover:bg-opacity-90 transition flex items-center justify-center">
                                            <i class="ri-bank-card-line mr-2"></i>
                                            Zapłać <?php echo number_format($order['total_amount'], 2, ',', ' '); ?> zł
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <?php elseif ($order['payment_method'] === 'cash'): ?>
                            <!-- Informacja o płatności przy odbiorze -->
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <div class="bg-gray-50 p-4 border-b border-gray-200">
                                    <h2 class="text-lg font-semibold text-gray-900">Płatność przy odbiorze</h2>
                                </div>
                                <div class="p-6">
                                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                                        <div class="flex items-center text-gray-700 mb-2">
                                            <i class="ri-money-dollar-circle-line mr-2"></i>
                                            <span>Kwota do zapłaty przy odbiorze: <strong><?php echo number_format($order['total_amount'], 2, ',', ' '); ?> zł</strong></span>
                                        </div>
                                    </div>
                                    
                                    <div id="payment-loading" class="hidden">
                                        <div class="flex flex-col items-center justify-center p-8">
                                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mb-4"></div>
                                            <p class="text-gray-600">Przetwarzanie zamówienia...</p>
                                        </div>
                                    </div>
                                    
                                    <form method="post" id="payment-form">
                                        <input type="hidden" name="payment_method" value="cash">
                                        <input type="hidden" name="action" value="simulate_payment">
                                        <button type="submit" id="payment-button" class="w-full bg-primary text-white py-4 px-4 rounded-lg font-medium hover:bg-opacity-90 transition flex items-center justify-center">
                                            <i class="ri-check-line mr-2"></i>
                                            Potwierdź zamówienie
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <?php elseif ($order['payment_method'] === 'transfer'): ?>
                            <!-- Informacja o przelewie -->
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <div class="bg-gray-50 p-4 border-b border-gray-200">
                                    <h2 class="text-lg font-semibold text-gray-900">Przelew tradycyjny</h2>
                                </div>
                                <div class="p-6">
                                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                                        <div class="space-y-3">
                                            <div class="flex items-center">
                                                <i class="ri-building-line text-gray-400 mr-2"></i>
                                                <span><strong>Nazwa odbiorcy:</strong> MotoShop Sp. z o.o.</span>
                                            </div>
                                            <div class="flex items-center">
                                                <i class="ri-bank-card-line text-gray-400 mr-2"></i>
                                                <span><strong>Nr rachunku:</strong> 12 3456 7890 1234 5678 9012 3456</span>
                                            </div>
                                            <div class="flex items-center">
                                                <i class="ri-file-text-line text-gray-400 mr-2"></i>
                                                <span><strong>Tytuł przelewu:</strong> Zamówienie <?php echo $order['order_number']; ?></span>
                                            </div>
                                            <div class="flex items-center">
                                                <i class="ri-money-dollar-circle-line text-gray-400 mr-2"></i>
                                                <span><strong>Kwota:</strong> <?php echo number_format($order['total_amount'], 2, ',', ' '); ?> zł</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div id="payment-loading" class="hidden">
                                        <div class="flex flex-col items-center justify-center p-8">
                                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mb-4"></div>
                                            <p class="text-gray-600">Przetwarzanie zamówienia...</p>
                                        </div>
                                    </div>
                                    
                                    <form method="post" id="payment-form">
                                        <input type="hidden" name="payment_method" value="transfer">
                                        <input type="hidden" name="action" value="simulate_payment">
                                        <button type="submit" id="payment-button" class="w-full bg-primary text-white py-4 px-4 rounded-lg font-medium hover:bg-opacity-90 transition flex items-center justify-center">
                                            <i class="ri-check-line mr-2"></i>
                                            Potwierdź zamówienie
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Stopka z informacjami o bezpieczeństwie -->
                <div class="mt-8 text-center text-sm text-gray-500">
                    <div class="flex items-center justify-center space-x-4 mb-4">
                        <div class="flex items-center">
                            <i class="ri-shield-check-line mr-1"></i>
                            <span>Bezpieczna płatność</span>
                        </div>
                        <div class="flex items-center">
                            <i class="ri-lock-line mr-1"></i>
                            <span>Szyfrowanie SSL</span>
                        </div>
                        <div class="flex items-center">
                            <i class="ri-customer-service-2-line mr-1"></i>
                            <span>Wsparcie 24/7</span>
                        </div>
                    </div>
                    <p>© 2024 MotoShop. Wszelkie prawa zastrzeżone.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
// Skrypt JS do obsługi płatności
$extra_js = <<<EOT
<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentForm = document.getElementById('payment-form');
    const paymentButton = document.getElementById('payment-button');
    const paymentLoading = document.getElementById('payment-loading');
    const bankSelect = document.getElementById('bank-select');
    const cardNumber = document.getElementById('card-number');
    const expiryDate = document.getElementById('expiry-date');
    const cvv = document.getElementById('cvv');
    const applePayModal = document.getElementById('apple-pay-modal');
    const scanAnimation = document.getElementById('scan-animation');
    const googlePayModal = document.getElementById('google-pay-modal');
    const googlePayAnimation = document.getElementById('google-pay-animation');
    const confirmGooglePay = document.getElementById('confirm-google-pay');
    const confirmApplePay = document.getElementById('confirm-apple-pay');
    
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Sprawdzanie walidacji dla różnych metod płatności
            let isValid = true;
            
            if (bankSelect && !bankSelect.value) {
                alert('Wybierz bank');
                isValid = false;
            }
            
            if (cardNumber) {
                if (!cardNumber.value.match(/^[0-9\s]{13,19}$/)) {
                    alert('Wprowadź poprawny numer karty');
                    isValid = false;
                }
                
                if (!expiryDate.value.match(/^(0[1-9]|1[0-2])\/([0-9]{2})$/)) {
                    alert('Wprowadź poprawną datę ważności (MM/RR)');
                    isValid = false;
                }
                
                if (!cvv.value.match(/^[0-9]{3}$/)) {
                    alert('Wprowadź poprawny kod CVV');
                    isValid = false;
                }
            }
            
            if (isValid) {
                // Pokaż animację ładowania
                paymentButton.classList.add('hidden');
                paymentLoading.classList.remove('hidden');
                
                // Symulacja opóźnienia płatności
                setTimeout(() => {
                    paymentForm.submit();
                }, 2000);
            }
        });
    }
    
    // Formatowanie numeru karty
    if (cardNumber) {
        cardNumber.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            let formattedValue = '';
            
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            
            e.target.value = formattedValue;
        });
    }
    
    // Formatowanie daty ważności
    if (expiryDate) {
        expiryDate.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2);
            }
            
            e.target.value = value;
        });
    }
    
    // Obsługa wyboru banku
    const bankOptions = document.querySelectorAll('.bank-option');
    
    bankOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Usuń zaznaczenie z poprzednio wybranego banku
            bankOptions.forEach(opt => {
                opt.classList.remove('border-primary', 'bg-primary-50');
                opt.classList.add('border-gray-200');
            });
            
            // Zaznacz wybrany bank
            this.classList.remove('border-gray-200');
            this.classList.add('border-primary', 'bg-primary-50');
            
            // Ustaw wartość w ukrytym polu
            bankSelect.value = this.dataset.bank;
        });
    });
    
    // Obsługa płatności mobilnych
    const mobilePayOptions = document.querySelectorAll('.mobile-pay-option');
    
    mobilePayOptions.forEach(option => {
        option.addEventListener('click', function() {
            const payMethod = this.dataset.pay;
            
            if (payMethod === 'apple') {
                // Pokaż modal Apple Pay
                applePayModal.classList.remove('hidden');
                applePayModal.classList.add('flex');
            } else if (payMethod === 'google') {
                // Pokaż modal Google Pay
                googlePayModal.classList.remove('hidden');
                googlePayModal.classList.add('flex');
            }
        });
    });
    
    // Obsługa przycisku potwierdzenia Google Pay
    if (confirmGooglePay) {
        confirmGooglePay.addEventListener('click', function() {
            // Pokaż animację płatności
            googlePayAnimation.classList.remove('hidden');
            this.disabled = true;
            this.classList.add('opacity-50');
            
            // Symulacja płatności
            setTimeout(() => {
                googlePayAnimation.classList.add('hidden');
                closeGooglePayModal();
                paymentButton.classList.add('hidden');
                paymentLoading.classList.remove('hidden');
                
                // Symulacja opóźnienia płatności
                setTimeout(() => {
                    paymentForm.submit();
                }, 2000);
            }, 2000);
        });
    }
    
    // Obsługa przycisku potwierdzenia Apple Pay
    if (confirmApplePay) {
        confirmApplePay.addEventListener('click', function() {
            // Pokaż animację Face ID
            const faceIdAnimation = document.getElementById('face-id-animation');
            const successAnimation = document.getElementById('success-animation');
            faceIdAnimation.classList.remove('hidden');
            this.disabled = true;
            this.classList.add('opacity-50');
            
            // Symulacja skanowania
            setTimeout(() => {
                faceIdAnimation.classList.add('hidden');
                successAnimation.classList.remove('hidden');
                
                // Pokaż animację potwierdzenia
                setTimeout(() => {
                    successAnimation.classList.add('hidden');
                    closeApplePayModal();
                    paymentButton.classList.add('hidden');
                    paymentLoading.classList.remove('hidden');
                    
                    // Symulacja opóźnienia płatności
                    setTimeout(() => {
                        paymentForm.submit();
                    }, 2000);
                }, 1500);
            }, 3000);
        });
    }
    
    // Funkcja zamykania modalu Apple Pay
    window.closeApplePayModal = function() {
        applePayModal.classList.add('hidden');
        applePayModal.classList.remove('flex');
    }
    
    // Funkcja zamykania modalu Google Pay
    window.closeGooglePayModal = function() {
        googlePayModal.classList.add('hidden');
        googlePayModal.classList.remove('flex');
    }
});
</script>
EOT;

// Dodaj do sekcji CSS w head:
$extra_css = <<<EOT
<style>
@keyframes scan {
    0% {
        transform: translateY(-10px);
        opacity: 0;
    }
    50% {
        opacity: 1;
    }
    100% {
        transform: translateY(10px);
        opacity: 0;
    }
}

.animate-scan {
    animation: scan 1.5s ease-in-out infinite;
}

@keyframes scan-dot {
    0% {
        transform: scale(1);
        opacity: 0.3;
    }
    50% {
        transform: scale(1.5);
        opacity: 1;
    }
    100% {
        transform: scale(1);
        opacity: 0.3;
    }
}

.animate-scan-dot {
    animation: scan-dot 2s ease-in-out infinite;
}

@keyframes scan-circle {
    0% {
        transform: scale(0.8);
        opacity: 0.3;
    }
    50% {
        transform: scale(1);
        opacity: 1;
    }
    100% {
        transform: scale(0.8);
        opacity: 0.3;
    }
}

.animate-scan-circle {
    animation: scan-circle 2s ease-in-out infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.05);
        opacity: 0.8;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.animate-pulse {
    animation: pulse 2s ease-in-out infinite;
}

@keyframes success-circle {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.1);
        opacity: 1;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.animate-success-circle {
    animation: success-circle 0.5s ease-out forwards;
}

@keyframes success-check {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.2);
        opacity: 1;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.animate-success-check {
    animation: success-check 0.5s ease-out forwards;
    animation-delay: 0.2s;
}
</style>
EOT;

include 'includes/footer.php';
?> 