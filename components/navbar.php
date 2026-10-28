<?php
// ./app/components/navbar.php
$username_display = 'Admin';
?>
<nav class="bg-white shadow fixed top-0 w-full z-50">
    <div class="px-4 py-3 flex justify-between items-center h-16">
        <div class="flex items-center space-x-3">
            <button id="sidebarToggle" onclick="toggleSidebar()"
                class="p-2 text-gray-700 rounded-lg hover:bg-gray-100 transition duration-150 focus:outline-none">
                <i class="fas fa-bars"></i>
            </button>

            <img src="./assets/img/flexo.png" alt="Validasi Stock - FLEXOFAST" class="h-8 md:h-10">
            <span class="text-xs md:text-sm font-semibold text-gray-800 hidden sm:block">PM - FLEXOFAST</span>
        </div>

        <div class="flex items-center space-x-3">
            <span class="text-xs text-gray-600 hidden sm:block"><?php echo $username_display; ?></span>
            <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white text-xs font-bold">
                <?php echo strtoupper(substr($username_display, 0, 1)); ?>
            </div>
        </div>
    </div>
</nav>