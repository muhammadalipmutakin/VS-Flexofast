<?php
// pages/dashboard.php
?>

<div class="p-2 sm:p-4 bg-gray-50 min-h-screen" x-data="dashboardManager()">

    <h1 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
        <i class="fas fa-chart-line text-primary mr-2"></i> Dashboard Validasi Stock - FLEXOFAST
    </h1>

    <template x-if="loading">
        <div class="py-8 text-center text-gray-500">
            <i class="fas fa-spinner fa-spin text-2xl text-primary"></i>
            <p class="mt-2">Memuat data dashboard...</p>
        </div>
    </template>

    <div x-show="!loading" class="space-y-4">

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="bg-white p-3 rounded-lg shadow border-l-4 border-gray-400">
                <p class="text-xs font-medium text-gray-500">Total Item</p>
                <p class="text-xl font-bold text-gray-900 mt-1" x-text="summary.total_items.toLocaleString()"></p>
                <div class="mt-1 text-xs text-gray-500 flex items-center">
                    <i class="fas fa-boxes mr-1"></i> Data Master
                </div>
            </div>

            <div class="bg-white p-3 rounded-lg shadow border-l-4 border-primary">
                <p class="text-xs font-medium text-gray-500">Permintaan Hari Ini</p>
                <p class="text-xl font-bold text-primary mt-1" x-text="summary.total_requests_today.toLocaleString()"></p>
                <div class="mt-1 text-xs text-gray-500 flex items-center">
                    <i class="fas fa-inbox mr-1"></i> Total request
                </div>
            </div>

            <div class="bg-white p-3 rounded-lg shadow border-l-4 border-green-500">
                <p class="text-xs font-medium text-gray-500">Dikonfirmasi</p>
                <p class="text-xl font-bold text-green-600 mt-1" x-text="summary.total_confirmed_today.toLocaleString()"></p>
                <div class="mt-1 text-xs text-gray-500 flex items-center">
                    <i class="fas fa-check-circle mr-1"></i> Sudah dikonfirmasi
                </div>
            </div>

            <div class="bg-white p-3 rounded-lg shadow border-l-4 border-red-500 cursor-pointer"
                @click="summary.total_unconfirmed_today > 0 ? isUnconfirmedModalOpen = true : null">
                <p class="text-xs font-medium text-gray-500">Belum Dikonfirmasi</p>
                <p class="text-xl font-bold text-red-600 mt-1" x-text="summary.total_unconfirmed_today.toLocaleString()"></p>
                <div class="mt-1 text-xs font-semibold"
                    :class="summary.total_unconfirmed_today > 0 ? 'text-red-500' : 'text-gray-500'">
                    <span x-show="summary.total_unconfirmed_today > 0" class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i> KLIK untuk Detail
                    </span>
                    <span x-show="summary.total_unconfirmed_today === 0" class="flex items-center">
                        <i class="fas fa-check mr-1"></i> Sudah beres
                    </span>
                </div>
            </div>

            <div class="bg-white p-3 rounded-lg shadow border-l-4 border-yellow-500">
                <p class="text-xs font-medium text-gray-500">Fullfill %</p>
                <p class="text-xl font-bold text-yellow-600 mt-1">
                    <span x-text="summary.percentage_fullfill_today"></span>%
                </p>
                <div class="mt-1 text-xs text-gray-500 flex items-center">
                    <i class="fas fa-percentage mr-1"></i> Fullfill Pas/Lebih
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="bg-white p-4 rounded-lg shadow">
            <h2 class="text-base font-semibold text-gray-800 mb-3 border-b pb-2">
                <i class="fas fa-chart-area mr-2 text-primary"></i> Trend 7 Hari Terakhir
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Left Chart -->
                <div class="bg-gray-50 p-3 rounded border border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2 text-center">
                        <i class="fas fa-chart-bar mr-2 text-blue-500"></i> Total Permintaan
                    </h3>
                    <div class="h-48">
                        <canvas id="chart-requests"></canvas>
                    </div>
                </div>

                <!-- Right Chart -->
                <div class="bg-gray-50 p-3 rounded border border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2 text-center">
                        <i class="fas fa-percentage mr-2 text-primary"></i> Persentase Fullfill
                    </h3>
                    <div class="h-48">
                        <canvas id="chart-percentage"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tables Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Confirmed Today -->
            <div class="bg-white p-4 rounded-lg shadow">
                <h2 class="text-base font-semibold text-gray-800 mb-3 border-b pb-2 text-green-600">
                    <i class="fas fa-clipboard-check mr-2"></i> Sudah Dikonfirmasi
                </h2>
                <div class="overflow-x-auto max-h-64">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">No. Order</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase">Req. Qty</th>
                                <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase">Supply Qty</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(req, index) in confirmed_today" :key="req.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-2 py-1 whitespace-nowrap text-xs text-gray-700" x-text="req.order_number"></td>
                                    <td class="px-2 py-1 whitespace-nowrap text-xs font-medium text-primary" x-text="req.item"></td>
                                    <td class="px-2 py-1 whitespace-nowrap text-xs text-right text-gray-700" x-text="parseInt(req.requested_qty).toLocaleString()"></td>
                                    <td class="px-2 py-1 whitespace-nowrap text-xs text-right text-green-600 font-semibold" x-text="parseInt(req.supply_qty).toLocaleString()"></td>
                                    <td class="px-2 py-1 whitespace-nowrap text-xs font-semibold"
                                        :class="{
                                            'text-green-500': req.status === 'Fullfill Pas', 
                                            'text-yellow-500': req.status === 'Fullfill Lebih', 
                                            'text-red-500': req.status === 'Kurang Supply'
                                        }"
                                        x-text="req.status"></td>
                                </tr>
                            </template>
                            <template x-if="confirmed_today.length === 0">
                                <tr>
                                    <td colspan="5" class="px-2 py-3 text-center text-gray-500 italic">Tidak ada data</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Unconfirmed Today -->
            <div class="bg-white p-4 rounded-lg shadow">
                <h2 class="text-base font-semibold text-gray-800 mb-3 border-b pb-2 text-red-600">
                    <i class="fas fa-clock mr-2"></i> Belum Dikonfirmasi
                </h2>
                <div class="overflow-x-auto max-h-64">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">No. Order</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qty Req.</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Jenis</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(req, index) in unconfirmed_today" :key="req.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-2 py-1 whitespace-nowrap text-xs text-gray-700" x-text="req.order_number"></td>
                                    <td class="px-2 py-1 whitespace-nowrap text-xs font-medium text-primary" x-text="req.item"></td>
                                    <td class="px-2 py-1 whitespace-nowrap text-xs text-right font-medium text-red-600" x-text="parseInt(req.qty).toLocaleString()"></td>
                                    <td class="px-2 py-1 whitespace-nowrap text-xs font-semibold"
                                        :class="req.jenis === 'butuh_validasi' ? 'text-blue-500' : 'text-green-500'"
                                        x-text="req.jenis.replace('_', ' ').toUpperCase()"></td>
                                </tr>
                            </template>
                            <template x-if="unconfirmed_today.length === 0">
                                <tr>
                                    <td colspan="4" class="px-2 py-3 text-center text-gray-500 italic">Tidak ada data</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Item Details -->
        <div class="bg-white p-4 rounded-lg shadow">
            <h2 class="text-base font-semibold text-gray-800 mb-3 border-b pb-2">
                <i class="fas fa-boxes mr-2 text-primary"></i> Rekap Detail (Per Item)
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total Qty Req.</th>
                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total Qty Supply</th>
                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total Req.</th>
                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total Conf.</th>
                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total Fullfill</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(detail, index) in details_today" :key="detail.item">
                            <tr class="hover:bg-gray-50">
                                <td class="px-2 py-2 whitespace-nowrap text-xs font-medium text-primary" x-text="detail.item"></td>
                                <td class="px-2 py-2 whitespace-nowrap text-xs text-right text-gray-700" x-text="parseInt(detail.total_requested_qty).toLocaleString()"></td>
                                <td class="px-2 py-2 whitespace-nowrap text-xs text-right text-gray-700" x-text="parseInt(detail.total_supplied_qty).toLocaleString()"></td>
                                <td class="px-2 py-2 whitespace-nowrap text-xs text-right text-gray-700" x-text="parseInt(detail.total_requests).toLocaleString()"></td>
                                <td class="px-2 py-2 whitespace-nowrap text-xs text-right text-green-600 font-semibold" x-text="parseInt(detail.total_confirmed).toLocaleString()"></td>
                                <td class="px-2 py-2 whitespace-nowrap text-xs text-right font-semibold"
                                    :class="parseInt(detail.fullfill_count) > 0 ? 'text-yellow-600' : 'text-gray-500'"
                                    x-text="parseInt(detail.fullfill_count).toLocaleString()"></td>
                            </tr>
                        </template>
                        <template x-if="details_today.length === 0">
                            <tr>
                                <td colspan="6" class="px-2 py-3 text-center text-gray-500 italic">Tidak ada data</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Unconfirmed Modal -->
    <div x-cloak x-show="isUnconfirmedModalOpen" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-3 py-4">
            <div x-show="isUnconfirmedModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="isUnconfirmedModalOpen = false"></div>

            <div x-show="isUnconfirmedModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
                class="relative bg-white rounded-lg w-full max-w-4xl mx-auto shadow-xl">
                <div class="p-4">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0 flex items-center justify-center h-8 w-8 rounded-full bg-red-100">
                            <i class="fas fa-exclamation-triangle text-red-600 text-sm"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-base font-medium text-red-600">
                                <i class="fas fa-bell mr-1"></i> Detail Outbond Request Belum Dikonfirmasi
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">
                                Terdapat <span class="font-bold text-red-600" x-text="unconfirmed_today.length"></span> Outbond Request yang belum dikonfirmasi.
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto max-h-64">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">No. Order</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                    <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qty Req.</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Order Date</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Jenis</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="(req, index) in unconfirmed_today" :key="req.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-2 py-1 whitespace-nowrap text-xs font-semibold text-gray-700" x-text="index + 1"></td>
                                        <td class="px-2 py-1 whitespace-nowrap text-xs text-gray-700" x-text="req.order_number"></td>
                                        <td class="px-2 py-1 whitespace-nowrap text-xs text-gray-900" x-text="req.item"></td>
                                        <td class="px-2 py-1 whitespace-nowrap text-xs text-right font-medium text-red-600" x-text="parseInt(req.qty).toLocaleString()"></td>
                                        <td class="px-2 py-1 whitespace-nowrap text-xs text-gray-700" x-text="req.order_date"></td>
                                        <td class="px-2 py-1 whitespace-nowrap text-xs font-semibold"
                                            :class="req.jenis === 'butuh_validasi' ? 'text-blue-500' : 'text-green-500'"
                                            x-text="req.jenis.replace('_', ' ').toUpperCase()"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 flex justify-end">
                    <button @click="isUnconfirmedModalOpen = false" type="button"
                        class="px-4 py-2 bg-primary text-white text-xs font-medium rounded hover:bg-orange-600 transition duration-150">
                        Tutup
                    </button>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('dashboardManager', () => ({
            API_URL_DASHBOARD: 'controller/dashboard/route.php',
            loading: true,
            isUnconfirmedModalOpen: false,

            summary: {
                total_items: 0,
                total_requests_today: 0,
                total_confirmed_today: 0,
                total_unconfirmed_today: 0,
                percentage_fullfill_today: 0
            },
            chart_trend: {
                labels: [],
                requests: [],
                fullfill_percent: []
            },
            confirmed_today: [],
            unconfirmed_today: [],
            details_today: [],

            chartRequestsInstance: null,
            chartPercentageInstance: null,
            notifications: [],

            init() {
                this.loadDashboardData();
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

            async loadDashboardData() {
                this.loading = true;
                try {
                    const response = await fetch(this.API_URL_DASHBOARD);
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.summary = result.data.summary;
                        this.chart_trend = result.data.chart_trend;
                        this.confirmed_today = result.data.confirmed_today;
                        this.unconfirmed_today = result.data.unconfirmed_today;
                        this.details_today = result.data.details_today;

                        this.renderCharts();

                    } else {
                        this.showNotification(`Gagal memuat data: ${result.message}`, 'error');
                    }
                } catch (error) {
                    console.error("Error loading dashboard data:", error);
                    this.showNotification('Terjadi kesalahan koneksi.', 'error');
                } finally {
                    this.loading = false;
                    if (this.summary.total_unconfirmed_today > 0) {
                        setTimeout(() => {
                            this.isUnconfirmedModalOpen = true;
                        }, 1000);
                    }
                }
            },

            renderCharts() {
                if (this.chartRequestsInstance) this.chartRequestsInstance.destroy();
                if (this.chartPercentageInstance) this.chartPercentageInstance.destroy();

                // Chart 1: Total Permintaan
                const requestsCtx = document.getElementById('chart-requests');
                if (requestsCtx) {
                    this.chartRequestsInstance = new Chart(requestsCtx, {
                        type: 'bar',
                        data: {
                            labels: this.chart_trend.labels,
                            datasets: [{
                                label: 'Total Permintaan',
                                data: this.chart_trend.requests,
                                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                                borderColor: 'rgba(59, 130, 246, 1)',
                                borderWidth: 1,
                                borderRadius: 4,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                },
                                x: {
                                    ticks: {
                                        maxRotation: 45
                                    }
                                }
                            }
                        }
                    });
                }

                // Chart 2: Persentase Fullfill
                const percentageCtx = document.getElementById('chart-percentage');
                if (percentageCtx) {
                    this.chartPercentageInstance = new Chart(percentageCtx, {
                        type: 'line',
                        data: {
                            labels: this.chart_trend.labels,
                            datasets: [{
                                label: 'Persentase Fullfill',
                                data: this.chart_trend.fullfill_percent,
                                borderColor: 'rgba(249, 115, 22, 1)',
                                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    ticks: {
                                        callback: function(value) {
                                            return value + '%';
                                        }
                                    }
                                },
                                x: {
                                    ticks: {
                                        maxRotation: 45
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }));
    });
</script>