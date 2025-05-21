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

$page_title = "Zarządzanie Kategoriami";

// Obsługa dodawania nowej kategorii
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = sanitize($_POST['name']);
        $slug = sanitize($_POST['slug']);
        $description = sanitize($_POST['description']);
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        // Walidacja
        if (empty($name)) {
            $error = "Nazwa kategorii jest wymagana!";
        } elseif (empty($slug)) {
            // Generujemy slug z nazwy, jeśli nie został podany
            $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9\-]/', '', $name)));
        }

        // Sprawdzenie unikalności sluga
        $check_query = "SELECT id FROM categories WHERE slug = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Kategoria o podanym identyfikatorze URL już istnieje!";
        }

        if (empty($error)) {
            // Dodawanie kategorii
            if ($parent_id) {
                $query = "INSERT INTO categories (name, slug, description, parent_id) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssi", $name, $slug, $description, $parent_id);
            } else {
                $query = "INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sss", $name, $slug, $description);
            }

            if ($stmt->execute()) {
                $success = "Kategoria została dodana pomyślnie!";
            } else {
                $error = "Błąd podczas dodawania kategorii: " . $conn->error;
            }
        }
    } 
    // Obsługa usuwania kategorii
    elseif (isset($_POST['delete_category'])) {
        $category_id = (int)$_POST['category_id'];
        
        // Sprawdzenie, czy kategoria ma przypisane produkty
        $check_products_query = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
        $stmt = $conn->prepare($check_products_query);
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $error = "Nie można usunąć kategorii, ponieważ ma przypisane produkty. Najpierw zmień kategorię tych produktów.";
        } else {
            // Sprawdzenie, czy kategoria ma podkategorie
            $check_subcategories_query = "SELECT COUNT(*) as count FROM categories WHERE parent_id = ?";
            $stmt = $conn->prepare($check_subcategories_query);
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                $error = "Nie można usunąć kategorii, ponieważ ma podkategorie. Najpierw usuń podkategorie.";
            } else {
                // Usuwanie kategorii
                $delete_query = "DELETE FROM categories WHERE id = ?";
                $stmt = $conn->prepare($delete_query);
                $stmt->bind_param("i", $category_id);
                
                if ($stmt->execute()) {
                    $success = "Kategoria została usunięta pomyślnie!";
                } else {
                    $error = "Błąd podczas usuwania kategorii: " . $conn->error;
                }
            }
        }
    }
    // Obsługa edycji kategorii
    elseif (isset($_POST['edit_category'])) {
        $category_id = (int)$_POST['category_id'];
        $name = sanitize($_POST['name']);
        $slug = sanitize($_POST['slug']);
        $description = sanitize($_POST['description']);
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        
        // Walidacja
        if (empty($name)) {
            $error = "Nazwa kategorii jest wymagana!";
        } elseif (empty($slug)) {
            // Generujemy slug z nazwy, jeśli nie został podany
            $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9\-]/', '', $name)));
        }
        
        // Sprawdzenie unikalności sluga (z wyłączeniem edytowanej kategorii)
        $check_query = "SELECT id FROM categories WHERE slug = ? AND id != ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("si", $slug, $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Kategoria o podanym identyfikatorze URL już istnieje!";
        }
        
        // Sprawdzenie, czy parent_id nie jest równy id kategorii (zapobieganie cyklicznemu zagnieżdżeniu)
        if ($parent_id == $category_id) {
            $error = "Kategoria nie może być podkategorią samej siebie!";
        }
        
        if (empty($error)) {
            // Aktualizacja kategorii
            if ($parent_id) {
                $query = "UPDATE categories SET name = ?, slug = ?, description = ?, parent_id = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssii", $name, $slug, $description, $parent_id, $category_id);
            } else {
                $query = "UPDATE categories SET name = ?, slug = ?, description = ?, parent_id = NULL WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssi", $name, $slug, $description, $category_id);
            }
            
            if ($stmt->execute()) {
                $success = "Kategoria została zaktualizowana pomyślnie!";
            } else {
                $error = "Błąd podczas aktualizacji kategorii: " . $conn->error;
            }
        }
    }
}

// Pobieranie kategorii
$categories_query = "SELECT c.*, p.name as parent_name 
                    FROM categories c 
                    LEFT JOIN categories p ON c.parent_id = p.id 
                    ORDER BY COALESCE(c.parent_id, c.id), c.name";
$categories_result = $conn->query($categories_query);
$categories = [];

