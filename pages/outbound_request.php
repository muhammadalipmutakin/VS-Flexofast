<?php
// pages/outbound_request.php
?>

<div class="p-2 sm:p-4 bg-gray-50 min-h-screen" x-data="outboundRequestManager()">

    <h1 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center justify-between">
        <span class="flex items-center">
            <i class="fas fa-truck-loading text-primary mr-2"></i> Outbond Request
        </span>
        <a href="?page=manage_outbound" class="text-xs px-3 py-1 bg-gray-200 text-gray-700 font-semibold rounded hover:bg-gray-300 transition duration-150 flex items-center">
            <i class="fas fa-list-alt mr-1"></i> Lihat Data
        </a>
    </h1>

    <div class="mb-4 border-b border-gray-200">
        <ul class="flex flex-wrap -mb-px text-xs font-bold text-center">
            <li class="mr-1">
                <button @click="currentTab = 'validasi_stok'" :class="{'text-white bg-primary shadow border-primary': currentTab === 'validasi_stok', 'text-gray-600 hover:text-primary': currentTab !== 'validasi_stok'}"
                    class="inline-block px-4 py-2 border-b-2 rounded-t transition duration-150">
                    <i class="fas fa-check-circle mr-1"></i> Validasi Stok
                </button>
            </li>
            <li class="mr-1">
                <button @click="currentTab = 'butuh_replen'" :class="{'text-white bg-primary shadow border-primary': currentTab === 'butuh_replen', 'text-gray-600 hover:text-primary': currentTab !== 'butuh_replen'}"
                    class="inline-block px-4 py-2 border-b-2 rounded-t transition duration-150">
                    <i class="fas fa-redo-alt mr-1"></i> Butuh Replen
                </button>
            </li>
        </ul>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <form @submit.prevent="submitForm()" class="space-y-4">

            <div x-show="currentTab === 'validasi_stok'" x-cloak>
                <p class="text-xs text-gray-500 mb-4 border-l-4 border-red-500 pl-2 italic">
                    Permintaan ini mencakup nomor order dan memerlukan validasi ketersediaan stok.
                </p>

                <template x-if="loadingItems">
                    <div class="text-center py-6 bg-gray-50 rounded">
                        <i class="fas fa-spinner fa-spin text-xl text-primary"></i>
                        <p class="mt-2 text-xs text-gray-600">Memuat data item...</p>
                    </div>
                </template>

                <template x-if="!loadingItems">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Order Date</label>
                            <input type="date" x-model="formData.order_date"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">SLA Date</label>
                            <input type="date" x-model="formData.sla_date"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Order Number</label>
                            <input type="text" x-model="formData.order_number"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary"
                                placeholder="Masukkan nomor order">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Pilih Item <span class="text-red-500">*</span></label>
                            <select x-model="formData.id_item" @change="updateItemDetails()" required
                                class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary bg-white">
                                <option value="" disabled>-- Pilih Item --</option>
                                <template x-for="item in items" :key="item.id">
                                    <option :value="item.id" x-text="item.item"></option>
                                </template>
                            </select>

                            <div class="mt-3 p-3 bg-orange-50 rounded border border-orange-200 text-xs" x-show="selectedItem.id" x-transition>
                                <p class="font-bold text-primary">Detail Item Dipilih:</p>
                                <p class="text-gray-700 mt-1">
                                    <i class="fas fa-file-alt mr-2 text-primary"></i> Deskripsi: <span class="font-medium" x-text="selectedItem.desc || '-'"></span>
                                </p>
                                <p class="text-gray-700">
                                    <i class="fas fa-tag mr-2 text-primary"></i> Brand: <span class="font-medium" x-text="selectedItem.brand || '-'"></span>
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">QTY Diminta</label>
                            <input type="number" x-model="formData.qty" min="1" step="1"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Keterangan</label>
                            <textarea x-model="formData.keterangan" rows="2"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary"></textarea>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="currentTab === 'butuh_replen'" x-cloak>
                <p class="text-xs text-gray-500 mb-4 border-l-4 border-blue-500 pl-2 italic">
                    Permintaan ini untuk mengisi ulang stok, tidak memerlukan nomor order.
                </p>

                <template x-if="loadingItems">
                    <div class="text-center py-6 bg-gray-50 rounded">
                        <i class="fas fa-spinner fa-spin text-xl text-primary"></i>
                        <p class="mt-2 text-xs text-gray-600">Memuat data item...</p>
                    </div>
                </template>

                <template x-if="!loadingItems">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Order Date</label>
                            <input type="date" x-model="formData.order_date"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">SLA Date</label>
                            <input type="date" x-model="formData.sla_date"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Pilih Item <span class="text-red-500">*</span></label>
                            <select x-model="formData.id_item" @change="updateItemDetails()" required
                                class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary bg-white">
                                <option value="" disabled>-- Pilih Item --</option>
                                <template x-for="item in items" :key="item.id">
                                    <option :value="item.id" x-text="item.item"></option>
                                </template>
                            </select>

                            <div class="mt-3 p-3 bg-orange-50 rounded border border-orange-200 text-xs" x-show="selectedItem.id" x-transition>
                                <p class="font-bold text-primary">Detail Item Dipilih:</p>
                                <p class="text-gray-700 mt-1">
                                    <i class="fas fa-file-alt mr-2 text-primary"></i> Deskripsi: <span class="font-medium" x-text="selectedItem.desc || '-'"></span>
                                </p>
                                <p class="text-gray-700">
                                    <i class="fas fa-tag mr-2 text-primary"></i> Brand: <span class="font-medium" x-text="selectedItem.brand || '-'"></span>
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">QTY Diminta</label>
                            <input type="number" x-model="formData.qty" min="1" step="1"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Keterangan</label>
                            <textarea x-model="formData.keterangan" rows="2"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary"></textarea>
                        </div>
                    </div>
                </template>
            </div>

            <div class="pt-4 border-t border-gray-200 flex justify-end">
                <button type="submit" :disabled="formLoading || loadingItems || !formData.id_item"
                    class="px-6 py-2 bg-primary text-white text-xs font-bold rounded hover:bg-orange-600 transition duration-150 flex items-center justify-center disabled:bg-gray-400">
                    <i x-show="formLoading" class="fas fa-spinner fa-spin mr-2"></i>
                    <i x-show="!formLoading" class="fas fa-paper-plane mr-2"></i>
                    Ajukan Permintaan
                </button>
            </div>
        </form>
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
        const getTodayDate = () => {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        Alpine.data('outboundRequestManager', () => ({
            API_URL: 'controller/outbound/route.php',
            loadingItems: true,
            formLoading: false,
            currentTab: 'validasi_stok',

            items: [],
            selectedItem: {
                id: null,
                item: '',
                desc: '',
                brand: ''
            },

            formData: {
                action: 'create',
                order_date: getTodayDate(),
                sla_date: getTodayDate(),
                order_number: '',
                id_item: '',
                qty: '',
                keterangan: '',
                jenis: 'butuh_validasi'
            },

            notifications: [],

            init() {
                this.loadItems();
                this.$watch('currentTab', (newTab) => {
                    this.resetForm();
                    this.formData.jenis = newTab === 'validasi_stok' ? 'butuh_validasi' : 'butuh_replen';
                });
            },

            async loadItems() {
                this.loadingItems = true;
                try {
                    const response = await fetch(`${this.API_URL}?action=read_items`);
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.items = result.data;
                    } else {
                        this.showNotification(`Gagal memuat daftar item: ${result.message}`, 'error');
                    }
                } catch (error) {
                    console.error("Error loading items:", error);
                    this.showNotification('Terjadi kesalahan koneksi.', 'error');
                } finally {
                    this.loadingItems = false;
                }
            },

            updateItemDetails() {
                const selectedId = this.formData.id_item;
                if (selectedId) {
                    const item = this.items.find(i => String(i.id) === String(selectedId));
                    this.selectedItem = item || {
                        id: null,
                        item: '',
                        desc: '',
                        brand: ''
                    };
                } else {
                    this.selectedItem = {
                        id: null,
                        item: '',
                        desc: '',
                        brand: ''
                    };
                }
            },

            resetForm() {
                this.formData = {
                    action: 'create',
                    order_date: getTodayDate(),
                    sla_date: getTodayDate(),
                    order_number: '',
                    id_item: '',
                    qty: '',
                    keterangan: '',
                    jenis: this.currentTab === 'validasi_stok' ? 'butuh_validasi' : 'butuh_replen'
                };
                this.selectedItem = {
                    id: null,
                    item: '',
                    desc: '',
                    brand: ''
                };
            },

            async submitForm() {
                if (!this.formData.id_item) {
                    this.showNotification('Anda harus memilih Item terlebih dahulu.', 'error');
                    return;
                }

                this.formLoading = true;
                const payload = {
                    ...this.formData
                };

                if (this.currentTab === 'butuh_replen') {
                    delete payload.order_number;
                }

                try {
                    const response = await fetch(this.API_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        this.showNotification(result.message, 'success');
                        this.resetForm();
                    } else {
                        this.showNotification(`Gagal mengajukan permintaan: ${result.message}`, 'error');
                    }
                } catch (error) {
                    console.error("Error submitting form:", error);
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