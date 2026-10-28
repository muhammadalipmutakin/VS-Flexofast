<?php
// pages/inventory_fullfill.php
$today = date('Y-m-d');
?>

<div class="p-2 sm:p-4 bg-gray-50 min-h-screen" x-data="inventoryFullfillment()">

    <h1 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
        <i class="fas fa-cubes text-primary mr-2"></i> Inventory Fulfillment Tracking
    </h1>

    <div class="bg-white rounded-lg shadow mb-4 transition-all duration-300"
        :class="isFilterOpen ? 'p-4' : 'p-3'">
        <div class="flex justify-between items-center cursor-pointer" @click="isFilterOpen = !isFilterOpen">
            <h2 class="text-sm font-semibold text-gray-800 flex items-center">
                <i class="fas fa-sliders-h text-primary mr-2"></i> Filter Data
            </h2>
            <div class="flex items-center space-x-2">
                <i class="fas text-gray-600 text-xs" :class="isFilterOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </div>
        </div>

        <div x-show="isFilterOpen" x-transition class="mt-3 pt-3 border-t border-gray-100">
            <form @submit.prevent="loadData()" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-600">Dari Tanggal</label>
                    <input type="date" x-model="filters.start_date" required
                        class="mt-1 w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Sampai Tanggal</label>
                    <input type="date" x-model="filters.end_date" required
                        class="mt-1 w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Berdasar Field</label>
                    <select x-model="filters.date_field"
                        class="mt-1 w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-primary focus:border-primary">
                        <option value="order_date">Order Date</option>
                        <option value="sla_date">SLA Date</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Item</label>
                    <select x-model="filters.item"
                        class="mt-1 w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-primary focus:border-primary">
                        <option value="">Semua Item</option>
                        <template x-for="item in itemList" :key="item.id">
                            <option :value="item.item" x-text="item.item"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Jenis</label>
                    <select x-model="filters.jenis"
                        class="mt-1 w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-primary focus:border-primary">
                        <option value="">Semua Jenis</option>
                        <option value="butuh_validasi">Butuh Validasi</option>
                        <option value="butuh_replen">Butuh Replen</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Status</label>
                    <select x-model="filters.status"
                        class="mt-1 w-full border border-gray-300 rounded px-2 py-1 text-xs focus:ring-primary focus:border-primary">
                        <option value="">Semua Status</option>
                        <option value="Terkonfirmasi">Terkonfirmasi</option>
                        <option value="Belum Terkonfirmasi">Belum Terkonfirmasi</option>
                        <option value="Fullfill Pas">Pas</option>
                        <option value="Fullfill Lebih">Lebih</option>
                        <option value="Kurang Supply">Kurang</option>
                    </select>
                </div>

                <div class="col-span-2 sm:col-span-3 lg:col-span-6 flex justify-end pt-2">
                    <button type="submit" :disabled="loading"
                        class="px-4 py-2 bg-primary text-white text-xs font-medium rounded hover:bg-orange-600 transition duration-150 flex items-center disabled:bg-gray-400">
                        <i x-show="loading" class="fas fa-spinner fa-spin mr-2"></i>
                        <i x-show="!loading" class="fas fa-search mr-2"></i>
                        Tampilkan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <template x-if="loading">
            <div class="py-8 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin text-2xl text-primary"></i>
                <p class="mt-2 text-xs">Memuat data permintaan...</p>
            </div>
        </template>

        <template x-if="!loading && data.length === 0">
            <div class="py-8 text-center text-gray-500 border border-dashed border-gray-300 rounded">
                <i class="fas fa-inbox text-3xl text-gray-300"></i>
                <p class="mt-2 text-xs font-semibold">Tidak ada data permintaan.</p>
                <p class="text-xs">Silakan ubah filter.</p>
            </div>
        </template>

        <div x-show="!loading && data.length > 0">
            <p class="text-xs text-gray-600 mb-3"><span x-text="data.length"></span> Permintaan Ditemukan.</p>

            <div class="space-y-3">
                <template x-for="(req, index) in data" :key="req.id">
                    <div class="bg-white border border-gray-200 rounded shadow-sm hover:shadow transition duration-150 p-3">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex-1">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex w-full justify-between md:justify-start space-x-2">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xs font-medium text-gray-500 w-4" x-text="index + 1 + '.'"></span>
                                            <div class="font-bold text-sm text-primary" x-text="req.item"></div>
                                        </div>
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full whitespace-nowrap"
                                            :class="req.jenis === 'butuh_validasi' ? 'text-red-700 bg-red-100' : 'text-blue-700 bg-blue-100'"
                                            x-text="req.jenis.replace('_', ' ').toUpperCase()"></span>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <p class="text-xs text-gray-700"
                                        x-text="'Deskripsi: ' + req.desc"></p>
                                    <div x-show="req.keterangan && req.keterangan.length > 0"
                                        class="mt-1">
                                        <button type="button"
                                            @click="openKeteranganModal(req.keterangan)"
                                            class="text-xs text-primary hover:text-orange-700 transition duration-150 text-left break-words max-w-full">
                                            <i class="fas fa-comment-dots mr-1"></i>
                                            <span class="inline"
                                                x-html="formatKeterangan(req.keterangan)"></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-xs border-t pt-2">
                                    <div>
                                        <span class="font-semibold text-gray-600">Request:</span>
                                        <span class="font-bold text-gray-900" x-text="req.qty"></span> Unit
                                    </div>
                                    <div x-show="req.is_confirm">
                                        <span class="font-semibold text-gray-600">Supply:</span>
                                        <span class="font-bold" :class="req.supply_qty > req.qty ? 'text-green-600' : (req.supply_qty < req.qty ? 'text-red-600' : 'text-gray-600')"
                                            x-text="req.supply_qty"></span> Unit
                                    </div>
                                    <div>
                                        <span class="font-semibold text-gray-600">Order:</span>
                                        <span x-text="formatDate(req.order_date)"></span>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-gray-600">SLA:</span>
                                        <span :class="isSLAOverdue(req.sla_date) ? 'text-red-600 font-bold' : 'text-green-600'" x-text="formatDate(req.sla_date)"></span>
                                    </div>
                                    <div x-show="req.is_confirm" class="col-span-2">
                                        <span class="font-semibold text-gray-600">Dikonfirmasi:</span>
                                        <span class="font-bold text-gray-700" x-text="formatDateTime(req.konfirmasi_at)"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 lg:mt-0 lg:ml-4 lg:text-right lg:min-w-[180px]">
                                <div class="mb-2">
                                    <div class="text-xs font-semibold text-gray-600 mb-1">Status Konfirmasi:</div>
                                    <div x-show="req.is_confirm">
                                        <div class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full mb-1"
                                            :class="getStatusBadgeClass(req.konfirmasi_status)"
                                            x-text="req.konfirmasi_status"></div>
                                        <div class="text-xs font-bold" :class="req.discrepancy > 0 ? 'text-green-600' : (req.discrepancy < 0 ? 'text-red-600' : 'text-gray-600')"
                                            x-text="'Discrepancy: ' + formatDiscrepancy(req.discrepancy)"></div>
                                    </div>
                                    <div x-show="!req.is_confirm" class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Menunggu Konfirmasi
                                    </div>
                                </div>

                                <div class="flex space-x-2 lg:justify-end">
                                    <template x-if="!req.is_confirm">
                                        <button @click="openConfirmModal('confirm', req)"
                                            class="px-3 py-1 bg-green-600 text-white text-xs font-semibold rounded hover:bg-green-700 transition duration-150 flex items-center">
                                            <i class="fas fa-check-circle mr-1"></i> Konfirmasi
                                        </button>
                                    </template>
                                    <template x-if="req.is_confirm">
                                        <button @click="openConfirmModal('edit', req)"
                                            class="px-3 py-1 bg-yellow-600 text-white text-xs font-semibold rounded hover:bg-yellow-700 transition duration-150 flex items-center">
                                            <i class="fas fa-edit mr-1"></i> Edit
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div x-cloak x-show="isKeteranganModalOpen" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-3 py-4">
            <div x-show="isKeteranganModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="isKeteranganModalOpen = false"></div>

            <div x-show="isKeteranganModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
                class="relative bg-white rounded-lg w-full max-w-md mx-auto shadow-xl">
                <div class="p-4">
                    <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-3">
                        Detail Keterangan
                    </h3>
                    <p class="text-xs text-gray-700 whitespace-pre-wrap" x-text="selectedKeterangan"></p>
                </div>
                <div class="bg-gray-50 px-4 py-3 flex justify-end">
                    <button @click="isKeteranganModalOpen = false" type="button"
                        class="px-4 py-2 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-cloak x-show="isConfirmModalOpen" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-3 py-4">
            <div x-show="isConfirmModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="isConfirmModalOpen = false"></div>

            <div x-show="isConfirmModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
                class="relative bg-white rounded-lg w-full max-w-md mx-auto shadow-xl">
                <form @submit.prevent="submitConfirmation()">
                    <div class="p-4">
                        <h3 class="text-base font-bold text-gray-900 border-b pb-2 mb-3" x-text="modalTitle"></h3>

                        <div class="space-y-3">
                            <div class="p-3 bg-orange-50 border border-orange-200 rounded">
                                <p class="text-xs font-semibold text-primary" x-text="'Item: ' + (selectedRequest ? selectedRequest.item : '')"></p>
                                <p class="text-xs text-primary">
                                    <i class="fas fa-box"></i> Qty Request: <span class="font-bold" x-text="selectedRequest ? selectedRequest.qty : ''"></span> Unit
                                </p>
                                <p x-show="modalMode === 'edit' && selectedRequest.konfirmasi_at" class="text-xs text-primary mt-1">
                                    <i class="fas fa-clock"></i> Dikonfirmasi: <span class="font-bold" x-text="formatDateTime(selectedRequest.konfirmasi_at)"></span>
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700">Jumlah Supply (Unit)</label>
                                <input type="number" x-model.number="confirmationData.supply_qty" min="0" required
                                    class="mt-1 w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary">
                            </div>

                            <div x-show="confirmationData.supply_qty !== null && selectedRequest && selectedRequest.qty !== null" class="p-2 text-xs rounded"
                                :class="getDiscrepancyClass()">
                                <p class="font-bold">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Status: <span x-text="getDiscrepancyStatus()"></span>
                                </p>
                                <p class="mt-0.5">
                                    Selisih: <span class="font-bold" x-text="getDiscrepancyText()"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 flex justify-end space-x-2">
                        <button @click="isConfirmModalOpen = false" type="button"
                            class="px-4 py-2 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="formLoading || confirmationData.supply_qty === null"
                            class="px-4 py-2 text-xs font-medium rounded text-white disabled:bg-gray-400"
                            :class="modalMode === 'confirm' ? 'bg-green-600 hover:bg-green-700' : 'bg-yellow-600 hover:bg-yellow-700'">
                            <i x-show="formLoading" class="fas fa-spinner fa-spin mr-1"></i>
                            <span x-text="modalMode === 'confirm' ? 'Konfirmasi' : 'Update'"></span>
                        </button>
                    </div>
                </form>
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
    document.addEventListener('alpine:init', () => {
        Alpine.data('inventoryFullfillment', () => ({
            API_URL: 'controller/inventory/route.php',
            loading: false,
            formLoading: false,
            data: [],
            itemList: [], // Tambahkan untuk menyimpan daftar item

            filters: {
                start_date: '<?php echo $today; ?>',
                end_date: '<?php echo $today; ?>',
                date_field: 'order_date',
                item: '',
                jenis: '',
                status: ''
            },
            // Default filter tertutup untuk layar kecil
            isFilterOpen: window.innerWidth >= 640, // true untuk desktop, false untuk mobile

            isKeteranganModalOpen: false,
            selectedKeterangan: '',
            isConfirmModalOpen: false,
            modalMode: 'confirm',
            modalTitle: 'Konfirmasi Supply',
            selectedRequest: null,
            confirmationData: {
                id_outbond_request: null,
                konfirmasi_id: null,
                supply_qty: 0,
                request_qty: null
            },

            notifications: [],

            async init() {
                await this.loadItems(); // Load daftar item saat inisialisasi
                this.loadData();

                // Handle resize untuk responsive behavior
                window.addEventListener('resize', () => {
                    this.isFilterOpen = window.innerWidth >= 640;
                });
            },

            // Fungsi baru untuk memuat daftar item
            async loadItems() {
                try {
                    const response = await fetch(`${this.API_URL}?action=get_items`);
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.itemList = result.data;
                    } else {
                        console.error('Gagal memuat daftar item:', result.message);
                    }
                } catch (error) {
                    console.error("Error loading items:", error);
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

            formatDate(dateString) {
                if (!dateString) return '-';
                const options = {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                };
                return new Date(dateString).toLocaleDateString('id-ID', options);
            },

            formatDateTime(dateTimeString) {
                if (!dateTimeString) return '-';
                const [datePart, timePart] = dateTimeString.split(' ');
                const dateOptions = {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                };
                const date = new Date(datePart).toLocaleDateString('id-ID', dateOptions);
                return `${date} ${timePart}`;
            },

            isSLAOverdue(slaDateString) {
                if (!slaDateString) return false;
                const slaDate = new Date(slaDateString);
                const today = new Date('<?php echo $today; ?>');
                slaDate.setHours(0, 0, 0, 0);
                today.setHours(0, 0, 0, 0);
                return slaDate < today;
            },

            getStatusBadgeClass(status) {
                if (status === 'Fullfill Pas') return 'bg-green-100 text-green-800 border border-green-200';
                if (status === 'Fullfill Lebih') return 'bg-blue-100 text-blue-800 border border-blue-200';
                if (status === 'Kurang Supply') return 'bg-red-100 text-red-800 border border-red-200';
                return 'bg-gray-100 text-gray-800';
            },

            // Fungsi baru untuk format keterangan yang langsung ditampilkan
            formatKeterangan(keterangan) {
                if (!keterangan) return '';
                const maxLength = 50; // Panjang maksimal sebelum dipotong
                if (keterangan.length > maxLength) {
                    return keterangan.substring(0, maxLength) + '<span class="text-primary font-semibold"> ... [klik untuk detail]</span>';
                }
                return keterangan;
            },

            formatDiscrepancy(discrepancy) {
                if (discrepancy === null) return '-';
                return discrepancy > 0 ? '+' + discrepancy : discrepancy;
            },

            getDiscrepancyStatus() {
                if (!this.selectedRequest) return '';
                const discrepancy = this.confirmationData.supply_qty - this.selectedRequest.qty;
                if (discrepancy > 0) return 'Fullfill Lebih';
                if (discrepancy < 0) return 'Kurang Supply';
                return 'Fullfill Pas';
            },

            getDiscrepancyText() {
                if (!this.selectedRequest) return '';
                const discrepancy = this.confirmationData.supply_qty - this.selectedRequest.qty;
                return this.formatDiscrepancy(discrepancy);
            },

            getDiscrepancyClass() {
                if (!this.selectedRequest) return '';
                const discrepancy = this.confirmationData.supply_qty - this.selectedRequest.qty;
                if (discrepancy > 0) return 'bg-blue-100 text-blue-800 border border-blue-300';
                if (discrepancy < 0) return 'bg-red-100 text-red-800 border border-red-300';
                return 'bg-green-100 text-green-800 border border-green-300';
            },

            openKeteranganModal(keterangan) {
                this.selectedKeterangan = keterangan || 'Tidak ada keterangan.';
                this.isKeteranganModalOpen = true;
            },

            openConfirmModal(mode, request) {
                this.modalMode = mode;
                this.selectedRequest = request;
                this.confirmationData.id_outbond_request = request.id;
                this.confirmationData.request_qty = request.qty;

                if (mode === 'confirm') {
                    this.modalTitle = 'Konfirmasi Supply Request';
                    this.confirmationData.supply_qty = request.qty;
                    this.confirmationData.konfirmasi_id = null;
                } else if (mode === 'edit') {
                    this.modalTitle = 'Edit Konfirmasi Supply';
                    this.confirmationData.supply_qty = request.supply_qty !== null ? request.supply_qty : request.qty;
                    this.confirmationData.konfirmasi_id = request.konfirmasi_id;
                }

                this.isConfirmModalOpen = true;
            },

            async loadData() {
                this.loading = true;
                const params = new URLSearchParams(this.filters).toString();
                const url = `${this.API_URL}?action=read&${params}`;

                try {
                    const response = await fetch(url);
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.data = result.data;
                    } else {
                        this.showNotification(`Gagal memuat data: ${result.message}`, 'error');
                    }
                } catch (error) {
                    console.error("Error loading data:", error);
                    this.showNotification('Terjadi kesalahan koneksi.', 'error');
                } finally {
                    this.loading = false;
                }
            },

            async submitConfirmation() {
                this.formLoading = true;
                const action = this.modalMode === 'confirm' ? 'confirm' : 'update_confirm';

                try {
                    const dataToSend = {
                        action: action,
                        ...this.confirmationData
                    };
                    const response = await fetch(this.API_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(dataToSend)
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        this.showNotification(result.message, 'success');
                        this.isConfirmModalOpen = false;
                        this.loadData();
                    } else {
                        this.showNotification(`Gagal ${action === 'confirm' ? 'konfirmasi' : 'update konfirmasi'}: ${result.message}`, 'error');
                    }
                } catch (error) {
                    console.error("Error submitting confirmation:", error);
                    this.showNotification('Terjadi kesalahan koneksi.', 'error');
                } finally {
                    this.formLoading = false;
                }
            }
        }));
    });
</script>