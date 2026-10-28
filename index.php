<?php
// ./app/index.php
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Stock - FLEXOFAST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="icon" type="image/x-icon" href="./assets/img/icon.png">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#f97316', // Orange
                        secondary: '#6b7280', // Abu-abu
                        accent: '#10b981', // Hijau
                    },
                },
            },
        }
    </script>
    <style>
        #sidebar {
            width: 16rem;
            transform: translateX(0);
            transition: transform 0.3s ease;
        }

        #content {
            margin-left: 16rem;
            transition: margin-left 0.3s ease;
        }

        body.sidebar-closed #sidebar {
            transform: translateX(-16rem);
        }

        body.sidebar-closed #content {
            margin-left: 0;
        }

        @media (max-width: 768px) {
            #sidebar {
                position: fixed;
                z-index: 40;
                height: 100vh;
            }

            #content {
                margin-left: 0;
            }

            body.sidebar-open #sidebar {
                transform: translateX(0);
            }

            body:not(.sidebar-open) #sidebar {
                transform: translateX(-16rem);
            }

            .sidebar-overlay {
                display: none;
            }

            body.sidebar-open .sidebar-overlay {
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 30;
            }
        }
    </style>
</head>

<body class="bg-gray-100 font-sans antialiased sidebar-open text-xs md:text-sm">
    <?php include 'components/navbar.php'; ?>

    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="flex pt-16">
        <?php include 'components/sidebar.php'; ?>

        <main id="content" class="flex-1 p-2 sm:p-4 transition-all duration-300 min-h-screen w-full">
            <?php
            $page = $_GET['page'] ?? 'dashboard';
            $pagePath = 'pages/' . $page . '.php';

            if (file_exists($pagePath)) {
                include $pagePath;
            } else {
                include 'pages/dashboard.php';
            }
            ?>
        </main>
    </div>

    <script>
        const body = document.body;
        const toggleBtn = document.getElementById('sidebarToggle');
        const overlay = document.querySelector('.sidebar-overlay');

        function toggleSidebar() {
            const isMobile = window.innerWidth <= 768;
            if (isMobile) {
                body.classList.toggle('sidebar-open');
                body.classList.toggle('sidebar-closed');
            } else {
                const isSidebarOpen = body.classList.contains('sidebar-open');
                if (isSidebarOpen) {
                    body.classList.remove('sidebar-open');
                    body.classList.add('sidebar-closed');
                } else {
                    body.classList.remove('sidebar-closed');
                    body.classList.add('sidebar-open');
                }
            }
        }

        function handleResize() {
            const isMobile = window.innerWidth <= 768;
            if (isMobile) {
                body.classList.remove('sidebar-open');
                body.classList.add('sidebar-closed');
            } else {
                body.classList.remove('sidebar-closed');
                body.classList.add('sidebar-open');
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            handleResize();
            window.addEventListener('resize', handleResize);

            // Close sidebar when clicking on links in mobile
            const sidebarLinks = document.querySelectorAll('#sidebar a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', (event) => {
                    const isMobile = window.innerWidth <= 768;

                    // Cek apakah link adalah link Master Data yang memiliki atribut @click.prevent
                    // untuk mencegah penutupan sidebar saat hanya melakukan expand/collapse.
                    const isMasterDataToggle = link.getAttribute('@click.prevent') !== null;

                    if (isMobile) {
                        // Jika bukan toggle Master Data, tutup sidebar
                        if (!isMasterDataToggle) {
                            toggleSidebar();
                        }
                        // Jika toggle Master Data, biarkan tetap terbuka dan AlpineJS yang menangani expand/collapse.
                    }
                });
            });
        });
    </script>
</body>

</html>