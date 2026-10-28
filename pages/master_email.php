<?php
// pages/master_email.php
?>

<div class="p-2 sm:p-4 bg-gray-50 min-h-screen" x-data="emailManager()">

    <h1 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
        <i class="fas fa-envelope-open-text text-primary mr-2"></i> Master Data Email Atasan
    </h1>

    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-600 mb-4 border-b pb-3">
            Halaman ini mengelola alamat email tetap yang digunakan untuk notifikasi. Hanya boleh ada <strong>satu email</strong> untuk Atasan 1 dan <strong>satu email</strong> untuk Atasan 2.
        </p>

        <template x-if="loading">
            <div class="py-6 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin text-xl"></i>
                <p class="mt-2 text-xs">Memuat data email...</p>
            </div>
        </template>

        <div x-show="!loading" class="space-y-4">

            <div class="p-3 border border-gray-200 rounded-lg bg-red-50 flex flex-col md:flex-row justify-between items-start md:items-center">
                <div class="md:w-3/4">
                    <h2 class="text-sm font-bold text-red-800 mb-1">
                        Atasan 1 (Persetujuan Awal)
                    </h2>
                    <template x-if="emails['atasan_1']">
                        <p class="text-xs text-gray-700 break-words">
                            <i class="fas fa-user-tag mr-2 text-red-600"></i> <span x-text="emails['atasan_1'].nama"></span><br>
                            <i class="fas fa-at mr-2 text-red-600"></i> <span x-text="emails['atasan_1'].email"></span>
                        </p>
                    </template>
                    <template x-if="!emails['atasan_1']">
                        <p class="text-red-500 text-xs font-semibold italic">
                            <i class="fas fa-exclamation-triangle mr-2"></i> Data email Atasan 1 belum diatur.
                        </p>
                    </template>
                </div>
                <div class="mt-2 md:mt-0 md:w-1/4 flex justify-start md:justify-end">
                    <template x-if="emails['atasan_1']">
                        <button @click="openModal('edit', emails['atasan_1'])"
                            class="px-3 py-1 bg-yellow-500 text-white text-xs font-semibold rounded hover:bg-yellow-600 transition duration-150 flex items-center">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </button>
                    </template>
                    <template x-if="!emails['atasan_1']">
                        <button @click="openModal('create', {role: 'atasan_1'})"
                            class="px-3 py-1 bg-primary text-white text-xs font-semibold rounded hover:bg-orange-600 transition duration-150 flex items-center">
                            <i class="fas fa-plus mr-1"></i> Tambah
                        </button>
                    </template>
                </div>
            </div>

            <div class="p-3 border border-gray-200 rounded-lg bg-blue-50 flex flex-col md:flex-row justify-between items-start md:items-center">
                <div class="md:w-3/4">
                    <h2 class="text-sm font-bold text-blue-800 mb-1">
                        Atasan 2 (Persetujuan Akhir)
                    </h2>
                    <template x-if="emails['atasan_2']">
                        <p class="text-xs text-gray-700 break-words">
                            <i class="fas fa-user-tag mr-2 text-blue-600"></i> <span x-text="emails['atasan_2'].nama"></span><br>
                            <i class="fas fa-at mr-2 text-blue-600"></i> <span x-text="emails['atasan_2'].email"></span>
                        </p>
                    </template>
                    <template x-if="!emails['atasan_2']">
                        <p class="text-blue-500 text-xs font-semibold italic">
                            <i class="fas fa-exclamation-triangle mr-2"></i> Data email Atasan 2 belum diatur.
                        </p>
                    </template>
                </div>
                <div class="mt-2 md:mt-0 md:w-1/4 flex justify-start md:justify-end">
                    <template x-if="emails['atasan_2']">
                        <button @click="openModal('edit', emails['atasan_2'])"
                            class="px-3 py-1 bg-yellow-500 text-white text-xs font-semibold rounded hover:bg-yellow-600 transition duration-150 flex items-center">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </button>
                    </template>
                    <template x-if="!emails['atasan_2']">
                        <button @click="openModal('create', {role: 'atasan_2'})"
                            class="px-3 py-1 bg-primary text-white text-xs font-semibold rounded hover:bg-orange-600 transition duration-150 flex items-center">
                            <i class="fas fa-plus mr-1"></i> Tambah
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

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
                            <i class="fas fa-envelope text-primary text-sm"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-base font-medium text-gray-900" x-text="modalTitle"></h3>
                        </div>
                    </div>

                    <form @submit.prevent="submitForm()" class="space-y-3">
                        <div x-show="modalMode === 'create'" class="p-2 bg-gray-100 rounded text-xs font-medium">
                            <label class="block text-gray-700">Role Email:</label>
                            <span class="text-primary font-bold uppercase" x-text="formData.role.replace('_', ' ')"></span>
                            <input type="hidden" x-model="formData.role">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">Nama Atasan</label>
                            <input type="text" x-model="formData.nama" required
                                class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">Alamat Email</label>
                            <input type="email" x-model="formData.email" required
                                class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 text-xs focus:ring-primary focus:border-primary">
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
        Alpine.data('emailManager', () => ({
            API_URL: 'controller/email/route.php',
            loading: true,
            formLoading: false,

            emails: {
                'atasan_1': null,
                'atasan_2': null
            },

            isModalOpen: false,
            modalMode: 'create',
            modalTitle: 'Tambah Email Baru',
            submitButtonText: 'Simpan Email',
            formData: {
                id: null,
                nama: '',
                email: '',
                role: ''
            },

            notifications: [],

            init() {
                this.loadEmails();
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

            async loadEmails() {
                this.loading = true;
                const url = `${this.API_URL}?action=read`;

                try {
                    const response = await fetch(url);
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.emails = result.data;
                    } else {
                        this.showNotification(`Gagal memuat data: ${result.message}`, 'error');
                    }
                } catch (error) {
                    console.error("Error loading emails:", error);
                    this.showNotification('Terjadi kesalahan koneksi.', 'error');
                } finally {
                    this.loading = false;
                }
            },

            openModal(mode, data = null) {
                this.modalMode = mode;
                this.formData = {
                    id: null,
                    nama: '',
                    email: '',
                    role: ''
                };

                if (mode === 'create' && data) {
                    this.modalTitle = 'Tambah Email untuk ' + data.role.replace('_', ' ').toUpperCase();
                    this.submitButtonText = 'Simpan Email';
                    this.formData.role = data.role;
                } else if (mode === 'edit' && data) {
                    this.modalTitle = 'Edit Email ' + data.role.replace('_', ' ').toUpperCase();
                    this.submitButtonText = 'Update Email';
                    this.formData = {
                        ...data
                    };
                }

                this.isModalOpen = true;
            },

            async submitForm() {
                this.formLoading = true;
                const action = this.modalMode;

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
                        this.loadEmails();
                    } else {
                        this.showNotification(`Gagal ${action === 'create' ? 'menyimpan' : 'mengupdate'} data: ${result.message}`, 'error');
                    }
                } catch (error) {
                    console.error("Error submitting form:", error);
                    this.showNotification('Terjadi kesalahan koneksi.', 'error');
                } finally {
                    this.formLoading = false;
                }
            }
        }));
    });
</script>