<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import PublicLayout from '@/components/PublicLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

interface SecurityHeaderItem {
    label: string;
    present: boolean;
    value: string | null;
    recommendation: string;
}

interface HeadersResult {
    ok: boolean;
    url: string;
    status_code?: number;
    score?: string;
    security_headers?: Record<string, SecurityHeaderItem>;
    all_headers?: Record<string, string>;
    elapsed_ms?: number;
    error?: string;
}

interface Props {
    initialUrl?: string;
    initialResult?: HeadersResult | null;
    appUrl: string;
}

const props = defineProps<Props>();

const urlInput = ref(props.initialUrl || '');
const result = ref<HeadersResult | null>(props.initialResult || null);
const loading = ref(false);
const errorMessage = ref('');
const showRawHeaders = ref(false);

const pageTitle = 'Free HTTP Security Headers Analyzer - Uptime Kita';
const pageDescription = 'Analyze HTTP security headers (HSTS, CSP, X-Frame-Options, Permissions-Policy) and compute your website security grade.';
const shareUrl = `${props.appUrl}/tools/headers-checker`;
const shareText = 'Analyze your website HTTP security headers for free with Uptime Kita!';

async function checkHeaders() {
    const u = urlInput.value.trim();
    if (!u) return;

    loading.value = true;
    errorMessage.value = '';
    result.value = null;

    try {
        const res = await fetch('/api/tools/headers-check', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ url: u }),
        });

        const data = await res.json();
        if (!res.ok) {
            errorMessage.value = data.message || 'Headers check failed.';
        } else {
            result.value = data;
            if (!data.ok) {
                errorMessage.value = data.error || 'Unable to fetch HTTP headers.';
            }
        }
    } catch {
        errorMessage.value = 'Network error while checking headers.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <PublicLayout :title="pageTitle" :description="pageDescription" :share-url="shareUrl" :share-text="shareText" container-class="max-w-4xl">
        <!-- Breadcrumb -->
        <div class="mb-4 flex items-center gap-2 text-xs font-semibold text-gray-500 dark:text-gray-400">
            <Link href="/tools" class="hover:text-blue-600 dark:hover:text-blue-400">Free Tools</Link>
            <span>/</span>
            <span class="text-gray-900 dark:text-white">Security Headers Analyzer</span>
        </div>

        <!-- Header -->
        <div class="mb-6 text-center sm:mb-8">
            <div
                class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-purple-500 to-violet-600 text-white shadow-md"
            >
                <Icon name="shield" class="h-6 w-6" />
            </div>
            <h1 class="mt-3 text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">HTTP Security Headers Analyzer</h1>
            <p class="mx-auto mt-1.5 max-w-xl text-xs text-gray-500 sm:text-sm dark:text-gray-400">
                Audit crucial security headers (HSTS, CSP, X-Frame-Options, nosniff) to protect users from XSS and clickjacking.
            </p>
        </div>

        <!-- Search Input Box -->
        <Card class="mb-6 rounded-3xl border border-gray-200/80 bg-white/80 shadow-sm backdrop-blur-sm dark:border-gray-800/80 dark:bg-gray-900/80">
            <CardContent class="p-4 sm:p-5">
                <form @submit.prevent="checkHeaders" class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <Icon name="globe" class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input
                            v-model="urlInput"
                            type="text"
                            placeholder="Enter website URL (e.g. https://google.com, github.com)..."
                            class="w-full rounded-xl border border-gray-200/80 bg-gray-50/50 py-2.5 pr-4 pl-10 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-gray-700/80 dark:bg-gray-800/50 dark:text-white dark:focus:bg-gray-800"
                        />
                    </div>
                    <button
                        type="submit"
                        :disabled="loading || !urlInput.trim()"
                        class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-6 py-2.5 text-xs font-bold text-white shadow-sm transition-all hover:bg-blue-700 active:scale-95 disabled:opacity-60"
                    >
                        <Icon v-if="loading" name="loader" class="h-3.5 w-3.5 animate-spin" />
                        <Icon v-else name="search" class="h-3.5 w-3.5" />
                        <span>{{ loading ? 'Auditing…' : 'Analyze Headers' }}</span>
                    </button>
                </form>

                <!-- Sample URLs -->
                <div class="mt-3 flex flex-wrap items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span class="text-[11px] font-medium">Popular:</span>
                    <button
                        v-for="d in ['github.com', 'stripe.com', 'cloudflare.com', 'mozilla.org']"
                        :key="d"
                        type="button"
                        @click="
                            urlInput = d;
                            checkHeaders();
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
            <!-- Score Banner -->
            <div
                class="flex flex-col gap-4 rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex items-center gap-3.5">
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-2xl font-black text-white shadow-sm"
                        :class="{
                            'bg-emerald-500': result.score === 'A+' || result.score === 'A',
                            'bg-blue-500': result.score === 'B',
                            'bg-amber-500': result.score === 'C',
                            'bg-rose-500': result.score === 'D' || result.score === 'F',
                        }"
                    >
                        {{ result.score }}
                    </div>
                    <div>
                        <h2 class="text-base font-black text-gray-900 sm:text-lg dark:text-white">{{ result.url }}</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">HTTP {{ result.status_code }} • Responded in {{ result.elapsed_ms }}ms</p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="showRawHeaders = !showRawHeaders"
                    class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    {{ showRawHeaders ? 'Hide Raw Headers' : 'View All Headers' }}
                </button>
            </div>

            <!-- Security Headers Breakdown -->
            <div class="space-y-2.5">
                <Card
                    v-for="(sh, headerName) in result.security_headers"
                    :key="headerName"
                    class="rounded-2xl border transition-all dark:bg-gray-900/80"
                    :class="
                        sh.present
                            ? 'border-emerald-200/80 bg-white/80 dark:border-emerald-900/30'
                            : 'border-gray-200/80 bg-white/80 dark:border-gray-800/80'
                    "
                >
                    <CardContent class="p-3.5 sm:p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-2.5">
                                <div
                                    class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-white"
                                    :class="sh.present ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-700'"
                                >
                                    <Icon :name="sh.present ? 'check' : 'x'" class="h-3 w-3" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-mono text-xs font-bold text-gray-900 dark:text-white">{{ headerName }}</h3>
                                        <span
                                            class="py-0.2 rounded-full px-2 text-[9px] font-extrabold uppercase"
                                            :class="
                                                sh.present
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                                    : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                                            "
                                        >
                                            {{ sh.present ? 'Enabled' : 'Missing' }}
                                        </span>
                                    </div>
                                    <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">{{ sh.recommendation }}</p>
                                    <p
                                        v-if="sh.value"
                                        class="mt-1.5 rounded-lg bg-gray-50 p-2 font-mono text-[11px] break-all text-gray-700 dark:bg-gray-800/70 dark:text-gray-300"
                                    >
                                        {{ sh.value }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Raw Headers Drawer -->
            <Card
                v-if="showRawHeaders && result.all_headers"
                class="rounded-3xl border border-gray-200/80 bg-white/80 dark:border-gray-800/80 dark:bg-gray-900/80"
            >
                <CardContent class="p-4 sm:p-5">
                    <h3 class="mb-3 text-xs font-bold tracking-wider text-gray-400 uppercase">All Response Headers</h3>
                    <div class="space-y-1.5 font-mono text-xs">
                        <div
                            v-for="(val, key) in result.all_headers"
                            :key="key"
                            class="flex flex-col gap-1 border-b border-gray-100 pb-1 sm:flex-row sm:items-baseline dark:border-gray-800"
                        >
                            <span class="font-bold text-gray-900 sm:w-1/3 dark:text-white">{{ key }}:</span>
                            <span class="break-all text-gray-600 sm:w-2/3 dark:text-gray-400">{{ val }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </PublicLayout>
</template>
