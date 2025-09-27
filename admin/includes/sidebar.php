<?php
/**
 * Admin Sidebar - Konsisten untuk semua halaman admin
 */

// Get current page name
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<nav class="fixed left-0 top-0 h-full w-64 bg-gradient-to-b from-primary-600 to-primary-800 shadow-xl z-40">
    <div class="p-6">
        <div class="text-center mb-8">
            <h4 class="text-white text-xl font-bold">
                <i class="fas fa-cog mr-2"></i> Admin Panel
            </h4>
        </div>
        <ul class="space-y-2">
            <li>
                <a class="flex items-center px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?= $currentPage === 'index.php' ? 'bg-white bg-opacity-20 shadow-lg' : '' ?>" href="index.php">
                    <i class="fas fa-tachometer-alt mr-3 w-5"></i> Dashboard
                </a>
            </li>
            <li>
                <a class="flex items-center px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?= $currentPage === 'api-keys.php' ? 'bg-white bg-opacity-20 shadow-lg' : '' ?>" href="api-keys.php">
                    <i class="fas fa-key mr-3 w-5"></i> API Keys
                </a>
            </li>
            <li>
                <a class="flex items-center px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?= $currentPage === 'analytics.php' ? 'bg-white bg-opacity-20 shadow-lg' : '' ?>" href="analytics.php">
                    <i class="fas fa-chart-line mr-3 w-5"></i> Analytics
                </a>
            </li>
            <li>
                <a class="flex items-center px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?= $currentPage === 'logs.php' ? 'bg-white bg-opacity-20 shadow-lg' : '' ?>" href="logs.php">
                    <i class="fas fa-list mr-3 w-5"></i> API Logs
                </a>
            </li>
            <li>
                <a class="flex items-center px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?= $currentPage === 'notifications.php' ? 'bg-white bg-opacity-20 shadow-lg' : '' ?>" href="notifications.php">
                    <i class="fas fa-bell mr-3 w-5"></i> Notifications
                </a>
            </li>
            <li>
                <a class="flex items-center px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?= $currentPage === 'packages.php' ? 'bg-white bg-opacity-20 shadow-lg' : '' ?>" href="packages.php">
                    <i class="fas fa-box mr-3 w-5"></i> Packages
                </a>
            </li>
            <li>
                <a class="flex items-center px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?= $currentPage === 'api-tester.php' ? 'bg-white bg-opacity-20 shadow-lg' : '' ?>" href="api-tester.php">
                    <i class="fas fa-flask mr-3 w-5"></i> API Tester
                </a>
            </li>
            <li>
                <a class="flex items-center px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?= $currentPage === 'settings.php' ? 'bg-white bg-opacity-20 shadow-lg' : '' ?>" href="settings.php">
                    <i class="fas fa-cog mr-3 w-5"></i> Settings
                </a>
            </li>
            <li class="mt-8 pt-4 border-t border-white border-opacity-20">
                <a class="flex items-center px-4 py-3 text-white hover:bg-red-500 hover:bg-opacity-20 rounded-lg transition-all duration-200" href="logout.php">
                    <i class="fas fa-sign-out-alt mr-3 w-5"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</nav>

