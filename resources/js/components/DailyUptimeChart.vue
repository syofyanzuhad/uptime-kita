<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'; // Import onUnmounted

// --- INTERFACES ---
interface Uptime {
    date: string;
    uptime_percentage: number;
}

interface TooltipData {
    date: string;
    uptime_percentage: number | null;
}

// --- PROPS ---
const props = defineProps<{
    monitorId: number;
    isAuthenticated: boolean;
    uptimesDaily?: Uptime[];
    isLoading: boolean;
    error?: string | null;
}>();

// --- REFS ---
const scrollContainer = ref<HTMLDivElement | null>(null);
const tooltipRef = ref<HTMLDivElement | null>(null); // Ref untuk elemen tooltip itu sendiri

// State untuk mengelola tooltip
const tooltip = ref({
    visible: false,
    content: '',
    date: '',
    top: 0,
    left: 0,
});

// --- WATCHERS & LIFECYCLE ---

// Auto-scroll to right
watch(
    () => [props.uptimesDaily, props.isLoading],
    () => {
        setTimeout(() => {
            if (scrollContainer.value) {
                scrollContainer.value.scrollLeft = scrollContainer.value.scrollWidth;
            }
        }, 0);
    },
    { immediate: true },
);

/**
 * Menangani klik di luar area chart dan tooltip untuk menutupnya.
 */
const handleClickOutside = (event: MouseEvent) => {
    if (
        tooltip.value.visible &&
        tooltipRef.value &&
        scrollContainer.value &&
        !tooltipRef.value.contains(event.target as Node) &&
        !scrollContainer.value.contains(event.target as Node)
    ) {
        hideTooltip();
    }
};

onMounted(() => {
    if (scrollContainer.value) {
        scrollContainer.value.scrollLeft = scrollContainer.value.scrollWidth;
    }
    // Tambahkan event listener saat komponen dimuat
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    // Hapus event listener untuk mencegah memory leak
    document.removeEventListener('click', handleClickOutside);
});

// --- HELPER FUNCTIONS ---
function getLatest100Days() {
    const dates = [];
    const today = new Date();
    for (let i = 99; i >= 0; i--) {
        const d = new Date(today);
        d.setDate(today.getDate() - i);
        dates.push(d.toISOString().slice(0, 10));
    }
    return dates;
}

const firstDay = computed(() => getLatest100Days()[0] || '');
const lastDay = computed(() => getLatest100Days()[getLatest100Days().length - 1] || '');

// --- TOOLTIP FUNCTIONS ---

const showTooltip = (event: MouseEvent, data: TooltipData) => {
    const targetEl = event.currentTarget as HTMLElement; // Gunakan currentTarget
    const rect = targetEl.getBoundingClientRect();

    tooltip.value = {
        visible: true,
        date: data.date,
        content: data.uptime_percentage !== null ? `${data.uptime_percentage.toFixed(2)}%` : 'No data',
        top: rect.top,
        left: rect.left + rect.width / 2,
    };
};

const hideTooltip = () => {
    tooltip.value.visible = false;
};

/**
 * Toggle tooltip untuk perangkat sentuh (HP)
 */
const toggleTooltip = (event: MouseEvent, data: TooltipData) => {
    // Jika tooltip sudah terlihat untuk bar yang sama, sembunyikan
    if (tooltip.value.visible && tooltip.value.date === data.date) {
        hideTooltip();
    } else {
        // Jika tidak, tampilkan
        showTooltip(event, data);
    }
};
</script>

<template>
    <div class="mt-2">
        <template v-if="isAuthenticated">
            <div v-if="isLoading" class="text-xs text-gray-400">Loading uptime history...</div>
            <div v-else-if="error" class="text-xs text-red-400">{{ error }}</div>
            <div v-else-if="uptimesDaily && uptimesDaily.length > 0">
                <div
                    ref="scrollContainer"
                    class="flex h-auto w-full min-w-[320px] items-end overflow-x-auto rounded border border-gray-200 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-900"
                >
                    <template v-for="date in getLatest100Days()" :key="date">
                        <template v-if="uptimesDaily.some((u) => u.date === date)">
                            <div
                                v-for="uptime in uptimesDaily.filter((u) => u.date === date)"
                                :key="uptime.date"
                                class="mx-px flex min-w-1.5 flex-1 cursor-pointer flex-col items-center"
                                @mouseover="showTooltip($event, uptime)"
                                @mouseleave="hideTooltip"
                                @click="toggleTooltip($event, uptime)"
                            >
                                <div
                                    :class="[
                                        'h-8 w-full min-w-1.5 rounded transition-all duration-200',
                                        'pointer-events-none',
                                        uptime.uptime_percentage == 100
                                            ? 'bg-green-500'
                                            : uptime.uptime_percentage >= 99
                                              ? 'bg-green-300'
                                              : uptime.uptime_percentage >= 90
                                                ? 'bg-yellow-400'
                                                : 'bg-red-500',
                                    ]"
                                ></div>
                            </div>
                        </template>
                        <template v-else>
                            <div
                                class="mx-px flex min-w-1.5 flex-1 cursor-pointer flex-col items-center"
                                @mouseover="showTooltip($event, { date, uptime_percentage: null })"
                                @mouseleave="hideTooltip"
                                @click="toggleTooltip($event, { date, uptime_percentage: null })"
                            >
                                <div class="pointer-events-none h-8 w-full min-w-1.5 rounded bg-gray-300 dark:bg-gray-700"></div>
                            </div>
                        </template>
                    </template>
                </div>
                <div class="mt-1 flex justify-between text-[10px] text-gray-400 dark:text-gray-500">
                    <span>{{ firstDay }}</span>
                    <span>{{ lastDay }}</span>
                </div>
            </div>
        </template>
        <template v-else> </template>

        <Teleport to="body">
            <div
                ref="tooltipRef"
                v-if="tooltip.visible"
                :style="{ top: `${tooltip.top}px`, left: `${tooltip.left}px` }"
                class="fixed z-50 -mt-1 -translate-x-1/2 -translate-y-full rounded bg-white px-2 py-1 text-xs whitespace-nowrap text-gray-700 shadow-lg transition-opacity duration-200 dark:bg-gray-800 dark:text-gray-200"
            >
                {{ tooltip.date }}<br />
                <span class="font-semibold">{{ tooltip.content }}</span>
            </div>
        </Teleport>
    </div>
</template>
```
