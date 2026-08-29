<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import PublicLayout from '@/components/PublicLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface DomainCheckResult {
    url: string;
    host: string;
    status_code: number | null;
    ok: boolean;
    response_time_ms: number;
    error?: string;
}

interface Props {
    initialUrl?: string;
    appUrl: string;
}

const props = defineProps<Props>();

const urlInput = ref(props.initialUrl || '');
const result = ref<DomainCheckResult | null>(null);
const loading = ref(false);
const errorMessage = ref('');

const pageTitle = 'Free Website Uptime & Latency Checker - Uptime Kita';
const pageDescription = 'Check any website or API uptime, HTTP status code, and latency in real time with zero sign-up.';
const shareUrl = `${props.appUrl}/tools/website-checker`;
const shareText = 'Check website uptime and response time for free with Uptime Kita!';

async function checkWebsite() {
    const u = urlInput.value.trim();
    if (!u) return;

    loading.value = true;
    errorMessage.value = '';
    result.value = null;

    try {
        const res = await fetch(`/api/check-domain?url=${encodeURIComponent(u)}`, {
            headers: { Accept: 'application/json' },
        });

        const data = await res.json();
        if (!res.ok) {
            errorMessage.value = data.message || 'Check failed. Please verify the URL.';
        } else {
            result.value = data;
            if (!data.ok && data.error) {
                errorMessage.value = data.error;
            }
        }
    } catch {
        errorMessage.value = 'Network error while checking website.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    if (props.initialUrl) {
        checkWebsite();
    }
});
</script>

