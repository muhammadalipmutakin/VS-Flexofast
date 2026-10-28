<?php
// pages/manage_outbound.php
require_once 'controller/db/config.php';
?>

<div class="p-2 sm:p-4 bg-gray-50 min-h-screen" x-data="outboundManager()">

    <h1 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center justify-between">
        <span class="flex items-center">
            <i class="fas fa-database text-primary mr-2"></i> Data Outbond Request
        </span>
        <a href="?page=outbound_request" class="text-xs px-3 py-1 bg-gray-200 text-gray-700 font-semibold rounded hover:bg-gray-300 transition duration-150 flex items-center">
            <i class="fas fa-plus mr-1"></i> Buat Permintaan Baru
        </a>
    </h1>

    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-gray-800 flex items-center">
                <i class="fas fa-filter mr-2 text-primary"></i> Filter Data
            </h3>
            <button @click="isFilterCollapsed = !isFilterCollapsed"
                class="lg:hidden text-primary text-xs font-semibold px-2 py-1 rounded hover:bg-orange-50 transition duration-150">
                <span x-text="isFilterCollapsed ? 'Tampilkan Filter' : 'Sembunyikan Filter'"></span>
                <i :class="isFilterCollapsed ? 'fa-chevron-down' : 'fa-chevron-up'" class="fas ml-1"></i>
            </button>

        </div>

        <div x-show="!isFilterCollapsed" x-collapse.duration.300ms class="lg:block">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal Awal</label>
                    <input type="date" x-model="filters.start_date"
                        class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal Akhir</label>
                    <input type="date" x-model="filters.end_date"
                        class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Filter Item</label>
                    <select x-model="filters.item_id"
                        class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                        <option value="">Semua Item</option>
                        <template x-for="item in items" :key="item.id">
                            <option :value="item.id" x-text="item.item"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Filter Jenis</label>
                    <select x-model="filters.jenis"
                        class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                        <option value="">Semua Jenis</option>
                        <option value="butuh_validasi">Butuh Validasi</option>
                        <option value="butuh_replen">Butuh Replen</option>
                    </select>
                </div>
                <div class="flex items-end space-x-2">
                    <button @click="loadRequests()" :disabled="loadingRequests"
                        class="flex-1 bg-primary text-white text-xs font-bold p-2 rounded hover:bg-orange-600 transition duration-150 disabled:bg-gray-400">
                        <i class="fas fa-search mr-1"></i> Cari
                    </button>
                    <button @click="resetFilters(); loadRequests()"
                        class="flex-1 bg-gray-400 text-white text-xs font-bold p-2 rounded hover:bg-gray-500 transition duration-150">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>

    </div>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <template x-if="loadingRequests">
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-2xl text-primary"></i>
                <p class="mt-2 text-xs text-gray-600">Memuat data permintaan...</p>
            </div>
        </template>

        <template x-if="!loadingRequests && requests.length === 0">
            <div class="text-center py-8">
                <i class="fas fa-exclamation-circle text-2xl text-red-500"></i>
                <p class="mt-2 text-xs text-gray-600">Tidak ada data permintaan.</p>
            </div>
        </template>

        <div class="hidden lg:block">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-primary text-white">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-bold uppercase w-10">No.</th>
                        <th class="px-3 py-2 text-left text-xs font-bold uppercase">ID</th>
                        <th class="px-3 py-2 text-left text-xs font-bold uppercase">Order/SLA</th>
                        <th class="px-3 py-2 text-left text-xs font-bold uppercase">No. Order</th>
                        <th class="px-3 py-2 text-left text-xs font-bold uppercase">Item & QTY</th>
                        <th class="px-3 py-2 text-left text-xs font-bold uppercase">Jenis & Keterangan</th>
                        <th class="px-3 py-2 text-center text-xs font-bold uppercase">Konfirmasi</th>
                        <th class="px-3 py-2 text-center text-xs font-bold uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="(request, index) in requests" :key="request.id">
                        <tr class="hover:bg-gray-50">
                            <td x-text="index + 1" class="px-3 py-2 text-xs text-gray-900 font-medium"></td>
                            <td x-text="request.id" class="px-3 py-2 text-xs text-gray-500"></td>
                            <td class="px-3 py-2 text-xs text-gray-500">
                                📅 Order: <span x-text="request.order_date || '-'"></span><br>
                                ⏳ SLA: <span x-text="request.sla_date || '-'"></span>
                            </td>
                            <td x-text="request.order_number || '-'" class="px-3 py-2 text-xs text-gray-500"></td>
                            <td class="px-3 py-2 text-xs text-gray-500">
                                <span class="font-bold text-gray-900" x-text="request.item"></span><br>
                                QTY: <span class="font-bold text-primary" x-text="request.requested_qty || 0"></span>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500">
                                <span :class="request.jenis === 'butuh_validasi' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'"
                                    class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full" x-text="request.jenis"></span><br>
                                <span x-text="request.keterangan || '-'" class="italic text-gray-600 block mt-1"></span>
                            </td>
                            <td class="px-3 py-2 text-center text-xs">
                                <div x-show="request.is_confirm" class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold border border-green-500">
                                    <i class="fas fa-lock mr-1"></i> Sudah Dikonfirmasi<br>
                                    <span class="font-normal" x-text="request.konfirmasi_status || 'N/A'"></span>
                                </div>
                                <div x-show="!request.is_confirm" class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-bold">
                                    <i class="fas fa-unlock mr-1"></i> Belum Dikonfirmasi
                                </div>
                            </td>
                            <td class="px-3 py-2 text-center text-xs">
                                <div x-show="request.is_confirm" class="text-gray-400 font-bold">
                                    <i class="fas fa-ban"></i> Aksi Dikunci
                                </div>
                                <div x-show="!request.is_confirm" class="flex items-center justify-center space-x-1">
                                    <button @click="openEditModal(request)"
                                        class="text-primary hover:text-orange-700 p-1 rounded hover:bg-orange-50">
                                        <i class="fas fa-edit text-xs"></i>
                                    </button>
                                    <button @click="openDeleteModal(request)"
                                        class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-50">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </div>
                            </td>

                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="lg:hidden space-y-3 p-4">
            <template x-for="(request, index) in requests" :key="request.id">
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition duration-150 p-4">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <div class="flex items-center space-x-2 mb-1">
                                <span class="text-xs font-bold text-gray-900">No: <span x-text="index + 1"></span> (ID: <span x-text="request.id"></span>)</span>
                                <span :class="request.jenis === 'butuh_validasi' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'"
                                    class="px-2 py-0.5 text-xs font-semibold rounded-full" x-text="request.jenis"></span>
                            </div>
                            <div class="text-sm font-bold text-primary" x-text="request.item"></div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-500 mb-1" x-text="request.order_number || 'No Order'"></div>
                            <div x-show="request.is_confirm" class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs font-bold">
                                <i class="fas fa-lock mr-1"></i> Terkonfirmasi
                            </div>
                            <div x-show="!request.is_confirm" class="bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded text-xs font-bold">
                                <i class="fas fa-unlock mr-1"></i> Belum
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2 mb-3">
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="font-semibold text-gray-600">Order:</span>
                                <span x-text="request.order_date || '-'"></span>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-600">SLA:</span>
                                <span x-text="request.sla_date || '-'"></span>
                            </div>
                        </div>
                        <div class="text-xs">
                            <span class="font-semibold text-gray-600">QTY Request:</span>
                            <span class="font-bold text-primary" x-text="request.requested_qty || 0"></span> Unit
                        </div>
                        <div x-show="request.konfirmasi_status" class="text-xs">
                            <span class="font-semibold text-gray-600">Status:</span>
                            <span class="font-bold" :class="{
                'text-green-600': request.konfirmasi_status === 'Fullfill Pas',
                'text-blue-600': request.konfirmasi_status === 'Fullfill Lebih',
                'text-red-600': request.konfirmasi_status === 'Kurang Supply'
              }" x-text="request.konfirmasi_status"></span>
                        </div>
                        <div x-show="request.keterangan" class="text-xs">
                            <span class="font-semibold text-gray-600">Keterangan:</span>
                            <span class="text-gray-600 italic" x-text="request.keterangan"></span>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                        <div class="text-xs text-gray-500">
                            Terakhir update
                        </div>
                        <div class="flex space-x-2">
                            <div x-show="request.is_confirm" class="text-gray-400 text-xs font-semibold">
                                <i class="fas fa-ban mr-1"></i> Dikunci
                            </div>
                            <div x-show="!request.is_confirm" class="flex space-x-1">
                                <button @click="openEditModal(request)"
                                    class="text-primary hover:text-orange-700 p-1 rounded hover:bg-orange-50 transition duration-150"
                                    title="Edit">
                                    <i class="fas fa-edit text-xs"></i>
                                </button>
                                <button @click="openDeleteModal(request)"
                                    class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-50 transition duration-150"
                                    title="Hapus">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div x-cloak x-show="isEditModalOpen" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-3 py-4">
            <div x-show="isEditModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="isEditModalOpen = false"></div>

            <div x-show="isEditModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
                class="relative bg-white rounded-lg w-full max-w-md mx-auto shadow-xl">
                <div class="p-4">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0 flex items-center justify-center h-8 w-8 rounded-full bg-orange-100">
                            <i class="fas fa-edit text-primary text-sm"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-base font-medium text-gray-900">Edit Permintaan ID: <span x-text="editFormData.id"></span></h3>
                            <p class="text-xs text-gray-500 italic">Hanya data yang Belum Dikonfirmasi yang bisa diubah.</p>
                        </div>
                    </div>

                    <form @submit.prevent="submitEdit()" class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Item</label>
                            <select x-model="editFormData.id_item" required
                                class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary">
                                <option value="">-- Pilih Item --</option>
                                <template x-for="item in items" :key="item.id">
                                    <option :value="item.id" x-text="item.item"></option>
                                </template>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Order Date</label>
                                <input type="date" x-model="editFormData.order_date"
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">SLA Date</label>
                                <input type="date" x-model="editFormData.sla_date"
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary">
                            </div>
                        </div>

                        <div x-show="editFormData.jenis === 'butuh_validasi'">
                            <label class="block text-xs font-medium text-gray-700">Order Number</label>
                            <input type="text" x-model="editFormData.order_number"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">QTY Diminta</label>
                                <input type="number" x-model="editFormData.qty" min="1" step="1"
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Jenis</label>
                                <select x-model="editFormData.jenis" required
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary">
                                    <option value="butuh_validasi">Butuh Validasi</option>
                                    <option value="butuh_replen">Butuh Replenishment</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">Keterangan</label>
                            <textarea x-model="editFormData.keterangan" rows="2"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary"></textarea>
                        </div>

                        <div class="flex justify-end space-x-2 pt-3">
                            <button @click="isEditModalOpen = false" type="button"
                                class="px-4 py-2 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                Batal
                            </button>
                            <button type="submit" :disabled="formLoading"
                                class="px-4 py-2 bg-primary text-xs font-medium rounded text-white hover:bg-orange-600 disabled:bg-gray-400">
                                <i x-show="formLoading" class="fas fa-spinner fa-spin mr-1"></i>
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div x-cloak x-show="isDeleteModalOpen" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-3 py-4">
            <div x-show="isDeleteModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="isDeleteModalOpen = false"></div>

            <div x-show="isDeleteModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
                class="relative bg-white rounded-lg w-full max-w-md mx-auto shadow-xl">
                <div class="p-4">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0 flex items-center justify-center h-8 w-8 rounded-full bg-red-100">
                            <i class="fas fa-exclamation-triangle text-red-600 text-sm"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-base font-medium text-gray-900">Konfirmasi Penghapusan Permintaan</h3>
                            <div class="mt-2 text-xs text-gray-700 space-y-1">
                                <p>Anda yakin ingin menghapus permintaan ini?</p>
                                <p class="font-bold text-red-600">ID Permintaan: <span x-text="deleteRequest.id"></span></p>
                                <p class="font-semibold">Item: <span x-text="deleteRequest.item"></span></p>
                                <p>QTY: <span x-text="deleteRequest.requested_qty"></span> | Jenis: <span x-text="deleteRequest.jenis"></span></p>
                                <p class="text-xs text-gray-500 italic mt-1">Aksi ini tidak dapat dibatalkan.</p>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 flex justify-end space-x-2">
                    <button @click="isDeleteModalOpen = false" type="button"
                        class="px-4 py-2 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                        Batal
                    </button>
                    <button @click="confirmDelete()" type="button" :disabled="formLoading"
                        class="px-4 py-2 bg-red-600 text-xs font-medium rounded text-white hover:bg-red-700 disabled:bg-gray-400">
                        <i x-show="formLoading" class="fas fa-spinner fa-spin mr-1"></i>
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

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
    const getTodayDate = () => {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    };

    document.addEventListener('alpine:init', () => {
        Alpine.data('outboundManager', () => ({
            API_MANAGE_URL: 'controller/outbound/manage_route.php',
            API_ITEM_URL: 'controller/outbound/route.php?action=read_items',

            loadingRequests: true,
            formLoading: false,
            // PERUBAHAN: Tambah state untuk filter collapse
            isFilterCollapsed: window.innerWidth < 1024,

            requests: [],
            items: [],

            isEditModalOpen: false,
            isDeleteModalOpen: false,

            editFormData: {
                action: 'update',
                id: null,
                order_date: '',
                sla_date: '',
                order_number: '',
                id_item: '',
                qty: '',
                keterangan: '',
                jenis: ''
            },
            deleteRequest: {
                id: null,
                // PERUBAHAN: Tambahkan detail untuk modal hapus
                item: '',
                requested_qty: 0,
                jenis: '',
            },

            filters: {
                start_date: getTodayDate(),
                end_date: getTodayDate(),
                item_id: '',
                jenis: ''
            },

            notifications: [],

            init() {
                // PERUBAHAN: Tambah listener untuk layar resize
                window.addEventListener('resize', () => {
                    this.isFilterCollapsed = window.innerWidth < 1024;
                });

                this.loadItems().then(() => {
                    this.loadRequests();
                });
            },

            async loadItems() {
                try {
                    const response = await fetch(this.API_ITEM_URL);
                    const result = await response.json();
                    if (result.status === 'success') {
                        this.items = result.data;
                    } else {
                        this.showNotification(`Gagal memuat daftar item: ${result.message}`, 'error');
                    }
                } catch (error) {
                    this.showNotification('Terjadi kesalahan koneksi.', 'error');
                }
            },

            async loadRequests() {
                this.loadingRequests = true;
                const filterToSend = {
                    ...this.filters,
                    item_id: this.filters.item_id === '' ? null : this.filters.item_id
                };

                const params = new URLSearchParams(filterToSend).toString();
                try {
                    const response = await fetch(`${this.API_MANAGE_URL}?action=read&${params}`);
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.requests = result.data;
                    } else {
                        this.showNotification(`Gagal memuat data: ${result.message}`, 'error');
                        this.requests = [];
                    }
                } catch (error) {
                    console.error("Error loading requests:", error);
                    this.showNotification('Terjadi kesalahan koneksi.', 'error');
                    this.requests = [];
                } finally {
                    this.loadingRequests = false;
                }
            },

            resetFilters() {
                this.filters = {
                    start_date: getTodayDate(),
                    end_date: getTodayDate(),
                    item_id: '',
                    jenis: ''
                };
            },

            openEditModal(request) {
                let itemId = request.id_item;
                if (!itemId && request.item) {
                    const foundItem = this.items.find(i => i.item === request.item);
                    itemId = foundItem ? foundItem.id : '';
                }

                this.editFormData = {
                    action: 'update',
                    id: request.id,
                    order_date: request.order_date || '',
                    sla_date: request.sla_date || '',
                    order_number: request.order_number || '',
                    id_item: String(itemId || ''),
                    qty: request.requested_qty || '',
                    keterangan: request.keterangan || '',
                    jenis: request.jenis
                };

                this.$nextTick(() => {
                    this.isEditModalOpen = true;
                });
            },

            async submitEdit() {
                this.formLoading = true;
                const payload = {
                    ...this.editFormData
                };
                payload.id_item = Number(payload.id_item);

                if (!payload.id_item || payload.id_item <= 0) {
                    this.showNotification('Item wajib dipilih.', 'error');
                    this.formLoading = false;
                    return;
                }

                if (!payload.qty || payload.qty <= 0) {
                    this.showNotification('QTY harus lebih dari 0.', 'error');
                    this.formLoading = false;
                    return;
                }

                if (payload.jenis === 'butuh_replen') {
                    payload.order_number = '';
                }

                try {
                    const response = await fetch(this.API_MANAGE_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.showNotification(result.message, 'success');
                        this.loadRequests();
                        this.isEditModalOpen = false;
                    } else {
                        this.showNotification(`Gagal update: ${result.message}`, 'error');
                    }
                } catch (error) {
                    console.error("Error submitting edit:", error);
                    this.showNotification('Terjadi kesalahan koneksi.', 'error');
                } finally {
                    this.formLoading = false;
                }
            },

            // PERUBAHAN: Memuat detail data ke deleteRequest
            openDeleteModal(request) {
                this.deleteRequest = {
                    id: request.id,
                    item: request.item,
                    requested_qty: request.requested_qty,
                    jenis: request.jenis
                };
                this.isDeleteModalOpen = true;
            },

            async confirmDelete() {
                this.formLoading = true;
                try {
                    const response = await fetch(this.API_MANAGE_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            id: this.deleteRequest.id
                        })
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.showNotification(result.message, 'success');
                        this.loadRequests();
                        this.isDeleteModalOpen = false;
                    } else {
                        this.showNotification(`Gagal hapus: ${result.message}`, 'error');
                    }
                } catch (error) {
                    console.error("Error deleting request:", error);
                    this.showNotification('Terjadi kesalahan koneksi.', 'error');
                } finally {
                    this.formLoading = false;
                }
            },

            showNotification(message, type = 'success') {
                const id = Date.now();
                this.notifications.push({
                    id,
                    message,
                    type
                });
                setTimeout(() => this.removeNotification(id), 5000);
            },

            removeNotification(id) {
                this.notifications = this.notifications.filter(note => note.id !== id);
            },
        }));
    });
</script>