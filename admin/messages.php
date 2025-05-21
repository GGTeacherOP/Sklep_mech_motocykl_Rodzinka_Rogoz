<?php
session_start();

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Stała określająca, że jesteśmy w panelu administracyjnym
define('ADMIN_PANEL', true);

// Ścieżka do głównego katalogu
$base_path = dirname(__DIR__);
require_once $base_path . '/includes/config.php';

$page_title = "Wiadomości";

// Obsługa zmiany statusu wiadomości
if (isset($_GET['action']) && $_GET['action'] === 'mark_read' && isset($_GET['id'])) {
    $message_id = (int)$_GET['id'];
    
    $update_query = "UPDATE contact_messages SET status = 'read' WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $message_id);
    
    if ($stmt->execute()) {
        setMessage('Wiadomość została oznaczona jako przeczytana.', 'success');
    } else {
        setMessage('Wystąpił błąd podczas aktualizacji statusu wiadomości.', 'error');
    }
    
    header("Location: messages.php");
    exit;
}

// Obsługa usuwania wiadomości
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $message_id = (int)$_GET['id'];
    
    $delete_query = "DELETE FROM contact_messages WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $message_id);
    
    if ($stmt->execute()) {
        setMessage('Wiadomość została usunięta.', 'success');
    } else {
        setMessage('Wystąpił błąd podczas usuwania wiadomości.', 'error');
    }
    
    header("Location: messages.php");
    exit;
}

// Pobieranie filtra
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Konstruowanie zapytania w zależności od filtra
$where_clause = '';
if ($filter === 'unread') {
    $where_clause = "WHERE status = 'new'";
} else if ($filter === 'read') {
    $where_clause = "WHERE status = 'read'";
}

// Pobieranie wiadomości
$messages_query = "SELECT * FROM contact_messages {$where_clause} ORDER BY created_at DESC";
$result = $conn->query($messages_query);
$messages = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}

// Pobieranie liczby nieprzeczytanych wiadomości
$unread_query = "SELECT COUNT(*) as unread_count FROM contact_messages WHERE status = 'new'";
$unread_result = $conn->query($unread_query);
$unread_count = 0;

if ($unread_result && $unread_result->num_rows > 0) {
    $unread_data = $unread_result->fetch_assoc();
    $unread_count = $unread_data['unread_count'];
}

// Dołączenie nagłówka i sidebar
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Główna zawartość -->
<div class="admin-content ml-0 lg:ml-260 p-4 md:p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Wiadomości od klientów</h1>
            <p class="text-gray-600">Zarządzaj wiadomościami z formularza kontaktowego</p>
        </div>
        
        <div class="mt-4 md:mt-0 flex space-x-2">
            <a href="?filter=all" class="px-4 py-2 rounded-md <?php echo $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                Wszystkie
            </a>
            <a href="?filter=unread" class="px-4 py-2 rounded-md <?php echo $filter === 'unread' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                Nieprzeczytane <span class="inline-block bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $unread_count; ?></span>
            </a>
            <a href="?filter=read" class="px-4 py-2 rounded-md <?php echo $filter === 'read' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                Przeczytane
            </a>
        </div>
    </div>
    
    <?php if (empty($messages)): ?>
    <div class="bg-white rounded-lg shadow-sm p-6 text-center">
        <div class="flex flex-col items-center justify-center py-12">
            <i class="ri-mail-line text-5xl text-gray-400 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Brak wiadomości</h3>
            <p class="text-gray-600">Nie znaleziono żadnych wiadomości w tej kategorii.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Status</th>
                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Imię i nazwisko</th>
                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Email</th>
                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Temat</th>
                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Data</th>
                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Akcje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($messages as $message): ?>
                    <tr class="hover:bg-gray-50 <?php echo $message['status'] === 'new' ? 'font-semibold' : ''; ?>">
                        <td class="py-3 px-4">
                            <?php if ($message['status'] === 'new'): ?>
                            <span class="inline-block w-2 h-2 bg-blue-600 rounded-full mr-2"></span>
                            <span class="text-sm text-blue-600">Nowa</span>
                            <?php else: ?>
                            <span class="inline-block w-2 h-2 bg-gray-400 rounded-full mr-2"></span>
                            <span class="text-sm text-gray-400">Przeczytana</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($message['name']); ?></td>
                        <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($message['email']); ?></td>
                        <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($message['subject']); ?></td>
                        <td class="py-3 px-4 text-sm"><?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?></td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <button class="text-blue-600 hover:text-blue-800" 
                                        onclick="showMessageModal(<?php echo htmlspecialchars(json_encode($message)); ?>)">
                                    <i class="ri-eye-line"></i>
                                </button>
                                
                                <?php if ($message['status'] === 'new'): ?>
                                <a href="?action=mark_read&id=<?php echo $message['id']; ?>" class="text-green-600 hover:text-green-800">
                                    <i class="ri-check-double-line"></i>
                                </a>
                                <?php endif; ?>
                                
                                <a href="?action=delete&id=<?php echo $message['id']; ?>" 
                                   onclick="return confirm('Czy na pewno chcesz usunąć tę wiadomość?')"
                                   class="text-red-600 hover:text-red-800">
                                    <i class="ri-delete-bin-line"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal do wyświetlania treści wiadomości -->
