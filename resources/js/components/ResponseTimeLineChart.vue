<script setup lang="ts">
import { computed, ref } from 'vue';

interface HistoryData {
    created_at: string;
    uptime_status: string;
    response_time?: number | null;
}

const props = defineProps<{
    histories: HistoryData[];
    last100Minutes: Date[];
}>();

// SVG dimensions
const width = 800;
const height = 120;
const padding = { top: 10, right: 10, bottom: 20, left: 40 };

// Tooltip state
const tooltipData = ref<{
    visible: boolean;
    x: number;
    y: number;
    date: string;
    responseTime: number | null;
    status: string;
}>({
    visible: false,
    x: 0,
    y: 0,
    date: '',
    responseTime: null,
    status: '',
});

// Create a map of histories by minute
const historyMinuteMap = computed(() => {
    const map: Record<string, HistoryData> = {};
    props.histories.forEach((h) => {
        const key = new Date(h.created_at).toISOString().slice(0, 16);
        map[key] = h;
    });
    return map;
});

// Get data points with response times
const dataPoints = computed(() => {
    return props.last100Minutes.map((date, index) => {
        const key = date.toISOString().slice(0, 16);
        const history = historyMinuteMap.value[key];
        return {
            index,
            date,
            responseTime: history?.response_time ?? null,
            status: history?.uptime_status ?? null,
        };
    });
});

// Calculate max response time for scaling
const maxResponseTime = computed(() => {
    const times = dataPoints.value.filter((d) => d.responseTime !== null).map((d) => d.responseTime as number);
    return times.length > 0 ? Math.max(...times) * 1.1 : 1000; // Add 10% padding
});

// Scale functions
const xScale = (index: number) => {
    const chartWidth = width - padding.left - padding.right;
    return padding.left + (index / (props.last100Minutes.length - 1)) * chartWidth;
};

const yScale = (value: number) => {
    const chartHeight = height - padding.top - padding.bottom;
    return padding.top + chartHeight - (value / maxResponseTime.value) * chartHeight;
};

// Generate SVG path for line
const linePath = computed(() => {
    const points = dataPoints.value.filter((d) => d.responseTime !== null);
    if (points.length < 2) return '';

    return points
        .map((point, i) => {
            const x = xScale(point.index);
            const y = yScale(point.responseTime as number);
            return `${i === 0 ? 'M' : 'L'} ${x} ${y}`;
        })
        .join(' ');
});

// Generate area path (filled area under line)
const areaPath = computed(() => {
    const points = dataPoints.value.filter((d) => d.responseTime !== null);
    if (points.length < 2) return '';

    const chartHeight = height - padding.top - padding.bottom;
    const baseline = padding.top + chartHeight;

    let path = points
        .map((point, i) => {
            const x = xScale(point.index);
            const y = yScale(point.responseTime as number);
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
const yTicks = computed(() => {
    const max = maxResponseTime.value;
    const tickCount = 4;
    return Array.from({ length: tickCount + 1 }, (_, i) => Math.round((max / tickCount) * i));
});

// Handle mouse events for tooltip
const handleMouseMove = (event: MouseEvent) => {
    const svg = event.currentTarget as SVGSVGElement;
    const rect = svg.getBoundingClientRect();
    const mouseX = event.clientX - rect.left;

    // Find closest data point
    const chartWidth = width - padding.left - padding.right;
    const relativeX = mouseX - padding.left;
    const index = Math.round((relativeX / chartWidth) * (props.last100Minutes.length - 1));
    const clampedIndex = Math.max(0, Math.min(props.last100Minutes.length - 1, index));

    const point = dataPoints.value[clampedIndex];
    if (point) {
        tooltipData.value = {
            visible: true,
            x: event.clientX,
            y: event.clientY,
            date: point.date.toLocaleString(),
            responseTime: point.responseTime,
            status: point.status || 'No data',
        };
    }
};

const handleMouseLeave = () => {
    tooltipData.value.visible = false;
};

// Get color based on response time
const getLineColor = (responseTime: number | null) => {
    if (responseTime === null) return '#9CA3AF';
    if (responseTime < 300) return '#22C55E'; // green
    if (responseTime < 1000) return '#EAB308'; // yellow
    return '#EF4444'; // red
};

const lineColor = computed(() => {
    const avgTime =
        dataPoints.value.filter((d) => d.responseTime !== null).reduce((sum, d) => sum + (d.responseTime || 0), 0) /
        dataPoints.value.filter((d) => d.responseTime !== null).length;
    return getLineColor(avgTime || null);
});
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
                    {{ tick }}ms
                </text>
            </g>

            <!-- Area fill -->
            <path v-if="areaPath" :d="areaPath" :fill="lineColor" fill-opacity="0.1" />

            <!-- Line -->
            <path v-if="linePath" :d="linePath" :stroke="lineColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" />

            <!-- Data points -->
            <g class="data-points">
                <circle
                    v-for="point in dataPoints.filter((d) => d.responseTime !== null)"
                    :key="point.index"
                    :cx="xScale(point.index)"
                    :cy="yScale(point.responseTime as number)"
                    r="3"
                    :fill="point.status === 'up' ? '#22C55E' : '#EF4444'"
                    class="opacity-0 transition-opacity hover:opacity-100"
                />
            </g>

            <!-- Down indicators -->
            <g class="down-indicators">
                <rect
                    v-for="point in dataPoints.filter((d) => d.status === 'down')"
                    :key="`down-${point.index}`"
                    :x="xScale(point.index) - 2"
                    :y="padding.top"
                    width="4"
                    :height="height - padding.top - padding.bottom"
                    fill="#EF4444"
                    fill-opacity="0.2"
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
                <div class="mt-1 font-medium" :class="tooltipData.status === 'up' ? 'text-green-600' : tooltipData.status === 'down' ? 'text-red-600' : 'text-gray-500'">
                    {{ tooltipData.status === 'up' ? 'Operational' : tooltipData.status === 'down' ? 'Down' : 'No data' }}
                </div>
                <div v-if="tooltipData.responseTime !== null" class="text-gray-700 dark:text-gray-300">{{ tooltipData.responseTime }}ms</div>
            </div>
        </Teleport>
    </div>
</template>
