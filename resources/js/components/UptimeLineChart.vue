<script setup lang="ts">
import { computed, ref } from 'vue';

interface UptimeDaily {
    date: string;
    uptime_percentage: number;
}

const props = defineProps<{
    uptimesDaily: UptimeDaily[];
}>();

// SVG dimensions
const width = 800;
const height = 140;
const padding = { top: 10, right: 10, bottom: 25, left: 45 };

// Tooltip state
const tooltipData = ref<{
    visible: boolean;
    x: number;
    y: number;
    date: string;
    uptime: number | null;
}>({
    visible: false,
    x: 0,
    y: 0,
    date: '',
    uptime: null,
});

// Generate last 90 days
const last90Days = computed(() => {
    const dates = [];
    const today = new Date();
    for (let i = 89; i >= 0; i--) {
        const d = new Date(today);
        d.setDate(today.getDate() - i);
        dates.push(d.toISOString().slice(0, 10));
    }
    return dates;
});

// Create a map of uptimes by date
const uptimeMap = computed(() => {
    const map: Record<string, number> = {};
    props.uptimesDaily.forEach((u) => {
        map[u.date] = u.uptime_percentage;
    });
    return map;
});

// Get data points
const dataPoints = computed(() => {
    return last90Days.value.map((date, index) => ({
        index,
        date,
        uptime: uptimeMap.value[date] ?? null,
    }));
});

// Scale functions (Y axis from 90% to 100% for better visibility)
const minY = 90;
const maxY = 100;

const xScale = (index: number) => {
    const chartWidth = width - padding.left - padding.right;
    return padding.left + (index / (last90Days.value.length - 1)) * chartWidth;
};

const yScale = (value: number) => {
    const chartHeight = height - padding.top - padding.bottom;
    const clampedValue = Math.max(minY, Math.min(maxY, value));
    return padding.top + chartHeight - ((clampedValue - minY) / (maxY - minY)) * chartHeight;
};

// Generate SVG path for line
const linePath = computed(() => {
    const points = dataPoints.value.filter((d) => d.uptime !== null);
    if (points.length < 2) return '';

    return points
        .map((point, i) => {
            const x = xScale(point.index);
            const y = yScale(point.uptime as number);
            return `${i === 0 ? 'M' : 'L'} ${x} ${y}`;
        })
        .join(' ');
});

// Generate area path (filled area under line)
const areaPath = computed(() => {
    const points = dataPoints.value.filter((d) => d.uptime !== null);
    if (points.length < 2) return '';

    const baseline = yScale(minY);

    let path = points
        .map((point, i) => {
            const x = xScale(point.index);
            const y = yScale(point.uptime as number);
            return `${i === 0 ? 'M' : 'L'} ${x} ${y}`;
        })
        .join(' ');

    // Close the path to create area
    const lastPoint = points[points.length - 1];
    const firstPoint = points[0];
    path += ` L ${xScale(lastPoint.index)} ${baseline}`;
    path += ` L ${xScale(firstPoint.index)} ${baseline}`;
    path += ' Z';

    return path;
});

// Y-axis ticks
const yTicks = [90, 92, 94, 96, 98, 100];

// X-axis ticks (show every 30 days)
const xTicks = computed(() => {
    return [0, 30, 60, 89].map((i) => ({
        index: i,
        label: last90Days.value[i],
    }));
});

// Handle mouse events for tooltip
const handleMouseMove = (event: MouseEvent) => {
    const svg = event.currentTarget as SVGSVGElement;
    const rect = svg.getBoundingClientRect();
    const mouseX = event.clientX - rect.left;

    // Find closest data point
    const chartWidth = width - padding.left - padding.right;
    const relativeX = mouseX - padding.left;
    const index = Math.round((relativeX / chartWidth) * (last90Days.value.length - 1));
    const clampedIndex = Math.max(0, Math.min(last90Days.value.length - 1, index));

    const point = dataPoints.value[clampedIndex];
    if (point) {
        tooltipData.value = {
            visible: true,
            x: event.clientX,
            y: event.clientY,
            date: point.date,
            uptime: point.uptime,
        };
    }
};

const handleMouseLeave = () => {
    tooltipData.value.visible = false;
};

