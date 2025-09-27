<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'User Panel' ?> - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom animations for Tailwind */
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        .slide-in { animation: slideIn 0.3s ease-out; }
        .bounce-in { animation: bounceIn 0.6s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes bounceIn { 0% { opacity: 0; transform: scale(0.3); } 50% { opacity: 1; transform: scale(1.05); } 70% { transform: scale(0.9); } 100% { opacity: 1; transform: scale(1); } }
        /* Skeleton Loading */
        .skeleton { background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: loading 1.5s infinite; }
        @keyframes loading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        /* Modal Backdrop Fix */
        .modal-backdrop { position: fixed; top: 0; left: 0; z-index: 1040; width: 100vw; height: 100vh; background-color: #000; }
        .modal-backdrop.fade { opacity: 0; }
        .modal-backdrop.show { opacity: 0.5; }
        .modal { z-index: 1050; }
        .modal.show { display: block !important; }
        body.modal-open { overflow: hidden; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    <!-- Main Layout Container -->
    <div class="flex h-screen">
        <?php include 'includes/sidebar.php'; ?>
        <!-- Main Content Area -->
        <div class="flex-1 ml-64">
            <div class="h-full overflow-y-auto">
