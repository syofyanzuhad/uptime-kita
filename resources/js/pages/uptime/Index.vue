<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
// Impor Link dan usePage dari @inertiajs/vue3
// Penting: Untuk request seperti delete, post, put, kita akan menggunakan 'router'
import { Head, Link, router } from '@inertiajs/vue3';
import type { Monitor, Paginator, PaginatorLink } from '@/types/monitor';
import { ref, onMounted, onUnmounted } from 'vue';
// Import Dialog and Button components for modal
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue';
import DialogDescription from '@/components/ui/dialog/DialogDescription.vue';
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue';
import Button from '@/components/ui/button/Button.vue';
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/Pagination.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

// Pastikan props didefinisikan dengan benar dan diakses di template dengan 'props.' jika perlu
const props = defineProps<{
  monitors: Paginator<Monitor>;
  search?: string;
  statusFilter?: string;
  visibilityFilter?: string;
  perPage?: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Uptime Monitor',
        href: '/monitor',
    },
];

// Countdown timer state
const countdown = ref(0);
let countdownInterval: number | null = null;

// Function to calculate seconds until next minute
const calculateSecondsUntilNextMinute = () => {
  const now = new Date();
  return 60 - now.getSeconds();
};

// Function to start countdown
const startCountdown = () => {
  const updateCountdown = () => {
    const secondsUntilNextMinute = calculateSecondsUntilNextMinute();
    countdown.value = secondsUntilNextMinute;

    // If we're at the start of a minute, trigger a refresh
    if (secondsUntilNextMinute === 60) {
      router.reload({ only: ['monitors'] });
    }
  };

  // Initial update
  updateCountdown();

  // Update every second
  if (countdownInterval) {
    clearInterval(countdownInterval);
  }
  countdownInterval = setInterval(updateCountdown, 1000);
};

// Start countdown when component is mounted
onMounted(() => {
  startCountdown();
});

// Clean up interval when component is unmounted
onUnmounted(() => {
  if (countdownInterval) {
    clearInterval(countdownInterval);
  }
});

// Fungsi untuk menghapus monitor
// Modal state
const isDeleteModalOpen = ref(false);
const monitorToDelete = ref<Monitor | null>(null);

const openDeleteModal = (monitor: Monitor) => {
  monitorToDelete.value = monitor;
  isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false;
  monitorToDelete.value = null;
};

const confirmDeleteMonitor = () => {
  if (monitorToDelete.value) {
    router.delete(route('monitor.destroy', monitorToDelete.value.id), {
      onSuccess: () => closeDeleteModal(),
      onFinish: () => closeDeleteModal(),
    });
  }
};

const search = ref(props.search || '');
const statusFilter = ref(props.statusFilter || 'all');
const visibilityFilter = ref(props.visibilityFilter || 'all');
const perPage = ref((props.perPage as number) || 15);

function submitSearch() {
  router.get(route('monitor.index'), {
    search: search.value,
    status_filter: statusFilter.value,
    visibility_filter: visibilityFilter.value,
    per_page: perPage.value,
  }, { preserveState: true, only: ['monitors', 'search', 'statusFilter', 'perPage', 'visibilityFilter'] });
}

function clearSearch() {
  search.value = '';
  submitSearch();
}

function onStatusFilterChange() {
  submitSearch();
}

function onVisibilityFilterChange() {
  submitSearch();
}

function onPerPageChange() {
  submitSearch();
}

