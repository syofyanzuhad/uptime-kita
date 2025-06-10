<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
// Impor Link dan usePage dari @inertiajs/vue3
// Penting: Untuk request seperti delete, post, put, kita akan menggunakan 'router'
import { Head, Link, usePage, router, usePoll } from '@inertiajs/vue3';
import type { Monitor, FlashMessage } from '@/types/monitor';
import { ref, watch } from 'vue';

const page = usePage();

// Pastikan props didefinisikan dengan benar dan diakses di template dengan 'props.' jika perlu
const props = defineProps<{
  monitors: Monitor[];
  flash?: FlashMessage;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Uptime Monitor',
        href: '/monitor',
    },
];

// State untuk notifikasi
const showNotification = ref(false);
const notificationMessage = ref('');
const notificationType = ref('');

// Watcher untuk properti flash
watch(() => page.props.flash as FlashMessage | undefined, (newFlash) => {
    if (newFlash) {
        notificationMessage.value = newFlash.message;
        notificationType.value = newFlash.type;
        showNotification.value = true;

        // Sembunyikan notifikasi setelah beberapa detik
        setTimeout(() => {
            showNotification.value = false;
        }, 60000);
    }
}, { deep: true });

// Fungsi untuk menghapus monitor
const deleteMonitor = (monitorId: number) => {
  if (confirm('Apakah Anda yakin ingin menghapus monitor ini?')) {
    // Menggunakan 'router.delete()' yang sudah diimpor dari @inertiajs/vue3
    router.delete(route('monitor.destroy', monitorId));
  }
};

// Use Inertia's usePoll for auto-refresh
usePoll(5000);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Uptime Monitor" />

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Uptime Monitor</h2>
        </template>

        <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div v-if="showNotification" :class="{
            'bg-green-100 border border-green-400 text-green-700 dark:bg-green-900 dark:border-green-700 dark:text-green-200': notificationType === 'success',
            'bg-red-100 border border-red-400 text-red-700 dark:bg-red-900 dark:border-red-700 dark:text-red-200': notificationType === 'error',
            }" role="alert" class="px-4 py-3 rounded relative mb-4">
            <span class="block sm:inline">{{ notificationMessage }}</span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" @click="showNotification = false">
                <svg class="fill-current h-6 w-6 text-gray-500 dark:text-gray-400" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.103l-2.651 3.746a1.2 1.2 0 1 1-1.697-1.697l3.746-2.651-3.746-2.651a1.2 1.2 0 0 1 1.697-1.697l2.651 3.746 2.651-3.746a1.2 1.2 0 0 1 1.697 1.697L11.103 10l3.746 2.651a1.2 1.2 0 0 1 0 1.698z"/></svg>
            </span>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Daftar Monitor</h3>
                <Link :href="route('monitor.create')" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white rounded-md">
                Tambah Monitor
                </Link>
            </div>

            <div v-if="props.monitors.length === 0" class="text-gray-600 dark:text-gray-400"> Belum ada monitor yang terdaftar.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        URL
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Status Uptime
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Terakhir Dicek
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Sertifikat
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Aksi
                    </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="monitor in props.monitors" :key="monitor.id"> <td class="px-6 py-4 whitespace-nowrap">
                        <a :href="monitor.url" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">{{ monitor.url }}</a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span
                        :class="{
                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': monitor.uptime_status === 'up',
                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': monitor.uptime_status === 'down',
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': monitor.uptime_status === 'not yet checked',
                        }"
                        class="px-2.5 py-0.5 rounded-full text-sm font-medium"
                        >
                        {{ monitor.uptime_status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ monitor.last_check_date ? new Date(monitor.last_check_date).toLocaleString() : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        <template v-if="monitor.certificate_check_enabled">
                        <span
                            :class="{
                            'text-green-600 dark:text-green-400': monitor.certificate_status === 'valid',
                            'text-red-600 dark:text-red-400': monitor.certificate_status === 'invalid',
                            'text-gray-600 dark:text-gray-400': monitor.certificate_status === 'not applicable',
                            }"
                        >
                            {{ monitor.certificate_status }}
                        </span>
                        <br>
                        <span v-if="monitor.certificate_expiration_date" class="text-xs text-gray-500 dark:text-gray-400">
                            Expired: {{ new Date(monitor.certificate_expiration_date).toLocaleDateString() }}
                        </span>
                        </template>
                        <span v-else class="text-gray-400 dark:text-gray-500">Tidak dicek</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <Link :href="route('monitor.edit', monitor.id)" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3">Edit</Link>
                        <button @click="deleteMonitor(monitor.id)" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 cursor-pointer">Hapus</button>
                    </td>
                    </tr>
                </tbody>
                </table>
            </div>
            </div>
        </div>
        </div>
    </AppLayout>
</template>