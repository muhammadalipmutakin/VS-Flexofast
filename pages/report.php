<?php
// pages/report_outbound.php

// Inisialisasi tanggal default (misal 30 hari terakhir)
$today = date('Y-m-d');
$last_month = date('Y-m-d', strtotime('-30 days'));
?>

<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>

<div class="p-2 sm:p-4 bg-gray-50 min-h-screen" x-data="outboundReportManager('<?= $last_month ?>', '<?= $today ?>')">

    <h1 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
        <i class="fas fa-file-invoice text-primary mr-2"></i> Laporan Detail Outbound Request
    </h1>

    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="flex flex-col md:flex-row gap-3 items-end">
            <div class="flex-1 w-full md:w-auto">
                <label for="startDate" class="block text-xs font-medium text-gray-700 mb-1">Tanggal Awal Order</label>
                <input type="date" x-model="startDate" id="startDate" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-primary focus:border-primary">
            </div>
            <div class="flex-1 w-full md:w-auto">
                <label for="endDate" class="block text-xs font-medium text-gray-700 mb-1">Tanggal Akhir Order</label>
                <input type="date" x-model="endDate" id="endDate" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-primary focus:border-primary">
            </div>

            <button @click="loadReport()" :disabled="loading"
                class="w-full md:w-auto px-4 py-2 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-orange-600 transition duration-150 flex items-center justify-center disabled:bg-gray-400 disabled:cursor-not-allowed">
                <i x-show="loading" class="fas fa-spinner fa-spin mr-2"></i>
                <i x-show="!loading" class="fas fa-eye mr-2"></i>
                Tampilkan Data
            </button>

            <button @click="exportReport()" :disabled="loading || reportData.length === 0"
                class="w-full md:w-auto px-4 py-2 bg-green-600 text-white text-xs font-semibold rounded-lg hover:bg-green-700 transition duration-150 flex items-center justify-center disabled:bg-gray-400 disabled:cursor-not-allowed">
                <i class="fas fa-file-excel mr-2"></i> Export Excel (.xlsx)
            </button>
        </div>
    </div>

    <template x-if="!loading && reportData.length > 0">
        <p class="text-sm font-medium text-gray-700 mb-3">
            Total Ditemukan: <span class="font-bold text-primary" x-text="reportData.length"></span> Konfirmasi
            (Periode <span x-text="formatDate(startDate)"></span> s/d <span x-text="formatDate(endDate)"></span>)
        </p>
    </template>


    <div class="hidden lg:block bg-white rounded-lg shadow overflow-x-auto mb-4">
        <table id="reportTable" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-orange-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase min-w-[30px]">No.</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase min-w-[100px]">Tgl Order</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase min-w-[120px]">No. Order</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase min-w-[150px]">Item / Brand</th>
                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-700 uppercase">Req QTY</th>
                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-700 uppercase">Supply QTY</th>
                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-700 uppercase">Discrepancy</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase min-w-[120px]">Status</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase min-w-[150px]">Dibuat (OR)</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase min-w-[150px]">Dikonfirmasi</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase min-w-[150px]">Keterangan</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-if="loading">
                    <template x-for="i in 5" :key="i">
                        <tr>
                            <td colspan="11" class="px-3 py-2">
                                <div class="h-4 bg-gray-200 rounded animate-pulse w-full"></div>
                            </td>
                        </tr>
                    </template>
                </template>

                <template x-if="!loading && reportData.length > 0">
                    <template x-for="(data, index) in reportData" :key="data.or_id">
                        <tr class="hover:bg-gray-50">
                            <td x-text="index + 1" class="px-3 py-2 text-xs text-gray-500"></td>
                            <td x-text="data.order_date" class="px-3 py-2 text-xs font-medium text-gray-700"></td>
                            <td x-text="data.order_number || '-'" class="px-3 py-2 text-xs text-primary font-medium"></td>
                            <td class="px-3 py-2 text-xs">
                                <span class="font-semibold text-gray-900" x-text="data.item"></span><br>
                                <span class="text-gray-500" x-text="data.brand || '-'"></span>
                            </td>
                            <td x-text="data.request_qty" class="px-3 py-2 text-xs text-center font-medium"></td>
                            <td x-text="data.supply_qty || '-'" class="px-3 py-2 text-xs text-center font-bold"
                                :class="data.supply_qty === data.request_qty ? 'text-green-600' : 'text-orange-600'">
                            </td>
                            <td x-text="data.discrepancy" class="px-3 py-2 text-xs text-center"
                                :class="data.discrepancy < 0 ? 'text-red-600 font-bold' : 'text-gray-700'">
                            </td>
                            <td class="px-3 py-2 text-xs">
                                <span :class="{
                                    'bg-green-100 text-green-800': data.status === 'Fullfill Pas',
                                    'bg-blue-100 text-blue-800': data.status === 'Fullfill Lebih',
                                    'bg-red-100 text-red-800': data.status === 'Kurang Supply'
                                }" class="px-2 py-0.5 rounded-full font-semibold" x-text="data.status"></span>
                                <span class="block text-gray-500 mt-1" x-text="`(${data.jenis})`"></span>
                            </td>
                            <td x-text="data.or_created_at_fmt" class="px-3 py-2 text-xs text-gray-600"></td>
                            <td x-text="data.konfirmasi_at_fmt" class="px-3 py-2 text-xs text-gray-600 font-medium"></td>
                            <td x-text="data.keterangan || '-'" class="px-3 py-2 text-xs text-gray-600 max-w-xs truncate"></td>
                        </tr>
                    </template>
                </template>

                <template x-if="!loading && reportData.length === 0">
                    <tr>
                        <td colspan="11" class="px-3 py-4 text-center text-xs text-gray-500">
                            <i class="fas fa-search-minus text-2xl mb-2 text-gray-300"></i>
                            <p>Tidak ada data laporan yang ditemukan dalam rentang tanggal tersebut.</p>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div class="lg:hidden space-y-3 mb-4">
        <template x-if="loading">
            <template x-for="i in 3" :key="i">
                <div class="bg-white rounded-lg shadow p-4 animate-pulse">
                    <div class="h-4 bg-gray-200 rounded w-full mb-2"></div>
                    <div class="h-3 bg-gray-200 rounded w-2/3 mb-1"></div>
                    <div class="h-3 bg-gray-200 rounded w-full"></div>
                </div>
            </template>
        </template>
        <template x-if="!loading && reportData.length > 0">
            <template x-for="(data, index) in reportData" :key="data.or_id">
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 hover:shadow-md transition duration-150">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-semibold text-primary" x-text="`No. ${index + 1} | ${data.order_number || 'ID OR: ' + data.or_id}`"></span>
                        <span :class="{
                            'bg-green-100 text-green-800': data.status === 'Fullfill Pas',
                            'bg-blue-100 text-blue-800': data.status === 'Fullfill Lebih',
                            'bg-red-100 text-red-800': data.status === 'Kurang Supply'
                        }" class="px-2 py-0.5 text-xs rounded-full font-semibold" x-text="data.status"></span>
                    </div>

                    <div class="text-sm font-bold text-gray-900 mb-2" x-text="data.item"></div>

                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="font-semibold text-gray-600">Tgl Order:</span>
                            <span class="text-gray-700 ml-1" x-text="data.order_date"></span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-600">Brand:</span>
                            <span class="text-gray-700 ml-1" x-text="data.brand || '-'"></span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-600">QTY Request:</span>
                            <span class="text-gray-700 ml-1" x-text="data.request_qty"></span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-600">QTY Supply:</span>
                            <span class="text-gray-700 ml-1 font-bold"
                                :class="data.supply_qty === data.request_qty ? 'text-green-600' : 'text-orange-600'"
                                x-text="data.supply_qty || '-'">
                            </span>
                        </div>
                        <div class="col-span-2">
                            <span class="font-semibold text-gray-600">Discrepancy:</span>
                            <span class="text-gray-700 ml-1 font-bold"
                                :class="data.discrepancy < 0 ? 'text-red-600' : 'text-gray-700'"
                                x-text="data.discrepancy">
                            </span>
                            <span class="text-gray-500 ml-2" x-text="`(${data.jenis})`"></span>
                        </div>
                        <div class="col-span-2 text-xs border-t pt-2 mt-2">
                            <span class="font-semibold text-gray-600">Dibuat/Konfirmasi:</span>
                            <span class="text-gray-700 ml-1" x-text="`${data.or_created_at_fmt} / ${data.konfirmasi_at_fmt}`"></span>
                        </div>
                    </div>
                </div>
            </template>
        </template>
        <template x-if="!loading && reportData.length === 0">
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <i class="fas fa-search-minus text-3xl mb-3 text-gray-300"></i>
                <p class="text-xs text-gray-500">Tidak ada data laporan yang ditemukan.</p>
            </div>
        </template>
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
        Alpine.data('outboundReportManager', (initialStartDate, initialEndDate) => ({
            // Mengganti API_URL sesuai dengan nama file controller yang terakhir
            API_URL: 'controller/report/route.php',
            loading: false,
            startDate: initialStartDate,
            endDate: initialEndDate,
            reportData: [],
            notifications: [],

            init() {
                // Opsional: Langsung muat data saat halaman dimuat
                // this.loadReport();
            },

            // --- Utility Functions (Tidak ada perubahan) ---
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
            formatDate(dateStr) {
                if (!dateStr) return '-';
                const options = {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                };
                return new Date(dateStr).toLocaleDateString('id-ID', options);
            },

            // --- Data Load Function (Tidak ada perubahan) ---
            async loadReport() {
                if (!this.startDate || !this.endDate) {
                    this.showNotification('Harap masukkan Tanggal Awal dan Akhir.', 'error');
                    return;
                }
                this.loading = true;
                this.reportData = [];

                const url = `${this.API_URL}?action=read&start_date=${this.startDate}&end_date=${this.endDate}`;

                try {
                    const response = await fetch(url);
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.reportData = result.data || [];
                        this.showNotification(`Berhasil memuat ${this.reportData.length} data.`, 'success');
                    } else {
                        this.showNotification(`Gagal memuat data: ${result.message}`, 'error');
                    }
                } catch (error) {
                    console.error("Error loading report:", error);
                    this.showNotification('Terjadi kesalahan koneksi saat memuat laporan.', 'error');
                } finally {
                    this.loading = false;
                }
            },

            // --- Export XLSX Function (Diperbaiki untuk menggunakan Nomor Urut) ---
            exportReport() {
                if (this.reportData.length === 0) {
                    this.showNotification('Tidak ada data untuk diexport.', 'error');
                    return;
                }

                try {
                    // 1. Definisikan Header sesuai kolom yang detail (Mengganti 'ID Outbound' menjadi 'No. Urut')
                    const headers = [
                        'No. Urut', // Perubahan di sini
                        'ID Outbound (Internal)', // Menambahkan ID internal di belakang No. Urut
                        'Tanggal Order',
                        'Tanggal SLA',
                        'Nomor Order',
                        'Nama Item',
                        'Brand Item',
                        'QTY Request',
                        'QTY Supply',
                        'Discrepancy (Supply-Request)',
                        'Status Konfirmasi',
                        'Keterangan OR',
                        'Jenis OR',
                        'Dibuat Tgl (OR)',
                        'Dikonfirmasi Tgl'
                    ];

                    // 2. Map data ke format yang sesuai dengan urutan Header
                    const dataForExport = this.reportData.map((row, index) => [
                        index + 1, // Menggunakan Nomor Urut
                        row.or_id, // Tetap menyimpan ID Internal untuk referensi
                        row.order_date,
                        row.sla_date,
                        row.order_number,
                        row.item,
                        row.brand,
                        row.request_qty,
                        row.supply_qty,
                        row.discrepancy,
                        row.status,
                        row.keterangan,
                        row.jenis,
                        row.or_created_at_fmt,
                        row.konfirmasi_at_fmt
                    ]);

                    // Tambahkan header di awal array data
                    const finalData = [headers, ...dataForExport];

                    // 3. Buat workbook dan worksheet
                    const workbook = XLSX.utils.book_new();
                    const worksheet = XLSX.utils.aoa_to_sheet(finalData);

                    // 4. Tambahkan worksheet ke workbook
                    XLSX.utils.book_append_sheet(workbook, worksheet, "Laporan Outbound");

                    // 5. Tentukan nama file
                    const filename = `Laporan_Outbound_${this.startDate}_to_${this.endDate}.xlsx`;

                    // 6. Tulis file dan pemicu download
                    XLSX.writeFile(workbook, filename);

                    this.showNotification('Berhasil meng-export data ke XLSX.', 'success');
                } catch (e) {
                    console.error("Export Error:", e);
                    this.showNotification('Gagal meng-export. Pastikan CDN SheetJS dimuat.', 'error');
                }
            }
        }));
    });
</script>