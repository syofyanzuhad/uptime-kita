<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import PublicLayout from '@/components/PublicLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

interface SslResult {
    ok: boolean;
    domain: string;
    is_valid?: boolean;
    days_remaining?: number;
    issuer?: string;
    subject?: string;
    valid_from?: string;
    valid_to?: string;
    sans?: string[];
    signature_type?: string;
    elapsed_ms?: number;
    error?: string;
}

interface Props {
    initialDomain?: string;
    initialResult?: SslResult | null;
    appUrl: string;
}

const props = defineProps<Props>();

const domainInput = ref(props.initialDomain || '');
const result = ref<SslResult | null>(props.initialResult || null);
const loading = ref(false);
const errorMessage = ref('');

const pageTitle = 'Free SSL Certificate & Expiry Checker - Uptime Kita';
const pageDescription = 'Verify SSL/TLS certificate validity, expiration dates, days remaining, certificate authority, and SANs instantly.';
const shareUrl = `${props.appUrl}/tools/ssl-checker`;
const shareText = 'Check any website SSL certificate and expiration date for free with Uptime Kita!';

async function checkSsl() {
    const d = domainInput.value.trim();
    if (!d) return;

    loading.value = true;
    errorMessage.value = '';
    result.value = null;

    try {
        const res = await fetch('/api/tools/ssl-check', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ domain: d }),
        });

        const data = await res.json();
        if (!res.ok) {
            errorMessage.value = data.message || 'SSL check failed.';
        } else {
            result.value = data;
            if (!data.ok) {
                errorMessage.value = data.error || 'Unable to inspect SSL certificate.';
            }
        }
    } catch {
        errorMessage.value = 'Network error while checking SSL.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <PublicLayout
        :title="pageTitle"
        :description="pageDescription"
        :share-url="shareUrl"
        :share-text="shareText"
        container-class="max-w-4xl"
    >
        <!-- Breadcrumb / Back Link -->
        <div class="mb-4 flex items-center gap-2 text-xs font-semibold text-gray-500 dark:text-gray-400">
            <Link href="/tools" class="hover:text-blue-600 dark:hover:text-blue-400">Free Tools</Link>
            <span>/</span>
            <span class="text-gray-900 dark:text-white">SSL Certificate Checker</span>
        </div>

        <!-- Header -->
        <div class="mb-6 text-center sm:mb-8">
            <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-md">
                <Icon name="lock" class="h-6 w-6" />
            </div>
            <h1 class="mt-3 text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                SSL Certificate Checker
            </h1>
            <p class="mx-auto mt-1.5 max-w-xl text-xs text-gray-500 sm:text-sm dark:text-gray-400">
                Inspect SSL/TLS validity, days to expiration, issuer authority, and subject alternative names.
            </p>
        </div>

        <!-- Search Input Box -->
        <Card class="mb-6 rounded-3xl border border-gray-200/80 bg-white/80 shadow-sm backdrop-blur-sm dark:border-gray-800/80 dark:bg-gray-900/80">
            <CardContent class="p-4 sm:p-5">
                <form @submit.prevent="checkSsl" class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <Icon name="globe" class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input
                            v-model="domainInput"
                            type="text"
                            placeholder="Enter domain (e.g. google.com, github.com)..."
                            class="w-full rounded-xl border border-gray-200/80 bg-gray-50/50 py-2.5 pr-4 pl-10 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-gray-700/80 dark:bg-gray-800/50 dark:text-white dark:focus:bg-gray-800"
                        />
                    </div>
                    <button
                        type="submit"
                        :disabled="loading || !domainInput.trim()"
                        class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-6 py-2.5 text-xs font-bold text-white shadow-sm transition-all hover:bg-blue-700 active:scale-95 disabled:opacity-60"
                    >
                        <Icon v-if="loading" name="loader" class="h-3.5 w-3.5 animate-spin" />
                        <Icon v-else name="search" class="h-3.5 w-3.5" />
                        <span>{{ loading ? 'Inspecting…' : 'Check SSL' }}</span>
                    </button>
                </form>

                <!-- Sample Domains -->
                <div class="mt-3 flex flex-wrap items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-medium text-[11px]">Popular:</span>
                    <button
                        v-for="d in ['google.com', 'github.com', 'cloudflare.com', 'openai.com']"
                        :key="d"
                        type="button"
                        @click="domainInput = d; checkSsl()"
                        class="rounded-lg border border-gray-200 bg-gray-50 px-2 py-0.5 text-[11px] font-medium text-gray-700 transition-colors hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        {{ d }}
                    </button>
                </div>
            </CardContent>
        </Card>

        <!-- Error Message -->
        <div v-if="errorMessage" class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
            <div class="flex items-center gap-2">
                <Icon name="alertTriangle" class="h-4 w-4 shrink-0" />
                <span>{{ errorMessage }}</span>
            </div>
        </div>

        <!-- Result Section -->
        <div v-if="result && result.ok" class="space-y-4">
            <!-- Validity Top Card -->
            <div
                class="flex flex-col gap-4 rounded-3xl border p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between"
                :class="result.is_valid ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/40 dark:bg-emerald-950/20' : 'border-rose-200 bg-rose-50/50 dark:border-rose-900/40 dark:bg-rose-950/20'"
            >
                <div class="flex items-center gap-3.5">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl font-bold text-white shadow-sm"
                        :class="result.is_valid ? 'bg-emerald-500' : 'bg-rose-500'"
                    >
                        <Icon :name="result.is_valid ? 'check' : 'x'" class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-black text-gray-900 sm:text-lg dark:text-white">{{ result.domain }}</h2>
                            <span
                                class="rounded-full px-2 py-0.5 text-[10px] font-extrabold uppercase"
                                :class="result.is_valid ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-200'"
                            >
                                {{ result.is_valid ? 'Valid SSL' : 'Invalid / Expired' }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            Issued by <strong class="text-gray-900 dark:text-white">{{ result.issuer }}</strong> • Checked in {{ result.elapsed_ms }}ms
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div class="rounded-2xl border border-gray-200 bg-white p-3 text-center shadow-xs dark:border-gray-800 dark:bg-gray-900">
                        <span class="block text-[10px] font-bold tracking-wider text-gray-400 uppercase">Expires In</span>
                        <span
                            class="text-xl font-black"
                            :class="result.days_remaining && result.days_remaining > 14 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                        >
                            {{ result.days_remaining }} days
                        </span>
                    </div>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Card class="rounded-3xl border border-gray-200/80 bg-white/80 dark:border-gray-800/80 dark:bg-gray-900/80">
                    <CardContent class="p-4 sm:p-5">
                        <h3 class="mb-3 text-xs font-bold tracking-wider text-gray-400 uppercase">Validity Dates</h3>
                        <dl class="space-y-2 text-xs">
                            <div class="flex justify-between border-b border-gray-100 pb-1.5 dark:border-gray-800">
                                <dt class="text-gray-500 dark:text-gray-400">Valid From:</dt>
                                <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ result.valid_from }}</dd>
                            </div>
                            <div class="flex justify-between border-b border-gray-100 pb-1.5 dark:border-gray-800">
                                <dt class="text-gray-500 dark:text-gray-400">Valid Until:</dt>
                                <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ result.valid_to }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Signature Type:</dt>
                                <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ result.signature_type || 'N/A' }}</dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

                <Card class="rounded-3xl border border-gray-200/80 bg-white/80 dark:border-gray-800/80 dark:bg-gray-900/80">
                    <CardContent class="p-4 sm:p-5">
                        <h3 class="mb-3 text-xs font-bold tracking-wider text-gray-400 uppercase">Certificate Authority</h3>
                        <dl class="space-y-2 text-xs">
                            <div class="flex justify-between border-b border-gray-100 pb-1.5 dark:border-gray-800">
                                <dt class="text-gray-500 dark:text-gray-400">Issuer:</dt>
                                <dd class="font-medium text-gray-900 dark:text-white truncate max-w-[200px]">{{ result.issuer }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Common Name (CN):</dt>
                                <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ result.subject }}</dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>
            </div>

            <!-- SANs -->
            <Card v-if="result.sans?.length" class="rounded-3xl border border-gray-200/80 bg-white/80 dark:border-gray-800/80 dark:bg-gray-900/80">
                <CardContent class="p-4 sm:p-5">
                    <h3 class="mb-2 text-xs font-bold tracking-wider text-gray-400 uppercase">Subject Alternative Names (SANs) - {{ result.sans.length }}</h3>
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="san in result.sans"
                            :key="san"
                            class="rounded-lg bg-gray-100 px-2 py-0.5 font-mono text-[11px] text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        >
                            {{ san }}
                        </span>
                    </div>
                </CardContent>
            </Card>

            <!-- Monitor CTA -->
            <div class="rounded-2xl border border-blue-200 bg-blue-50/70 p-4 text-center dark:border-blue-900/40 dark:bg-blue-950/30">
                <p class="text-xs font-bold text-blue-900 dark:text-blue-200">
                    Want automatic alerts before your SSL certificate expires?
                </p>
                <p class="mt-0.5 text-[11px] text-blue-700 dark:text-blue-300">
                    Uptime Kita monitors SSL expiration 24/7 and sends Telegram, Discord, and Email alerts.
                </p>
                <Link
                    :href="`/monitor/create?url=${encodeURIComponent('https://' + result.domain)}`"
                    class="mt-2.5 inline-flex items-center gap-1 rounded-xl bg-blue-600 px-4 py-1.5 text-xs font-bold text-white shadow-xs transition-all hover:bg-blue-700 active:scale-95"
                >
                    <span>Track {{ result.domain }} on Uptime Kita</span>
                    <Icon name="arrowRight" class="h-3 w-3" />
                </Link>
            </div>
        </div>
    </PublicLayout>
</template>
