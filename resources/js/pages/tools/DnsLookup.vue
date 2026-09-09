<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import PublicLayout from '@/components/PublicLayout.vue';
import DnsPropagationMap from '@/components/tools/DnsPropagationMap.vue';
import RelatedTools from '@/components/tools/RelatedTools.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface DnsRecord {
    host: string;
    type: string;
    ttl: number;
    target: string;
    pri?: number;
}

interface GlobalResolverItem {
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
    records?: DnsRecord[];
    error?: string;
}

interface GlobalPropagationData {
    total: number;
    responding: number;
    consensus_answer?: string;
    propagation_percent: number;
    items: GlobalResolverItem[];
}

interface DnsResult {
    ok: boolean;
    domain: string;
    type: string;
    count?: number;
    records?: DnsRecord[];
    server?: string;
    elapsed_ms?: number;
    error?: string;
    global?: GlobalPropagationData;
}

interface Props {
    initialDomain?: string;
    initialType?: string;
    initialServer?: string;
    initialResult?: DnsResult | null;
    globalResolvers?: any[];
    appUrl: string;
}

const props = defineProps<Props>();

const domainInput = ref(props.initialDomain || '');
const typeFilter = ref(props.initialType || 'ALL');
const customServerInput = ref(props.initialServer || '');
const showAdvanced = ref(Boolean(props.initialServer));
const checkGlobalPropagation = ref(true);

const result = ref<DnsResult | null>(props.initialResult || null);
const loading = ref(false);
const errorMessage = ref('');
const copiedIndex = ref<number | null>(null);
const copiedGlobalIndex = ref<number | null>(null);

// Filter by region for propagation view
const selectedRegion = ref<string>('ALL');

const pageTitle = 'Free DNS Record & Global Propagation Checker - Uptime Kita';
const pageDescription =
    'Lookup DNS records across global regions (US, Europe, Asia, Australia, South America) or query custom nameservers with live propagation status.';
const shareUrl = `${props.appUrl}/tools/dns-lookup`;
const shareText = 'Check DNS propagation across global resolvers and query custom nameservers with Uptime Kita.';

const dnsTypes = ['ALL', 'A', 'AAAA', 'MX', 'TXT', 'CNAME', 'NS', 'SOA'];

const regions = computed(() => {
    if (!result.value?.global?.items) return [];
    const set = new Set<string>();
    result.value.global.items.forEach((item) => {
        if (item.region) set.add(item.region);
    });
    return Array.from(set);
});

const filteredGlobalResolvers = computed(() => {
    if (!result.value?.global?.items) return [];
    if (selectedRegion.value === 'ALL') return result.value.global.items;
    return result.value.global.items.filter((item) => item.region === selectedRegion.value);
});

