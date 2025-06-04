<?php
// Zabezpieczenie przed bezpośrednim dostępem do pliku
if (!defined('ADMIN_PANEL')) {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Panel administracyjny'; ?> | MotoShop</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <!-- Dodanie biblioteki Chart.js dla wykresów w analityce -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <!-- Dodanie Alpine.js dla interaktywnych komponentów UI -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.12.0/dist/cdn.min.js"></script>
    <style>
        /* Reset i podstawowe style */
        * {
            box-sizing: border-box;
        }
        body {
            overflow-x: hidden;
        }
        
        /* Główny kontener i układ */
        .admin-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }
        
        /* Pasek boczny */
        .admin-sidebar {
            width: 260px;
            flex-shrink: 0;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            transition: all 0.3s;
        }
        
        /* Główna zawartość */
        .admin-content {
            flex-grow: 1;
            margin-left: 260px; /* Taka sama szerokość jak pasek boczny */
            width: calc(100% - 260px);
            transition: all 0.3s;
        }
        
        /* Responsywność */
        @media (max-width: 1024px) {
            .admin-sidebar {
                margin-left: -260px;
                box-shadow: 0 0 15px rgba(0,0,0,0.1);
            }
            .admin-sidebar.show {
                margin-left: 0;
            }
            .admin-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="admin-wrapper">
        <!-- Mobile sidebar toggle -->
        <div class="fixed top-0 left-0 z-20 lg:hidden">
            <button type="button" id="sidebar-toggle" class="p-3 m-2 bg-white text-blue-600 rounded-lg shadow">
                <i class="ri-menu-line text-xl"></i>
            </button>
        </div>