function onPaginationLinkClick(link: PaginatorLink) {
  if (link.url) {
    // Append per_page to pagination link
    const url = new URL(link.url, window.location.origin);
    url.searchParams.set('per_page', perPage.value.toString());
    router.visit(url.pathname + url.search, { preserveState: true, only: ['monitors', 'search', 'statusFilter', 'perPage'] });
  }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Uptime Monitor" />

        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Uptime Monitor</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Daftar Monitor</h3>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Next refresh in: {{ countdown }}s
                    </div>
                    <Link :href="route('monitor.create')" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white rounded-md">
                    Tambah Monitor
                    </Link>
                </div>

                <!-- Search Bar & Filter -->
                <form @submit.prevent="submitSearch" class="mb-4 flex items-center gap-2 overflow-auto">
                  <input
                    v-model="search"
                    type="text"
                    placeholder="Cari monitor (min 3 karakter)..."
                    class="border border-gray-300 dark:border-gray-700 min-w-52 rounded px-3 py-2 w-full max-w-xs focus:outline-none focus:ring focus:border-blue-400 dark:bg-gray-900 dark:text-gray-100"
                  />
                  <select v-model="statusFilter" @change="onStatusFilterChange" class="border border-gray-300 dark:border-gray-700 rounded px-2 py-2 focus:outline-none focus:ring focus:border-blue-400 dark:bg-gray-900 dark:text-gray-100">
                    <option value="all">Semua Status</option>
                    <option value="up">Up</option>
                    <option value="down">Down</option>
                  </select>
                  <select v-model="visibilityFilter" @change="onVisibilityFilterChange" class="border border-gray-300 dark:border-gray-700 rounded px-2 py-2 focus:outline-none focus:ring focus:border-blue-400 dark:bg-gray-900 dark:text-gray-100">
                    <option value="all">Semua Visibilitas</option>
                    <option value="public">Publik</option>
                    <option value="private">Privat</option>
                  </select>
                    <select v-model.number="perPage" @change="onPerPageChange" class="border border-gray-300 dark:border-gray-700 rounded px-2 py-2 focus:outline-none focus:ring focus:border-blue-400 dark:bg-gray-900 dark:text-gray-100">
                        <option :value="5">5 / halaman</option>
                        <option :value="10">10 / halaman</option>
                        <option :value="15">15 / halaman</option>
                        <option :value="20">20 / halaman</option>
                        <option :value="50">50 / halaman</option>
                        <option :value="100">100 / halaman</option>
                    </select>
                  <button
                    v-if="search"
                    type="button"
                    @click="clearSearch"
                    class="ml-1 px-2 py-1 text-xs bg-gray-200 dark:bg-gray-700 rounded hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200"
                  >Bersihkan</button>
                  <button type="submit" class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded">Cari</button>
                </form>

                <div v-if="props.monitors.data.length === 0" class="text-gray-600 dark:text-gray-400"> Belum ada monitor yang terdaftar.
                </div>

                <div v-else class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>URL</TableHead>
                                <TableHead>Status Uptime</TableHead>
                                <TableHead>Terakhir Dicek</TableHead>
                                <TableHead>Today's Uptime</TableHead>
                                <TableHead>Sertifikat</TableHead>
                                <TableHead>Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="monitor in props.monitors.data" :key="monitor.id">
                                <TableCell>
                                    <a :href="monitor.url" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">{{ monitor.url }}</a>
                                </TableCell>
                                <TableCell>
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
                                </TableCell>
                                <TableCell class="text-gray-500 dark:text-gray-400">
                                    {{ monitor.last_check_date ? new Date(monitor.last_check_date).toLocaleString() : '-' }}
                                </TableCell>
                                <TableCell class="text-gray-500 dark:text-gray-400">
                                    {{ monitor.today_uptime_percentage ? monitor.today_uptime_percentage + '%' : '-' }}
                                </TableCell>
                                <TableCell class="text-gray-500 dark:text-gray-400">
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
                                </TableCell>
                                <TableCell class="text-right">
                                    <Link :href="route('monitor.edit', monitor.id)" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3">Edit</Link>
                                    <button @click="openDeleteModal(monitor)" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 cursor-pointer">Hapus</button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination Links -->
                <div class="mt-6">
                    <Pagination :data="props.monitors" :on-link-click="onPaginationLinkClick" />
                </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Dialog v-model:open="isDeleteModalOpen">
          <DialogContent class="sm:max-w-md">
            <DialogHeader>
              <DialogTitle>Hapus Monitor?</DialogTitle>
              <DialogDescription>
                Apakah Anda yakin ingin menghapus monitor ini? Tindakan ini tidak dapat dibatalkan.<br>
                <span v-if="monitorToDelete" class="block mt-2 text-sm text-gray-700 dark:text-gray-300">
                  <Icon name="alert-triangle" class="inline mr-1 text-red-500" />
                  <b>{{ monitorToDelete.url }}</b>
                </span>
              </DialogDescription>
            </DialogHeader>
            <DialogFooter>
              <Button variant="outline" @click="closeDeleteModal">Batal</Button>
              <Button variant="destructive" @click="confirmDeleteMonitor">Hapus</Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
    </AppLayout>
</template>