if ($categories_result && $categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Pobieranie kategorii głównych (dla selecta przy dodawaniu/edycji kategorii)
$parent_categories_query = "SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name";
$parent_categories_result = $conn->query($parent_categories_query);
$parent_categories = [];

if ($parent_categories_result && $parent_categories_result->num_rows > 0) {
    while ($row = $parent_categories_result->fetch_assoc()) {
        $parent_categories[] = $row;
    }
}

// Kategoria do edycji
$edit_category = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_query = "SELECT * FROM categories WHERE id = ?";
    $stmt = $conn->prepare($edit_query);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_result = $stmt->get_result();
    
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_category = $edit_result->fetch_assoc();
    }
}

include 'includes/header.php';
?>

<div class="admin-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Główna zawartość -->
    <div class="admin-content ml-0 lg:ml-260 p-4 md:p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Zarządzanie Kategoriami</h1>
        <p class="text-gray-600">Dodawaj, edytuj i usuwaj kategorie produktów</p>
    </div>
    
    <?php if (!empty($success)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p><?php echo $success; ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <p><?php echo $error; ?></p>
    </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Formularz dodawania/edycji kategorii -->
        <div class="md:col-span-1">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4"><?php echo $edit_category ? 'Edytuj kategorię' : 'Dodaj nową kategorię'; ?></h2>
                
                <form method="post" action="categories.php">
                    <?php if ($edit_category): ?>
                    <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <label for="name" class="block text-gray-700 font-medium mb-2">Nazwa <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" value="<?php echo $edit_category['name'] ?? ''; ?>" class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="slug" class="block text-gray-700 font-medium mb-2">Identyfikator URL</label>
                        <input type="text" id="slug" name="slug" value="<?php echo $edit_category['slug'] ?? ''; ?>" class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                        <p class="text-gray-500 text-sm mt-1">Pozostaw puste, aby wygenerować automatycznie z nazwy</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="block text-gray-700 font-medium mb-2">Opis</label>
                        <textarea id="description" name="description" rows="3" class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500"><?php echo $edit_category['description'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="parent_id" class="block text-gray-700 font-medium mb-2">Kategoria nadrzędna</label>
                        <select id="parent_id" name="parent_id" class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                            <option value="">-- Brak (kategoria główna) --</option>
                            <?php foreach ($parent_categories as $parent): ?>
                            <?php if ($edit_category && $parent['id'] == $edit_category['id']) continue; // Pomijamy aktualnie edytowaną kategorię ?>
                            <option value="<?php echo $parent['id']; ?>" <?php echo ($edit_category && $parent['id'] == $edit_category['parent_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($parent['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex justify-between">
                        <?php if ($edit_category): ?>
                        <button type="submit" name="edit_category" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 transition">
                            Aktualizuj kategorię
                        </button>
                        <a href="categories.php" class="bg-gray-300 text-gray-700 py-2 px-4 rounded hover:bg-gray-400 transition">
                            Anuluj
                        </a>
                        <?php else: ?>
                        <button type="submit" name="add_category" class="bg-green-500 text-white py-2 px-4 rounded hover:bg-green-600 transition">
                            Dodaj kategorię
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Lista kategorii -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-semibold">Lista kategorii</h2>
                </div>
                
                <?php if (empty($categories)): ?>
                <div class="p-6 text-center text-gray-500">
                    <i class="ri-price-tag-3-line text-4xl mb-2"></i>
                    <p>Brak kategorii. Dodaj pierwszą kategorię za pomocą formularza.</p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nazwa</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Identyfikator URL</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategoria nadrzędna</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($categories as $category): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($category['name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                                    <?php echo htmlspecialchars($category['slug']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                                    <?php echo !empty($category['parent_name']) ? htmlspecialchars($category['parent_name']) : '<span class="text-gray-400">-</span>'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="categories.php?edit=<?php echo $category['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                            <i class="ri-edit-line"></i> Edytuj
                                        </a>
                                        <form method="post" action="categories.php" class="inline" onsubmit="return confirm('Czy na pewno chcesz usunąć tę kategorię?');">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" name="delete_category" class="text-red-600 hover:text-red-900 bg-transparent border-0 cursor-pointer">
                                                <i class="ri-delete-bin-line"></i> Usuń
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Automatyczne generowanie sluga z nazwy kategorii
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    
    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            // Tylko jeśli pole slug jest puste lub nie było ręcznie modyfikowane
            if (slugInput.value === '' || slugInput.dataset.autoGenerated === 'true') {
                const slug = this.value.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '') // Usuwanie znaków specjalnych
                    .replace(/\s+/g, '-') // Zamiana spacji na myślniki
                    .replace(/-+/g, '-'); // Usuwanie wielu myślników obok siebie
                
                slugInput.value = slug;
                slugInput.dataset.autoGenerated = 'true';
            }
        });
        
        // Gdy użytkownik ręcznie modyfikuje slug, wyłączamy autogenerowanie
        slugInput.addEventListener('input', function() {
            slugInput.dataset.autoGenerated = 'false';
        });
    }
});
</script>

<?php
include 'includes/footer.php';
?>