// Get color based on uptime
const getUptimeColor = (uptime: number | null) => {
    if (uptime === null) return '#9CA3AF';
    if (uptime === 100) return '#22C55E';
    if (uptime >= 99.5) return '#86EFAC';
    if (uptime >= 95) return '#EAB308';
    return '#EF4444';
};

const lineColor = computed(() => {
    const validPoints = dataPoints.value.filter((d) => d.uptime !== null);
    if (validPoints.length === 0) return '#22C55E';
    const avgUptime = validPoints.reduce((sum, d) => sum + (d.uptime || 0), 0) / validPoints.length;
    return getUptimeColor(avgUptime);
});

// Format date for display
const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};
</script>

<template>
    <div class="relative w-full">
        <svg
            :viewBox="`0 0 ${width} ${height}`"
            class="h-auto w-full"
            preserveAspectRatio="xMidYMid meet"
            @mousemove="handleMouseMove"
            @mouseleave="handleMouseLeave"
        >
            <!-- Grid lines -->
            <g class="grid-lines">
                <line
                    v-for="tick in yTicks"
                    :key="tick"
                    :x1="padding.left"
                    :y1="yScale(tick)"
                    :x2="width - padding.right"
                    :y2="yScale(tick)"
                    class="stroke-gray-200 dark:stroke-gray-700"
                    stroke-width="1"
                    stroke-dasharray="4,4"
                />
            </g>

            <!-- Y-axis labels -->
            <g class="y-axis-labels">
                <text
                    v-for="tick in yTicks"
                    :key="tick"
                    :x="padding.left - 5"
                    :y="yScale(tick)"
                    text-anchor="end"
                    dominant-baseline="middle"
                    class="fill-gray-500 text-[10px] dark:fill-gray-400"
                >
                    {{ tick }}%
                </text>
            </g>

            <!-- X-axis labels -->
            <g class="x-axis-labels">
                <text
                    v-for="tick in xTicks"
                    :key="tick.index"
                    :x="xScale(tick.index)"
                    :y="height - 5"
                    text-anchor="middle"
                    class="fill-gray-500 text-[10px] dark:fill-gray-400"
                >
                    {{ formatDate(tick.label) }}
                </text>
            </g>

            <!-- 99.5% threshold line -->
            <line
                :x1="padding.left"
                :y1="yScale(99.5)"
                :x2="width - padding.right"
                :y2="yScale(99.5)"
                class="stroke-green-300 dark:stroke-green-700"
                stroke-width="1"
                stroke-dasharray="2,2"
            />

            <!-- Area fill -->
            <path v-if="areaPath" :d="areaPath" :fill="lineColor" fill-opacity="0.15" />

            <!-- Line -->
            <path v-if="linePath" :d="linePath" :stroke="lineColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" />

            <!-- Data points for low uptime days -->
            <g class="data-points">
                <circle
                    v-for="point in dataPoints.filter((d) => d.uptime !== null && d.uptime < 99.5)"
                    :key="point.index"
                    :cx="xScale(point.index)"
                    :cy="yScale(point.uptime as number)"
                    r="4"
                    :fill="getUptimeColor(point.uptime)"
                    class="stroke-white dark:stroke-gray-800"
                    stroke-width="2"
                />
            </g>
        </svg>

        <!-- Tooltip -->
        <Teleport to="body">
            <div
                v-if="tooltipData.visible"
                class="pointer-events-none fixed z-50 rounded-lg bg-white px-3 py-2 text-xs shadow-lg dark:bg-gray-800"
                :style="{
                    left: `${tooltipData.x + 10}px`,
                    top: `${tooltipData.y - 10}px`,
                    transform: 'translateY(-100%)',
                }"
            >
                <div class="text-gray-500 dark:text-gray-400">{{ tooltipData.date }}</div>
                <div
                    v-if="tooltipData.uptime !== null"
                    class="mt-1 font-medium"
                    :class="tooltipData.uptime === 100 ? 'text-green-600' : tooltipData.uptime >= 99.5 ? 'text-green-500' : tooltipData.uptime >= 95 ? 'text-yellow-600' : 'text-red-600'"
                >
                    {{ tooltipData.uptime.toFixed(2) }}% uptime
                </div>
                <div v-else class="mt-1 text-gray-500">No data</div>
            </div>
        </Teleport>
    </div>
</template>
