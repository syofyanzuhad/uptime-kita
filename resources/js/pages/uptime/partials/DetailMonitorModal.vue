<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import Button from '@/components/ui/button/Button.vue';
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue';
import type { Monitor, MonitorHistory } from '@/types/monitor';
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps<{
    open: boolean;
    monitor: Monitor | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'edit', monitor: Monitor): void;
}>();

const page = usePage();
const isAuthenticated = computed(() => !!(page.props.auth as any)?.user);

const histories = ref<MonitorHistory[]>([]);
const loading = ref(false);

const fetchHistory = async () => {
    if (!props.monitor) return;
    loading.value = true;
    try {
        const response = await axios.get(route('monitor.history', { monitor: props.monitor.id }));
        histories.value = response.data.histories || [];
    } catch (error) {
        console.error('Failed to fetch history:', error);
    } finally {
        loading.value = false;
    }
};

watch(() => props.open, (isOpen) => {
    if (isOpen && props.monitor) {
        fetchHistory();
    } else {
        histories.value = [];
    }
});

const statusColor = computed(() => {
    if (!props.monitor) return '';
    switch (props.monitor.uptime_status) {
        case 'up': return 'text-green-600';
        case 'down': return 'text-red-600';
        default: return 'text-yellow-600';
    }
});

const close = () => {
    emit('update:open', false);
};

function formatDate(dateString: string | null) {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString();
}
</script>

<template>
    <Dialog :open="open" @update:open="close">
        <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <img v-if="monitor?.favicon" :src="monitor.favicon" class="h-5 w-5 rounded" />
                    Detail Monitor: {{ monitor?.url }}
                </DialogTitle>
            </DialogHeader>

            <div v-if="monitor" class="space-y-6 py-4">
                <!-- Grid Info -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <div class="space-y-1">
                        <span class="text-xs text-gray-500 uppercase font-semibold">Status</span>
                        <div class="flex items-center gap-1">
                            <Icon :name="monitor.uptime_status === 'up' ? 'checkCircle' : 'xCircle'" :class="statusColor" size="16" />
                            <span class="font-bold uppercase text-sm" :class="statusColor">{{ monitor.uptime_status }}</span>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <span class="text-xs text-gray-500 uppercase font-semibold">Uptime Hari Ini</span>
                        <div class="font-bold text-sm">{{ monitor.today_uptime_percentage }}%</div>
                    </div>
                    <div class="space-y-1">
                        <span class="text-xs text-gray-500 uppercase font-semibold">Interval</span>
                        <div class="font-bold text-sm">{{ monitor.uptime_check_interval }} min</div>
                    </div>
                    <div class="space-y-1">
                        <span class="text-xs text-gray-500 uppercase font-semibold">SSL</span>
                        <div class="text-sm font-bold" :class="monitor.certificate_status === 'valid' ? 'text-green-600' : 'text-red-600'">
                            {{ monitor.certificate_status }}
                        </div>
                    </div>
                    <div class="space-y-1 col-span-2">
                        <span class="text-xs text-gray-500 uppercase font-semibold">Terakhir Dicek</span>
                        <div class="text-sm font-medium">{{ formatDate(monitor.last_check_date) }}</div>
                    </div>
                </div>

                <!-- Recent History -->
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-widest text-gray-900 dark:text-gray-100 mb-2">Riwayat Terakhir</h3>
                    <div v-if="loading" class="flex justify-center py-8">
                        <Icon name="clock" class="h-6 w-6 animate-spin text-gray-400" />
                    </div>
                    <div v-else-if="histories.length === 0" class="text-center py-8 text-sm text-gray-500">
                        Tidak ada riwayat tersedia.
                    </div>
                    <div v-else class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-3 py-2 font-bold uppercase">Waktu</th>
                                    <th class="px-3 py-2 font-bold uppercase">Status</th>
                                    <th class="px-3 py-2 font-bold uppercase">Pesan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                <tr v-for="history in histories.slice(0, 10)" :key="history.id">
                                    <td class="px-3 py-2 whitespace-nowrap">{{ formatDate(history.created_at) }}</td>
                                    <td class="px-3 py-2">
                                        <span :class="history.uptime_status === 'up' ? 'text-green-600' : 'text-red-600'" class="font-bold uppercase">
                                            {{ history.uptime_status }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 truncate max-w-[200px]">{{ history.message || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <Button variant="outline" @click="close">Tutup</Button>
                <Button v-if="monitor && isAuthenticated" variant="secondary" @click="emit('edit', monitor); close()">Edit Monitor</Button>
                <Button v-if="monitor" @click="router.get(route('monitor.show', monitor.id))">Buka Halaman Detail</Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