async function lookupDns() {
    const d = domainInput.value.trim();
    if (!d) return;

    loading.value = true;
    errorMessage.value = '';
    result.value = null;

    const payload: {
        domain: string;
        type: string;
        server?: string;
        check_global?: boolean;
    } = {
        domain: d,
        type: typeFilter.value,
    };

    const s = customServerInput.value.trim();
    if (s) {
        payload.server = s;
    } else if (checkGlobalPropagation.value) {
        payload.check_global = true;
    }

    try {
        const res = await fetch('/api/tools/dns-lookup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });

        const data = await res.json();
        if (!res.ok) {
            errorMessage.value = data.message || 'DNS lookup failed.';
        } else {
            result.value = data;
            if (!data.ok) {
                errorMessage.value = data.error || 'No DNS records found.';
            }
        }
    } catch {
        errorMessage.value = 'Network error during DNS lookup.';
    } finally {
        loading.value = false;
    }
}

function copyRecord(target: string, index: number) {
    navigator.clipboard.writeText(target);
    copiedIndex.value = index;
    setTimeout(() => {
        copiedIndex.value = null;
    }, 2000);
}

function copyGlobalAnswer(target: string, index: number) {
    navigator.clipboard.writeText(target);
    copiedGlobalIndex.value = index;
    setTimeout(() => {
        copiedGlobalIndex.value = null;
    }, 2000);
}
</script>

<template>
    <PublicLayout :title="pageTitle" :description="pageDescription" :share-url="shareUrl" :share-text="shareText" container-class="max-w-5xl">
        <!-- Breadcrumb -->
        <div class="mb-4 flex items-center gap-2 text-xs font-semibold text-gray-500 dark:text-gray-400">
            <Link href="/tools" class="hover:text-blue-600 dark:hover:text-blue-400">Free Tools</Link>
            <span>/</span>
            <span class="text-gray-900 dark:text-white">Global DNS Propagation & Lookup</span>
        </div>

        <!-- Header -->
        <div class="mb-6 text-center sm:mb-8">
            <div
                class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-md"
            >
                <Icon name="globe" class="h-6 w-6" />
            </div>
            <h1 class="mt-3 text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">DNS Lookup & Global Propagation</h1>
            <p class="mx-auto mt-1.5 max-w-2xl text-xs text-gray-500 sm:text-sm dark:text-gray-400">
                Check record propagation across 12 worldwide resolvers (US, Europe, Asia, Australia, South America) or query custom nameservers directly.
            </p>
        </div>

        <!-- Search Input Box -->
        <Card class="mb-6 rounded-3xl border border-gray-200/80 bg-white/80 shadow-sm backdrop-blur-sm dark:border-gray-800/80 dark:bg-gray-900/80">
            <CardContent class="p-4 sm:p-5">
                <form @submit.prevent="lookupDns" class="space-y-3">
                    <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
                        <div class="relative flex-1">
                            <Icon name="globe" class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <input
                                v-model="domainInput"
                                type="text"
                                placeholder="Enter domain (e.g. google.com, vercel.com)..."
                                class="w-full rounded-xl border border-gray-200/80 bg-gray-50/50 py-2.5 pr-4 pl-10 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-gray-700/80 dark:bg-gray-800/50 dark:text-white dark:focus:bg-gray-800"
                            />
                        </div>

                        <select
                            v-model="typeFilter"
                            class="rounded-xl border border-gray-200/80 bg-gray-50/50 px-3.5 py-2.5 text-xs font-bold text-gray-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700/80 dark:bg-gray-800/50 dark:text-gray-200 dark:focus:bg-gray-800"
                        >
                            <option v-for="t in dnsTypes" :key="t" :value="t">{{ t }} Records</option>
                        </select>

                        <button
                            type="submit"
                            :disabled="loading || !domainInput.trim()"
                            class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-6 py-2.5 text-xs font-bold text-white shadow-sm transition-all hover:bg-blue-700 active:scale-95 disabled:opacity-60"
                        >
                            <Icon v-if="loading" name="loader" class="h-3.5 w-3.5 animate-spin" />
                            <Icon v-else name="search" class="h-3.5 w-3.5" />
                            <span>{{ loading ? 'Checking…' : 'Check DNS' }}</span>
                        </button>
                    </div>

                    <!-- Toggles & Options Row -->
                    <div class="flex flex-wrap items-center justify-between gap-2 pt-1 text-xs">
                        <div class="flex items-center gap-4">
                            <label class="flex cursor-pointer items-center gap-2 select-none text-gray-600 dark:text-gray-300">
                                <input
                                    v-model="checkGlobalPropagation"
                                    type="checkbox"
                                    :disabled="Boolean(customServerInput.trim())"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
                                />
                                <span class="font-medium">Check global propagation (12 locations)</span>
                            </label>

                            <button
                                type="button"
                                @click="showAdvanced = !showAdvanced"
                                class="inline-flex items-center gap-1 font-semibold text-blue-600 hover:underline dark:text-blue-400"
                            >
                                <Icon name="settings" class="h-3.5 w-3.5" />
                                <span>{{ showAdvanced ? 'Hide Custom Server' : 'Custom DNS Server' }}</span>
                            </button>
                        </div>

                        <!-- Sample Domains -->
                        <div class="flex flex-wrap items-center gap-1.5 text-gray-500 dark:text-gray-400">
                            <span class="text-[11px] font-medium">Quick:</span>
                            <button
                                v-for="d in ['google.com', 'cloudflare.com', 'github.com', 'laravel.com']"
                                :key="d"
                                type="button"
                                @click="
                                    domainInput = d;
                                    lookupDns();
                                "
                                class="rounded-lg border border-gray-200 bg-gray-50 px-2 py-0.5 text-[11px] font-medium text-gray-700 transition-colors hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                {{ d }}
                            </button>
                        </div>
                    </div>

                    <!-- Collapsible Custom Nameserver Input -->
                    <div
                        v-if="showAdvanced"
                        class="mt-2 rounded-2xl border border-blue-100 bg-blue-50/50 p-3.5 sm:p-4 dark:border-blue-900/30 dark:bg-blue-950/20"
                    >
                        <div class="mb-2 flex items-center justify-between">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-blue-950 dark:text-blue-200">
                                <Icon name="server" class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" />
                                <span>Query Specific Nameserver / Resolver IP</span>
                            </div>
                            <span class="text-[11px] text-blue-700 dark:text-blue-300">UDP Port 53</span>
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <input
                                v-model="customServerInput"
                                type="text"
                                placeholder="e.g. 1.1.1.1, 8.8.8.8, 9.9.9.9, or your authoritative NS IP"
                                class="w-full rounded-xl border border-blue-200 bg-white px-3.5 py-2 text-xs font-mono text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-blue-900/60 dark:bg-gray-900 dark:text-white"
                            />
                            <div class="flex shrink-0 items-center gap-1.5">
                                <button
                                    v-for="srv in [
                                        { label: 'Cloudflare', ip: '1.1.1.1' },
                                        { label: 'Google', ip: '8.8.8.8' },
                                        { label: 'Quad9', ip: '9.9.9.9' },
                                    ]"
                                    :key="srv.ip"
                                    type="button"
                                    @click="customServerInput = srv.ip"
                                    class="rounded-lg border border-blue-200 bg-white px-2 py-1 text-[10px] font-bold text-blue-700 hover:bg-blue-100/50 dark:border-blue-800 dark:bg-gray-900 dark:text-blue-300"
                                >
                                    {{ srv.label }}
                                </button>
                                <button
                                    v-if="customServerInput"
                                    type="button"
                                    @click="customServerInput = ''"
                                    class="rounded-lg px-2 py-1 text-[10px] font-bold text-gray-500 hover:text-rose-600"
                                >
                                    Clear
                                </button>
                            </div>
                        </div>
                        <p class="mt-2 text-[11px] text-blue-600/80 dark:text-blue-400/80">
                            Directly probes the target nameserver via raw UDP socket. Private RFC1918 IPs (192.168.x, 10.x, 127.x) are blocked for security.
                        </p>
                    </div>
                </form>
            </CardContent>
        </Card>

        <!-- Error Message -->
        <div
            v-if="errorMessage"
            class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300"
        >
            <div class="flex items-center gap-2">
                <Icon name="alertTriangle" class="h-4 w-4 shrink-0" />
                <span>{{ errorMessage }}</span>
            </div>
        </div>

        <!-- Result Section -->
        <div v-if="result && result.ok" class="space-y-6">
            <!-- Top Summary Strip -->
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-black text-gray-900 dark:text-white">{{ result.domain }}</h2>
                        <span class="rounded-md bg-blue-100 px-2 py-0.5 font-mono text-xs font-extrabold text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                            {{ result.type }}
                        </span>
                        <span v-if="result.server" class="rounded-md bg-purple-100 px-2 py-0.5 text-xs font-bold text-purple-800 dark:bg-purple-950 dark:text-purple-300">
                            via {{ result.server }}
                        </span>
                    </div>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        {{ result.count ?? result.records?.length ?? 0 }} record(s) resolved in {{ result.elapsed_ms }}ms
                    </p>
                </div>
            </div>

            <!-- Global Propagation Panel -->
            <div v-if="result.global" class="space-y-4">
                <!-- Consensus & Progress Card -->
                <Card class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white/80 dark:border-gray-800/80 dark:bg-gray-900/80">
                    <CardContent class="p-4 sm:p-5">
                        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-black tracking-wider text-gray-400 uppercase">Global Propagation Status</span>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[10px] font-extrabold"
                                        :class="
                                            result.global.propagation_percent >= 90
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                                : result.global.propagation_percent >= 50
                                                  ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'
                                                  : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                                        "
                                    >
                                        {{ result.global.propagation_percent }}% Propagated
                                    </span>
                                </div>
                                <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                    Consensus Answer:
                                    <strong class="font-mono text-gray-900 dark:text-white">
                                        {{ result.global.consensus_answer || 'No consensus / varying results' }}
                                    </strong>
                                </div>
                            </div>

                            <div class="text-right sm:self-auto">
                                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">
                                    {{ result.global.responding }} / {{ result.global.total }} Resolvers Responded
                                </span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-3.5 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div
                                class="h-full transition-all duration-500"
                                :class="
                                    result.global.propagation_percent >= 90
                                        ? 'bg-emerald-500'
                                        : result.global.propagation_percent >= 50
                                          ? 'bg-amber-500'
                                          : 'bg-rose-500'
                                "
                                :style="{ width: `${result.global.propagation_percent}%` }"
                            ></div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Interactive SVG Map -->
                <DnsPropagationMap
                    :resolvers="result.global.items"
                    :consensus-answer="result.global.consensus_answer"
                />

                <!-- Region Filter Tabs -->
                <div class="flex flex-wrap items-center gap-1.5 pt-2">
                    <button
                        type="button"
                        @click="selectedRegion = 'ALL'"
                        class="rounded-xl px-3 py-1 text-xs font-bold transition-all"
                        :class="
                            selectedRegion === 'ALL'
                                ? 'bg-blue-600 text-white shadow-xs'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                        "
                    >
                        All Regions ({{ result.global.items.length }})
                    </button>
                    <button
                        v-for="region in regions"
                        :key="region"
                        type="button"
                        @click="selectedRegion = region"
                        class="rounded-xl px-3 py-1 text-xs font-bold transition-all"
                        :class="
                            selectedRegion === region
                                ? 'bg-blue-600 text-white shadow-xs'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                        "
                    >
                        {{ region }}
                    </button>
                </div>

                <!-- Global Resolvers Grid -->
                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="(resNode, idx) in filteredGlobalResolvers"
                        :key="resNode.id"
                        class="rounded-2xl border border-gray-200/80 bg-white/80 transition-all hover:border-gray-300 dark:border-gray-800/80 dark:bg-gray-900/80"
                    >
                        <CardContent class="p-3.5 sm:p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-base">{{ resNode.flag }}</span>
                                        <span class="truncate text-xs font-black text-gray-900 dark:text-white">
                                            {{ resNode.location }}
                                        </span>
                                    </div>
                                    <div class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">
                                        {{ resNode.name }} • <span class="font-mono text-gray-400">{{ resNode.ip }}</span>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-extrabold"
                                        :class="
                                            resNode.ok
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                                : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                                        "
                                    >
                                        {{ resNode.ok ? '✓ OK' : '✕ Fail' }}
                                    </span>
                                    <div class="mt-0.5 font-mono text-[10px] text-gray-400">{{ resNode.elapsed_ms }}ms</div>
                                </div>
                            </div>

                            <!-- Resolved Answers list -->
                            <div class="mt-3 border-t border-gray-100 pt-2 text-xs dark:border-gray-800">
                                <div v-if="resNode.ok && resNode.answers?.length" class="space-y-1">
                                    <div
                                        v-for="(ans, aIdx) in resNode.answers"
                                        :key="aIdx"
                                        class="flex items-center justify-between gap-1 rounded-lg bg-gray-50 px-2 py-1 font-mono text-[11px] break-all dark:bg-gray-800/60"
                                    >
                                        <span class="text-gray-800 dark:text-gray-200">{{ ans }}</span>
                                        <button
                                            type="button"
                                            @click="copyGlobalAnswer(ans, idx * 100 + aIdx)"
                                            class="shrink-0 text-[10px] text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
                                        >
                                            {{ copiedGlobalIndex === idx * 100 + aIdx ? '✓' : 'copy' }}
                                        </button>
                                    </div>
                                </div>
                                <div v-else-if="!resNode.ok" class="text-[11px] text-rose-500">
                                    {{ resNode.error || 'Timed out / connection failed' }}
                                </div>
                                <div v-else class="text-[11px] text-gray-400">No records returned</div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Standard DNS Records Detail List (if records present) -->
            <div v-if="result.records && result.records.length > 0" class="space-y-3 pt-2">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-black tracking-wider text-gray-400 uppercase">
                        Raw DNS Record Details ({{ result.records.length }})
                    </h3>
                </div>

                <div class="space-y-2">
                    <Card
                        v-for="(rec, idx) in result.records"
                        :key="idx"
                        class="rounded-2xl border border-gray-200/80 bg-white/80 transition-all hover:border-gray-300 dark:border-gray-800/80 dark:bg-gray-900/80"
                    >
                        <CardContent class="p-3.5 sm:p-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex min-w-0 items-start gap-3">
                                    <span
                                        class="inline-flex shrink-0 items-center justify-center rounded-lg px-2 py-1 font-mono text-[10px] font-extrabold uppercase"
                                        :class="{
                                            'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300':
                                                rec.type === 'A' || rec.type === 'AAAA',
                                            'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300': rec.type === 'MX',
                                            'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300': rec.type === 'TXT',
                                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300':
                                                rec.type === 'CNAME' || rec.type === 'NS',
                                            'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300': ![
                                                'A',
                                                'AAAA',
                                                'MX',
                                                'TXT',
                                                'CNAME',
                                                'NS',
                                            ].includes(rec.type),
                                        }"
                                    >
                                        {{ rec.type }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-mono text-xs font-bold break-all text-gray-900 dark:text-white">
                                            {{ rec.target }}
                                        </p>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                                            <span>
                                                Host: <strong class="text-gray-700 dark:text-gray-300">{{ rec.host }}</strong>
                                            </span>
                                            <span>•</span>
                                            <span>TTL: {{ rec.ttl }}s</span>
                                            <span v-if="rec.pri !== null && rec.pri !== undefined">• Priority: {{ rec.pri }}</span>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="copyRecord(rec.target, idx)"
                                    class="self-end rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-[11px] font-semibold text-gray-700 transition-colors hover:bg-gray-100 sm:self-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                >
                                    {{ copiedIndex === idx ? '✓ Copied' : 'Copy' }}
                                </button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Other Free Tools -->
        <RelatedTools current-slug="dns-lookup" :target-value="domainInput" />
    </PublicLayout>
</template>