<template>
    <PublicLayout :title="pageTitle" :description="pageDescription" :share-url="shareUrl" :share-text="shareText" container-class="max-w-4xl">
        <!-- Breadcrumb -->
        <div class="mb-3 flex items-center gap-2 text-xs font-semibold text-gray-500 sm:mb-4 dark:text-gray-400">
            <Link href="/tools" class="hover:text-blue-600 dark:hover:text-blue-400">Free Tools</Link>
            <span>/</span>
            <span class="text-gray-900 dark:text-white">Website & Uptime Checker</span>
        </div>

        <!-- Header -->
        <div class="mb-5 text-center sm:mb-8">
            <div
                class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-md sm:h-12 sm:w-12"
            >
                <Icon name="zap" class="h-5 w-5 sm:h-6 sm:w-6" />
            </div>
            <h1 class="mt-2.5 text-xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">Website Uptime & Latency Checker</h1>
            <p class="mx-auto mt-1 max-w-xl text-xs text-gray-500 sm:text-sm dark:text-gray-400">
                Ping any website, web service, or REST API to inspect uptime status, HTTP response codes, and round-trip latency.
            </p>
        </div>

        <!-- Search Input Box -->
        <Card
            class="mb-5 rounded-2xl border border-gray-200/80 bg-white/80 shadow-xs backdrop-blur-sm sm:mb-6 sm:rounded-3xl dark:border-gray-800/80 dark:bg-gray-900/80"
        >
            <CardContent class="p-3.5 sm:p-5">
                <form @submit.prevent="checkWebsite" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <Icon name="globe" class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input
                            v-model="urlInput"
                            type="text"
                            placeholder="Enter domain or URL (e.g. google.com, https://api.github.com)..."
                            class="w-full rounded-xl border border-gray-200/80 bg-gray-50/50 py-2 pr-4 pl-10 text-xs text-gray-900 placeholder-gray-400 transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:outline-none sm:py-2.5 sm:text-sm dark:border-gray-700/80 dark:bg-gray-800/50 dark:text-white dark:focus:bg-gray-800"
                        />
                    </div>
                    <button
                        type="submit"
                        :disabled="loading || !urlInput.trim()"
                        class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-5 py-2 text-xs font-bold text-white shadow-xs transition-all hover:bg-blue-700 active:scale-95 disabled:opacity-60 sm:py-2.5"
                    >
                        <Icon v-if="loading" name="loader" class="h-3.5 w-3.5 animate-spin" />
                        <Icon v-else name="zap" class="h-3.5 w-3.5" />
                        <span>{{ loading ? 'Checking…' : 'Check Website' }}</span>
                    </button>
                </form>

                <!-- Sample Domains -->
                <div class="mt-2.5 flex flex-wrap items-center gap-1.5 text-xs text-gray-500 sm:mt-3 dark:text-gray-400">
                    <span class="text-[10px] font-medium sm:text-[11px]">Popular:</span>
                    <button
                        v-for="d in ['google.com', 'github.com', 'cloudflare.com', 'laravel.com']"
                        :key="d"
                        type="button"
                        @click="
                            urlInput = d;
                            checkWebsite();
                        "
                        class="rounded-lg border border-gray-200 bg-gray-50 px-2 py-0.5 text-[10px] font-medium text-gray-700 transition-colors hover:bg-gray-100 sm:text-[11px] dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        {{ d }}
                    </button>
                </div>
            </CardContent>
        </Card>

        <!-- Error Message -->
        <div
            v-if="errorMessage"
            class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-3.5 text-xs font-semibold text-rose-700 sm:mb-6 sm:p-4 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300"
        >
            <div class="flex items-center gap-2">
                <Icon name="alertTriangle" class="h-4 w-4 shrink-0" />
                <span>{{ errorMessage }}</span>
            </div>
        </div>

        <!-- Result Section -->
        <div v-if="result" class="space-y-3 sm:space-y-4">
            <!-- Top Status Banner -->
            <div
                class="flex flex-col gap-3 rounded-2xl border p-4 shadow-xs sm:flex-row sm:items-center sm:justify-between sm:rounded-3xl sm:p-5"
                :class="
                    result.ok
                        ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/40 dark:bg-emerald-950/20'
                        : 'border-rose-200 bg-rose-50/50 dark:border-rose-900/40 dark:bg-rose-950/20'
                "
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl font-bold text-white shadow-xs sm:h-12 sm:w-12"
                        :class="result.ok ? 'bg-emerald-500' : 'bg-rose-500'"
                    >
                        <Icon :name="result.ok ? 'check' : 'x'" class="h-5 w-5 sm:h-6 sm:w-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-black text-gray-900 sm:text-lg dark:text-white">{{ result.host }}</h2>
                            <span
                                class="py-0.2 rounded-full px-2 text-[9px] font-extrabold uppercase sm:text-[10px]"
                                :class="
                                    result.ok
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200'
                                        : 'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-200'
                                "
                            >
                                {{ result.ok ? 'Online / Reachable' : 'Offline / Error' }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-[11px] text-gray-500 sm:text-xs dark:text-gray-400">
                            Target: <span class="font-mono text-gray-700 dark:text-gray-300">{{ result.url }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <div class="rounded-xl border border-gray-200 bg-white p-2.5 text-center shadow-2xs sm:p-3 dark:border-gray-800 dark:bg-gray-900">
                        <span class="block text-[9px] font-bold tracking-wider text-gray-400 uppercase">HTTP Code</span>
                        <span
                            class="text-base font-black sm:text-lg"
                            :class="result.ok ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                        >
                            {{ result.status_code || 'Err' }}
                        </span>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-2.5 text-center shadow-2xs sm:p-3 dark:border-gray-800 dark:bg-gray-900">
                        <span class="block text-[9px] font-bold tracking-wider text-gray-400 uppercase">Latency</span>
                        <span class="text-base font-black text-blue-600 sm:text-lg dark:text-blue-400"> {{ result.response_time_ms }}ms </span>
                    </div>
                </div>
            </div>

            <!-- Monitor CTA -->
            <div class="rounded-2xl border border-blue-200 bg-blue-50/70 p-3.5 text-center sm:p-4 dark:border-blue-900/40 dark:bg-blue-950/30">
                <p class="text-xs font-bold text-blue-900 dark:text-blue-200">Want 24/7 automated monitoring for {{ result.host }}?</p>
                <p class="mt-0.5 text-[11px] text-blue-700 dark:text-blue-300">
                    Get instant Telegram, Discord, and Email alerts the moment downtime is detected.
                </p>
                <Link
                    :href="`/monitor/create?url=${encodeURIComponent(result.url || 'https://' + result.host)}`"
                    class="mt-2.5 inline-flex items-center gap-1 rounded-xl bg-blue-600 px-4 py-1.5 text-xs font-bold text-white shadow-xs transition-all hover:bg-blue-700 active:scale-95"
                >
                    <span>Add to 24/7 Monitoring</span>
                    <Icon name="arrowRight" class="h-3 w-3" />
                </Link>
            </div>
        </div>
    </PublicLayout>
</template>
