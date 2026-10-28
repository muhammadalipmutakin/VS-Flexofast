<?php
// ./app/components/sidebar.php
$current_page = $_GET['page'] ?? 'dashboard';
$master_data_pages = ['master_item', 'master_email'];
$is_master_data_active = in_array($current_page, $master_data_pages);
?>

<aside id="sidebar" class="bg-white fixed top-16 left-0 h-[calc(100vh-64px)] p-4 shadow-lg border-r border-gray-200 transition-transform duration-300 z-40">
    <div class="overflow-y-auto overflow-x-hidden h-full">
        <ul class="space-y-1">

            <li>
                <a href="?page=dashboard" class="flex items-center p-3 rounded-lg transition duration-150 text-xs font-medium
                    <?php if ($current_page === 'dashboard'): ?>
                        bg-orange-100 text-primary border-r-4 border-primary
                    <?php else: ?>
                        text-gray-700 hover:bg-gray-100 hover:text-primary
                    <?php endif; ?>">
                    <i class="fas fa-tachometer-alt w-4 h-4 mr-3"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li x-data="{ open: <?php echo $is_master_data_active ? 'true' : 'false'; ?> }">
                <a @click.prevent="open = !open" href="#" class="flex items-center p-3 rounded-lg transition duration-150 cursor-pointer text-xs font-medium
                    <?php if ($is_master_data_active): ?>
                        bg-orange-100 text-primary border-r-4 border-primary
                    <?php else: ?>
                        text-gray-700 hover:bg-gray-100 hover:text-primary
                    <?php endif; ?>">
                    <i class="fas fa-database w-4 h-4 mr-3"></i>
                    <span class="flex-1">Master Data</span>
                    <i class="fas fa-chevron-down w-3 h-3 transition-transform" :class="{'rotate-180': open}"></i>
                </a>

                <ul x-show="open" x-transition class="ml-4 mt-1 space-y-1 border-l border-gray-200 pl-2">
                    <li>
                        <a href="?page=master_item" class="flex items-center p-2 rounded-lg transition duration-150 text-xs
                            <?php if ($current_page === 'master_item'): ?>
                                bg-orange-50 text-primary font-semibold
                            <?php else: ?>
                                text-gray-600 hover:bg-gray-100 hover:text-primary
                            <?php endif; ?>">
                            <i class="fas fa-box w-4 h-4 mr-2"></i>
                            <span>Item</span>
                        </a>
                    </li>

                    <li>
                        <a href="?page=master_email" class="flex items-center p-2 rounded-lg transition duration-150 text-xs
                            <?php if ($current_page === 'master_email'): ?>
                                bg-orange-50 text-primary font-semibold
                            <?php else: ?>
                                text-gray-600 hover:bg-gray-100 hover:text-primary
                            <?php endif; ?>">
                            <i class="fas fa-envelope w-4 h-4 mr-2"></i>
                            <span>Email</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="?page=outbound_request" class="flex items-center p-3 rounded-lg transition duration-150 text-xs font-medium
                    <?php if ($current_page === 'outbound_request'): ?>
                        bg-orange-100 text-primary border-r-4 border-primary
                    <?php else: ?>
                        text-gray-700 hover:bg-gray-100 hover:text-primary
                    <?php endif; ?>">
                    <i class="fas fa-paper-plane w-4 h-4 mr-3"></i>
                    <span>Outbound Request</span>
                </a>
            </li>

            <li>
                <a href="?page=manage_outbound" class="flex items-center p-3 rounded-lg transition duration-150 text-xs font-medium
                    <?php if ($current_page === 'manage_outbound'): ?>
                        bg-orange-100 text-primary border-r-4 border-primary
                    <?php else: ?>
                        text-gray-700 hover:bg-gray-100 hover:text-primary
                    <?php endif; ?>">
                    <i class="fas fa-list-alt w-4 h-4 mr-3"></i>
                    <span>Manage Outbound</span>
                </a>
            </li>

            <li>
                <a href="?page=inventory_fullfill" class="flex items-center p-3 rounded-lg transition duration-150 text-xs font-medium
                    <?php if ($current_page === 'inventory_fullfill'): ?>
                        bg-orange-100 text-primary border-r-4 border-primary
                    <?php else: ?>
                        text-gray-700 hover:bg-gray-100 hover:text-primary
                    <?php endif; ?>">
                    <i class="fas fa-truck-loading w-4 h-4 mr-3"></i>
                    <span>Inventory Fullfill</span>
                </a>
            </li>

            <li>
                <a href="?page=report" class="flex items-center p-3 rounded-lg transition duration-150 text-xs font-medium
                    <?php if ($current_page === 'report'): ?>
                        bg-orange-100 text-primary border-r-4 border-primary
                    <?php else: ?>
                        text-gray-700 hover:bg-gray-100 hover:text-primary
                    <?php endif; ?>">
                    <i class="fas fa-chart-bar w-4 h-4 mr-3"></i>
                    <span>Report</span>
                </a>
            </li>
        </ul>
    </div>
</aside>