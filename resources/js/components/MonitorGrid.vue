<script setup lang="ts">
import { computed } from 'vue';
import MonitorCard from './MonitorCard.vue';
import type { Monitor } from '@/types/monitor';

interface Props {
    monitors: Monitor[];
    type: 'private' | 'public';
    pinnedMonitors?: Set<number>;
    onTogglePin?: (monitorId: number) => void;
    onToggleActive?: (monitorId: number) => void;
    onSubscribe?: (monitorId: number) => void;
    onUnsubscribe?: (monitorId: number) => void;
    togglingMonitors?: Set<number>;
    subscribingMonitors?: Set<number>;
    unsubscribingMonitors?: Set<number>;
    showSubscribeButton?: boolean;
    showToggleButton?: boolean;
    showPinButton?: boolean;
    showUptimePercentage?: boolean;
    showCertificateStatus?: boolean;
    showLastChecked?: boolean;
    gridCols?: string;
}

const props = withDefaults(defineProps<Props>(), {
    pinnedMonitors: () => new Set(),
    togglingMonitors: () => new Set(),
    subscribingMonitors: () => new Set(),
    unsubscribingMonitors: () => new Set(),
    showSubscribeButton: true,
    showToggleButton: true,
    showPinButton: true,
    showUptimePercentage: true,
    showCertificateStatus: true,
    showLastChecked: true,
    gridCols: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
});

const sortedMonitors = computed(() => {
    return [...props.monitors].sort((a, b) => {
        const aPinned = props.pinnedMonitors.has(a.id);
        const bPinned = props.pinnedMonitors.has(b.id);
        if (aPinned && !bPinned) return -1;
        if (!aPinned && bPinned) return 1;
        return 0;
    });
});
</script>

<template>
    <div :class="`grid ${gridCols} gap-4`">
        <MonitorCard
            v-for="monitor in sortedMonitors"
            :key="monitor.id"
            :monitor="monitor"
            :type="type"
            :is-pinned="pinnedMonitors.has(monitor.id)"
            :on-toggle-pin="onTogglePin"
            :on-toggle-active="onToggleActive"
            :on-subscribe="onSubscribe"
            :on-unsubscribe="onUnsubscribe"
            :toggling-monitors="togglingMonitors"
            :subscribing-monitors="subscribingMonitors"
            :unsubscribing-monitors="unsubscribingMonitors"
            :show-subscribe-button="showSubscribeButton"
            :show-toggle-button="showToggleButton"
            :show-pin-button="showPinButton"
            :show-uptime-percentage="showUptimePercentage"
            :show-certificate-status="showCertificateStatus"
            :show-last-checked="showLastChecked"
        />
    </div>
</template>
