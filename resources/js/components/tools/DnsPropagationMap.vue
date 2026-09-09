<script setup lang="ts">
import { computed } from 'vue';
import { WORLD_MAP_PATH } from './worldMapData';

export interface ResolverResult {
    id: string;
    name: string;
    ip: string;
    location: string;
    country: string;
    country_code: string;
    flag: string;
    region: string;
    lat: number;
    lng: number;
    ok: boolean;
    elapsed_ms: number;
    answers: string[];
    error?: string;
}

interface Props {
    resolvers: ResolverResult[];
    consensusAnswer?: string;
}

const props = defineProps<Props>();

// Equirectangular projection coordinates for 800x400 SVG canvas
function project(lat: number, lng: number): { x: number; y: number } {
    // longitude: -180..180 -> 0..800
    const x = ((lng + 180) / 360) * 800;
    // latitude: 90..-90 -> 0..400
    const y = ((90 - lat) / 180) * 400;
    return {
        x: Math.round(x * 10) / 10,
        y: Math.round(y * 10) / 10,
    };
}

const projectedResolvers = computed(() => {
    return props.resolvers.map((r) => {
        const coords = project(r.lat, r.lng);
        const matchesConsensus =
            r.ok &&
            Boolean(props.consensusAnswer) &&
            r.answers.length > 0 &&
            [...r.answers].sort().join(', ') === props.consensusAnswer;

        return {
            ...r,
            ...coords,
            matchesConsensus,
        };
    });
});
</script>

<template>
    <div class="relative overflow-hidden rounded-2xl border border-gray-200/80 bg-slate-950 p-4 text-white shadow-inner sm:p-6 dark:border-gray-800">
        <!-- Map Header & Legend -->
        <div class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h3 class="text-xs font-black tracking-wider text-gray-300 uppercase">Global Propagation Map</h3>
                <p class="text-[11px] text-gray-400">Live resolver telemetry across 6 continents</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-[11px]">
                <div class="flex items-center gap-1.5">
                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-emerald-400/30"></span>
                    <span class="text-gray-300">Resolved / Match</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-amber-400 ring-2 ring-amber-400/30"></span>
                    <span class="text-gray-300">Variant Answer</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-rose-500 ring-2 ring-rose-500/30"></span>
                    <span class="text-gray-300">Timeout / Failed</span>
                </div>
            </div>
        </div>

        <!-- World Map SVG Canvas -->
        <div class="relative aspect-[2/1] w-full">
            <svg
                viewBox="0 0 800 400"
                class="h-full w-full select-none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <defs>
                    <!-- Subtle grid pattern for high-tech aesthetic -->
                    <pattern id="dns-map-grid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255, 255, 255, 0.04)" stroke-width="0.75" />
                    </pattern>
                </defs>

                <!-- Grid Background -->
                <rect width="800" height="400" fill="url(#dns-map-grid)" />

                <!-- Accurate World Landmass (Equirectangular Projection) -->
                <path
                    :d="WORLD_MAP_PATH"
                    class="fill-slate-800/90 stroke-slate-700/60"
                    stroke-width="0.75"
                />

                <!-- Coordinate Equator & Prime Meridian dashed lines -->
                <line x1="0" y1="200" x2="800" y2="200" stroke="rgba(255, 255, 255, 0.08)" stroke-dasharray="4 4" stroke-width="1" />
                <line x1="400" y1="0" x2="400" y2="400" stroke="rgba(255, 255, 255, 0.08)" stroke-dasharray="4 4" stroke-width="1" />

                <!-- Resolver Pins -->
                <g v-for="node in projectedResolvers" :key="node.id" class="group cursor-pointer">
                    <!-- Subtle pulsing ring -->
                    <circle
                        :cx="node.x"
                        :cy="node.y"
                        r="9"
                        class="animate-ping opacity-35"
                        :class="{
                            'fill-emerald-400': node.ok && (node.matchesConsensus || !consensusAnswer),
                            'fill-amber-400': node.ok && !node.matchesConsensus && consensusAnswer,
                            'fill-rose-500': !node.ok,
                        }"
                    />
                    <!-- Status Ring -->
                    <circle
                        :cx="node.x"
                        :cy="node.y"
                        r="4.5"
                        :class="{
                            'fill-emerald-500 stroke-emerald-200/80': node.ok && (node.matchesConsensus || !consensusAnswer),
                            'fill-amber-500 stroke-amber-200/80': node.ok && !node.matchesConsensus && consensusAnswer,
                            'fill-rose-600 stroke-rose-300/80': !node.ok,
                        }"
                        stroke-width="1.5"
                    />
                    <!-- Center Dot -->
                    <circle
                        :cx="node.x"
                        :cy="node.y"
                        r="1.8"
                        fill="#ffffff"
                    />

                    <!-- Tooltip Card on Hover -->
                    <foreignObject
                        :x="node.x > 580 ? node.x - 170 : node.x + 10"
                        :y="node.y > 300 ? node.y - 70 : node.y - 10"
                        width="180"
                        height="85"
                        class="pointer-events-none opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                    >
                        <div class="rounded-xl border border-gray-700 bg-gray-900/95 p-2 shadow-xl backdrop-blur-sm">
                            <div class="flex items-center gap-1 text-[11px] font-bold text-white">
                                <span>{{ node.flag }}</span>
                                <span class="truncate">{{ node.location }}</span>
                            </div>
                            <div class="text-[10px] text-gray-400">{{ node.name }} ({{ node.ip }})</div>
                            <div class="mt-1 flex items-center justify-between text-[10px]">
                                <span
                                    class="font-bold"
                                    :class="node.ok ? 'text-emerald-400' : 'text-rose-400'"
                                >
                                    {{ node.ok ? (node.matchesConsensus ? '✓ Matching' : 'Variant Answer') : 'Failed' }}
                                </span>
                                <span class="font-mono text-gray-400">{{ node.elapsed_ms }}ms</span>
                            </div>
                        </div>
                    </foreignObject>
                </g>
            </svg>
        </div>
    </div>
</template>