<div id="messageModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4">
        <div class="border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-semibold" id="modalTitle">Treść wiadomości</h3>
            <button onclick="closeMessageModal()" class="text-gray-500 hover:text-gray-700">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Imię i nazwisko</p>
                    <p class="font-medium" id="modalName"></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">Email</p>
                    <p class="font-medium" id="modalEmail"></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">Temat</p>
                    <p class="font-medium" id="modalSubject"></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">Data</p>
                    <p class="font-medium" id="modalDate"></p>
                </div>
            </div>
            
            <div class="mb-6">
                <p class="text-sm text-gray-500 mb-1">Wiadomość</p>
                <div class="p-4 bg-gray-50 rounded-md" id="modalMessage"></div>
            </div>
            
            <div class="flex justify-end space-x-2">
                <button onclick="closeMessageModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                    Zamknij
                </button>
                <a href="#" id="modalMarkAsRead" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Oznacz jako przeczytane
                </a>
                <a href="#" id="modalDelete" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                   onclick="return confirm('Czy na pewno chcesz usunąć tę wiadomość?')">
                    Usuń
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function showMessageModal(message) {
    // Wypełnienie danych w modalu
    document.getElementById('modalName').textContent = message.name;
    document.getElementById('modalEmail').textContent = message.email;
    document.getElementById('modalSubject').textContent = message.subject;
    document.getElementById('modalMessage').textContent = message.message;
    document.getElementById('modalDate').textContent = new Date(message.created_at).toLocaleString('pl-PL');
    
    // Aktualizacja linków
    document.getElementById('modalMarkAsRead').href = '?action=mark_read&id=' + message.id;
    document.getElementById('modalDelete').href = '?action=delete&id=' + message.id;
    
    // Pokazanie/ukrycie przycisku "Oznacz jako przeczytane"
    if (message.status === 'new') {
        document.getElementById('modalMarkAsRead').style.display = 'block';
    } else {
        document.getElementById('modalMarkAsRead').style.display = 'none';
    }
    
    // Pokazanie modalu
    document.getElementById('messageModal').classList.remove('hidden');
    
    // Automatyczne oznaczenie jako przeczytane po otwarciu
    if (message.status === 'new') {
        fetch('?action=mark_read&id=' + message.id, {
            method: 'GET'
        });
    }
}

function closeMessageModal() {
    document.getElementById('messageModal').classList.add('hidden');
    
    // Po zamknięciu modalu odśwież stronę, aby zaktualizować status wiadomości
    setTimeout(() => {
        window.location.reload();
    }, 300);
}
</script>

<?php
include 'includes/footer.php';
?>
