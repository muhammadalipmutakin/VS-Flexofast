<?php
// pages/master_item.php
$page = $_GET['p'] ?? 1;
$search_query = $_GET['q'] ?? '';
$limit = 30;
?>

<div class="p-2 sm:p-4 bg-gray-50 min-h-screen" x-data="itemManager(<?= $page ?>, '<?= $search_query ?>')">

    <h1 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
        <i class="fas fa-boxes text-primary mr-2"></i> Master Data Item
    </h1>

    <div class="flex flex-col sm:flex-row gap-3 mb-4">
        <div class="flex-1">
            <div class="relative">
                <input type="text" x-model="searchKeyword" @keydown.enter="performSearch()"
                    placeholder="Cari Item, Deskripsi, atau Brand..."
                    class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-xs focus:ring-primary focus:border-primary">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs"></i>
            </div>
        </div>
        <button @click="openModal('create')"
            class="px-4 py-2 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-orange-600 transition duration-150 flex items-center justify-center">
            <i class="fas fa-plus mr-2"></i> Tambah Item
        </button>
    </div>

    <!-- Desktop Table View -->
    <div class="hidden lg:block bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-orange-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">No.</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Item</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Deskripsi</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Brand</th>
                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-700 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-if="loading">
                    <template x-for="i in 5" :key="i">
                        <tr>
                            <td class="px-3 py-2">
                                <div class="h-4 bg-gray-200 rounded animate-pulse"></div>
                            </td>
                            <td class="px-3 py-2">
                                <div class="h-4 bg-gray-200 rounded animate-pulse w-32"></div>
                            </td>
                            <td class="px-3 py-2">
                                <div class="h-4 bg-gray-200 rounded animate-pulse"></div>
                            </td>
                            <td class="px-3 py-2">
                                <div class="h-4 bg-gray-200 rounded animate-pulse w-16"></div>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <div class="h-4 bg-gray-200 rounded animate-pulse w-20 mx-auto"></div>
                            </td>
                        </tr>
                    </template>
                </template>

                <template x-if="!loading && items.length > 0">
                    <template x-for="(item, index) in items" :key="item.id">
                        <tr class="hover:bg-gray-50">
                            <td x-text="(currentPage - 1) * <?= $limit ?> + index + 1" class="px-3 py-2 text-xs text-gray-700"></td>
                            <td x-text="item.item" class="px-3 py-2 text-xs font-medium text-primary"></td>
                            <td x-text="item.desc" class="px-3 py-2 text-xs text-gray-600 max-w-xs truncate"></td>
                            <td x-text="item.brand" class="px-3 py-2 text-xs text-gray-700"></td>
                            <td class="px-3 py-2 text-center">
                                <div class="flex space-x-1 justify-center">
                                    <button @click="openModal('edit', item)"
                                        class="text-primary hover:text-orange-700 p-1 rounded hover:bg-orange-50">
                                        <i class="fas fa-edit text-xs"></i>
                                    </button>
                                    <button @click="deleteItem(item.id)"
                                        class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-50">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </template>

                <template x-if="!loading && items.length === 0">
                    <tr>
                        <td colspan="5" class="px-3 py-4 text-center text-xs text-gray-500">
                            <i class="fas fa-box-open text-2xl mb-2 text-gray-300"></i>
                            <p>Tidak ada data Item.</p>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View -->
    <div class="lg:hidden space-y-3">
        <template x-if="loading">
            <template x-for="i in 5" :key="i">
                <div class="bg-white rounded-lg shadow p-4 animate-pulse">
                    <div class="flex justify-between items-start mb-2">
                        <div class="h-4 bg-gray-200 rounded w-1/3"></div>
                        <div class="h-6 bg-gray-200 rounded w-16"></div>
                    </div>
                    <div class="h-3 bg-gray-200 rounded w-full mb-2"></div>
                    <div class="h-3 bg-gray-200 rounded w-2/3"></div>
                    <div class="flex justify-between items-center mt-3">
                        <div class="h-3 bg-gray-200 rounded w-20"></div>
                        <div class="flex space-x-2">
                            <div class="h-6 bg-gray-200 rounded w-6"></div>
                            <div class="h-6 bg-gray-200 rounded w-6"></div>
                        </div>
                    </div>
                </div>
            </template>
        </template>

        <template x-if="!loading && items.length > 0">
            <template x-for="(item, index) in items" :key="item.id">
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition duration-150 p-4">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <div class="flex items-center space-x-2 mb-1">
                                <span class="text-xs font-bold text-gray-900">
                                    No: <span x-text="(currentPage - 1) * <?= $limit ?> + index + 1"></span>
                                </span>
                            </div>
                            <div class="text-sm font-bold text-primary" x-text="item.item"></div>
                        </div>
                        <div class="flex space-x-1">
                            <button @click="openModal('edit', item)"
                                class="text-primary hover:text-orange-700 p-1 rounded hover:bg-orange-50 transition duration-150"
                                title="Edit">
                                <i class="fas fa-edit text-xs"></i>
                            </button>
                            <button @click="deleteItem(item.id)"
                                class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-50 transition duration-150"
                                title="Hapus">
                                <i class="fas fa-trash-alt text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div>
                            <span class="font-semibold text-gray-600">Deskripsi:</span>
                            <span class="text-gray-600 ml-1" x-text="item.desc || '-'"></span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-600">Brand:</span>
                            <span class="text-gray-700 ml-1" x-text="item.brand || '-'"></span>
                        </div>
                    </div>
                </div>
            </template>
        </template>

        <template x-if="!loading && items.length === 0">
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <i class="fas fa-box-open text-3xl mb-3 text-gray-300"></i>
                <p class="text-xs text-gray-500">Tidak ada data Item yang ditemukan.</p>
            </div>
        </template>
    </div>

    <!-- Pagination -->
    <div x-show="!loading && totalPages > 1" class="mt-4 flex flex-col sm:flex-row justify-between items-center gap-3">
        <p class="text-xs text-gray-600">
            Menampilkan <span class="font-bold" x-text="paginationStart"></span> - <span class="font-bold" x-text="paginationEnd"></span> dari <span class="font-bold" x-text="totalItems"></span>
        </p>
        <nav class="flex space-x-1">
            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                class="px-3 py-1 border border-gray-300 bg-white text-xs text-gray-500 rounded hover:bg-gray-50 disabled:opacity-50">
                <i class="fas fa-chevron-left"></i>
            </button>
            <template x-for="p in pageNumbers" :key="p">
                <button @click="goToPage(p)"
                    :class="p === currentPage ? 'bg-primary text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'"
                    class="px-3 py-1 text-xs font-medium rounded">
                    <span x-text="p"></span>
                </button>
            </template>
            <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages"
                class="px-3 py-1 border border-gray-300 bg-white text-xs text-gray-500 rounded hover:bg-gray-50 disabled:opacity-50">
                <i class="fas fa-chevron-right"></i>
            </button>
        </nav>
    </div>

    <!-- Modal -->
    <div x-cloak x-show="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-3 py-4">
            <div x-show="isModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="isModalOpen = false"></div>

            <div x-show="isModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
                class="relative bg-white rounded-lg w-full max-w-md mx-auto shadow-xl">
                <div class="p-4">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0 flex items-center justify-center h-8 w-8 rounded-full bg-orange-100">
                            <i class="fas fa-box-open text-primary text-sm"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-base font-medium text-gray-900" x-text="modalTitle"></h3>
                        </div>
                    </div>

                    <form @submit.prevent="submitForm()" class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Nama Item <span class="text-red-500">*</span></label>
                            <input type="text" x-model="formData.item" required
                                class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary"
                                placeholder="Masukkan nama item">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Deskripsi</label>
                            <textarea x-model="formData.desc" rows="3"
                                class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary"
                                placeholder="Tambahkan deskripsi"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Brand</label>
                            <input type="text" x-model="formData.brand"
                                class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary"
                                placeholder="Masukkan brand">
                        </div>

                        <div class="flex justify-end space-x-2 pt-3">
                            <button @click="isModalOpen = false" type="button"
                                class="px-4 py-2 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                Batal
                            </button>
                            <button type="submit" :disabled="formLoading"
                                class="px-4 py-2 bg-primary text-xs font-medium rounded text-white hover:bg-orange-600 disabled:bg-gray-400">
                                <i x-show="formLoading" class="fas fa-spinner fa-spin mr-1"></i>
                                <span x-text="submitButtonText"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification -->
    <div id="notification" class="fixed bottom-3 right-3 w-72 z-50">
        <template x-for="note in notifications" :key="note.id">
            <div :class="note.type === 'success' ? 'bg-primary' : 'bg-red-500'"
                class="text-xs text-white p-3 rounded-lg shadow-lg mb-2 flex justify-between items-center transition-all duration-300">
                <span x-text="note.message"></span>
                <button @click="removeNotification(note.id)" class="text-white ml-2">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </template>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('itemManager', (initialPage, initialSearch) => ({
            API_URL: 'controller/item/route.php',
            loading: true,
            formLoading: false,
            items: [],
            currentPage: initialPage,
            totalItems: 0,
            totalPages: 0,
            searchKeyword: initialSearch,

            // Modal state
            isModalOpen: false,
            modalMode: 'create', // 'create' or 'edit'
            modalTitle: 'Tambah Item Baru',
            submitButtonText: 'Simpan Item',
            formData: {
                id: null,
                item: '',
                desc: '',
                brand: ''
            },

            notifications: [],

            init() {
                this.loadItems();
            },

            // --- Notification Functions ---
            showNotification(message, type = 'success') {
                const id = Date.now();
                this.notifications.push({
                    id,
                    message,
                    type
                });
                // Durasi notifikasi
                setTimeout(() => this.removeNotification(id), 5000);
            },
            removeNotification(id) {
                this.notifications = this.notifications.filter(note => note.id !== id);
            },

            // --- Pagination Helpers ---
            get paginationStart() {
                // Pastikan totalItems tidak 0
                if (this.totalItems === 0) return 0;
                return (this.currentPage - 1) * 30 + 1;
            },
            get paginationEnd() {
                return Math.min(this.currentPage * 30, this.totalItems);
            },
            get pageNumbers() {
                const pages = [];
                const maxPagesToShow = 5;
                const halfMax = Math.floor(maxPagesToShow / 2);

                let startPage = Math.max(1, this.currentPage - halfMax);
                let endPage = Math.min(this.totalPages, this.currentPage + halfMax);

                // Adjust start/end to always show maxPagesToShow if possible
                if (endPage - startPage + 1 < maxPagesToShow) {
                    if (this.currentPage <= halfMax) {
                        endPage = Math.min(this.totalPages, maxPagesToShow);
                    } else if (this.currentPage > this.totalPages - halfMax) {
                        startPage = Math.max(1, this.totalPages - maxPagesToShow + 1);
                    }
                }

                // Logic for adding first page and ellipses
                if (startPage > 1) {
                    pages.push(1);
                    if (startPage > 2) {
                        pages.push('...');
                    }
                }

                // Main page numbers
                for (let i = startPage; i <= endPage; i++) {
                    pages.push(i);
                }

                // Logic for adding last page and ellipses
                if (endPage < this.totalPages) {
                    if (endPage < this.totalPages - 1) {
                        pages.push('...');
                    }
                    pages.push(this.totalPages);
                }

                return [...new Set(pages)].filter(p => p !== '...');
            },
            goToPage(page) {
                if (page < 1 || page > this.totalPages || page === '...') return;
                this.currentPage = page;
                this.updateUrl();
                this.loadItems();
            },

            // --- Search & URL Functions ---
            performSearch() {
                this.currentPage = 1;
                this.updateUrl();
                this.loadItems();
            },
            updateUrl() {
                const url = new URL(window.location.href);
                // Asumsi parameter 'page' untuk navigasi utama (e.g., index.php?page=master_item)
                // Kita hanya ubah parameter 'p' (pagination) dan 'q' (query)
                url.searchParams.set('p', this.currentPage);

                if (this.searchKeyword) {
                    url.searchParams.set('q', this.searchKeyword);
                } else {
                    url.searchParams.delete('q');
                }

                window.history.pushState({}, '', url);
            },


            // --- CRUD Functions ---

            // READ
            async loadItems() {
                this.loading = true;
                const url = `${this.API_URL}?action=read&p=${this.currentPage}&q=${this.searchKeyword}&limit=30`;

                try {
                    const response = await fetch(url);
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.items = result.data.items || [];
                        this.totalItems = result.data.total_items || 0;
                        this.totalPages = Math.ceil(this.totalItems / 30); // 30 items per page

                        // Periksa jika currentPage menjadi tidak valid setelah pencarian/reload
                        if (this.currentPage > this.totalPages && this.totalPages > 0) {
                            this.currentPage = this.totalPages;
                            this.updateUrl();
                            this.loadItems(); // Load ulang dengan halaman terakhir
                            return;
                        } else if (this.totalItems === 0) {
                            this.currentPage = 1;
                        }
                    } else {
                        this.showNotification(`Gagal memuat data: ${result.message}`, 'error');
                        this.items = [];
                        this.totalItems = 0;
                        this.totalPages = 0;
                    }
                } catch (error) {
                    console.error("Error loading items:", error);
                    this.showNotification('Terjadi kesalahan koneksi saat memuat data.', 'error');
                } finally {
                    this.loading = false;
                }
            },

            // OPEN MODAL (CREATE/EDIT)
            openModal(mode, item = null) {
                this.modalMode = mode;

                if (mode === 'create') {
                    this.modalTitle = 'Tambah Item Baru';
                    this.submitButtonText = 'Simpan Item';
                    this.formData = {
                        id: null,
                        item: '',
                        desc: '',
                        brand: ''
                    };
                } else if (mode === 'edit' && item) {
                    this.modalTitle = 'Edit Item: ' + item.item;
                    this.submitButtonText = 'Update Item';
                    this.formData = {
                        ...item
                    }; // Copy data item
                }

                this.isModalOpen = true;
            },

            // CREATE/UPDATE
            async submitForm() {
                this.formLoading = true;
                const action = this.modalMode === 'create' ? 'create' : 'update';

                try {
                    const response = await fetch(this.API_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: action,
                            ...this.formData
                        })
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        this.showNotification(result.message, 'success');
                        this.isModalOpen = false;
                        this.loadItems(); // Reload data
                    } else {
                        this.showNotification(`Gagal ${action === 'create' ? 'menyimpan' : 'mengupdate'} data: ${result.message}`, 'error');
                    }
                } catch (error) {
                    console.error("Error submitting form:", error);
                    this.showNotification('Terjadi kesalahan koneksi saat mengirim data.', 'error');
                } finally {
                    this.formLoading = false;
                }
            },

            // DELETE
            async deleteItem(id) {
                // Menggunakan sweetalert/custom modal akan lebih baik
                if (!confirm('Apakah Anda yakin ingin menghapus Item ini secara permanen? Tindakan ini tidak dapat dibatalkan.')) return;

                try {
                    const response = await fetch(this.API_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            id: id
                        })
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        this.showNotification(result.message, 'success');
                        this.loadItems(); // Reload data
                    } else {
                        this.showNotification(`Gagal menghapus data: ${result.message}`, 'error');
                    }
                } catch (error) {
                    console.error("Error deleting item:", error);
                    this.showNotification('Terjadi kesalahan koneksi saat menghapus data.', 'error');
                }
            }
        }));
    });
</script>