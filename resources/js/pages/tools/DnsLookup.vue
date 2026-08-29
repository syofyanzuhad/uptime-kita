<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import PublicLayout from '@/components/PublicLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

interface DnsRecord {
    host: string;
    type: string;
    ttl: number;
    target: string;
    pri?: number;
}

interface DnsResult {
    ok: boolean;
    domain: string;
    type: string;
    count: number;
    records: DnsRecord[];
    elapsed_ms: number;
    error?: string;
}

interface Props {
    initialDomain?: string;
    initialType?: string;
    initialResult?: DnsResult | null;
    appUrl: string;
}

const props = defineProps<Props>();

const domainInput = ref(props.initialDomain || '');
const typeFilter = ref(props.initialType || 'ALL');
const result = ref<DnsResult | null>(props.initialResult || null);
const loading = ref(false);
const errorMessage = ref('');
const copiedIndex = ref<number | null>(null);

const pageTitle = 'Free DNS Record Lookup Tool - Uptime Kita';
const pageDescription = 'Lookup DNS records (A, AAAA, MX, TXT, CNAME, NS, SOA) for any domain with live propagation and TTL values.';
const shareUrl = `${props.appUrl}/tools/dns-lookup`;
const shareText = 'Lookup DNS records instantly with Uptime Kita DNS Lookup tool.';

const dnsTypes = ['ALL', 'A', 'AAAA', 'MX', 'TXT', 'CNAME', 'NS', 'SOA'];

async function lookupDns() {
    const d = domainInput.value.trim();
    if (!d) return;

    loading.value = true;
    errorMessage.value = '';
    result.value = null;

    try {
        const res = await fetch('/api/tools/dns-lookup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ domain: d, type: typeFilter.value }),
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
</script>

<template>
    <PublicLayout :title="pageTitle" :description="pageDescription" :share-url="shareUrl" :share-text="shareText" container-class="max-w-4xl">
        <!-- Breadcrumb -->
        <div class="mb-4 flex items-center gap-2 text-xs font-semibold text-gray-500 dark:text-gray-400">
            <Link href="/tools" class="hover:text-blue-600 dark:hover:text-blue-400">Free Tools</Link>
            <span>/</span>
            <span class="text-gray-900 dark:text-white">DNS Record Lookup</span>
        </div>

        <!-- Header -->
        <div class="mb-6 text-center sm:mb-8">
            <div
                class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-md"
            >
                <Icon name="globe" class="h-6 w-6" />
            </div>
            <h1 class="mt-3 text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">DNS Record Lookup</h1>
            <p class="mx-auto mt-1.5 max-w-xl text-xs text-gray-500 sm:text-sm dark:text-gray-400">
                Inspect public DNS records (A, AAAA, MX, TXT, CNAME, NS, SOA) and verify nameservers.
            </p>
        </div>

        <!-- Search Input Box -->
        <Card class="mb-6 rounded-3xl border border-gray-200/80 bg-white/80 shadow-sm backdrop-blur-sm dark:border-gray-800/80 dark:bg-gray-900/80">
            <CardContent class="p-4 sm:p-5">
                <form @submit.prevent="lookupDns" class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
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
                        <span>{{ loading ? 'Looking up…' : 'Lookup DNS' }}</span>
                    </button>
                </form>

                <!-- Sample Domains -->
                <div class="mt-3 flex flex-wrap items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span class="text-[11px] font-medium">Popular:</span>
                    <button
                        v-for="d in ['google.com', 'microsoft.com', 'apple.com', 'laravel.com']"
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
        <div v-if="result && result.ok" class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-black text-gray-900 dark:text-white">{{ result.domain }} ({{ result.count }} records)</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Resolved in {{ result.elapsed_ms }}ms</p>
                </div>
            </div>

            <!-- DNS Records Table / List -->
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
                                        'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300': rec.type === 'A' || rec.type === 'AAAA',
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
                                        <span
                                            >Host: <strong class="text-gray-700 dark:text-gray-300">{{ rec.host }}</strong></span
                                        >
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
    </PublicLayout>
</template>
