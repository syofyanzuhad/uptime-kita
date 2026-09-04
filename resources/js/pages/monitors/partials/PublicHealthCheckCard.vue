<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const heroInputRef = ref<HTMLInputElement | null>(null);
const domainInput = ref('');
const domainChecking = ref(false);
const domainResult = ref<null | {
    url: string;
    host: string;
    status_code: number | null;
    ok: boolean;
    response_time_ms: number;
    error?: string;
}>(null);
const domainError = ref('');
const exampleDomains = ['google.com', 'github.com', 'cloudflare.com'];
const showApiSnippet = ref(false);
const copiedApi = ref(false);
const isMobileExpanded = ref(false);

async function checkDomain() {
    isMobileExpanded.value = true;
    const v = domainInput.value.trim();
    if (!v) {
        domainError.value = 'Enter a domain or URL to inspect.';
        return;
    }
    domainChecking.value = true;
    domainResult.value = null;
    domainError.value = '';
    try {
        const res = await fetch(`/api/check-domain?url=${encodeURIComponent(v)}`, {
            headers: { Accept: 'application/json' },
        });
        const data = await res.json();
        if (!res.ok) {
            domainError.value = data.message || 'Check failed. Please check the domain.';
            return;
        }
        domainResult.value = data;
    } catch {
        domainError.value = 'Network error. Please try again.';
    } finally {
        domainChecking.value = false;
    }
}

function tryExample(domain: string) {
    isMobileExpanded.value = true;
    domainInput.value = domain;
    checkDomain();
}

function copyApiCommand(customHost?: string) {
    const target = customHost || domainInput.value.trim() || 'example.com';
    const baseUrl = typeof window !== 'undefined' ? window.location.origin : 'https://uptime.syofyanzuhad.dev';
    const cmd = `curl -X GET "${baseUrl}/api/v1/check?url=${encodeURIComponent(target)}"`;
    navigator.clipboard.writeText(cmd);
    copiedApi.value = true;
    setTimeout(() => {
        copiedApi.value = false;
    }, 2000);
}

defineExpose({
    focusInput: () => {
        isMobileExpanded.value = true;
        heroInputRef.value?.focus();
    },
});
</script>

<template>
    <!-- Instant Website Health Check Card -->
    <div
        class="mb-4 rounded-2xl border border-gray-200/80 bg-white p-3.5 shadow-xs transition-all sm:rounded-3xl sm:p-5 dark:border-gray-800/80 dark:bg-gray-900/90"
    >
        <!-- Header Row (Collapsible toggle on mobile, static on desktop) -->
        <div
            @click="isMobileExpanded = !isMobileExpanded"
            class="flex cursor-pointer items-center justify-between gap-2 select-none sm:cursor-default"
            :class="[isMobileExpanded ? 'mb-3' : 'mb-0 sm:mb-3']"
        >
            <div class="flex min-w-0 items-center gap-2">
                <Icon name="sparkles" class="h-4 w-4 shrink-0 text-amber-500" />
                <h2 class="truncate text-xs font-bold text-gray-900 sm:text-base dark:text-white">Instant Website Health Check</h2>
            </div>
            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                <div
                    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/70 bg-emerald-50/90 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 sm:px-2.5 sm:text-[11px] dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    <span class="xs:inline hidden">Free & No Sign-up</span>
                    <span class="xs:hidden">Free</span>
                </div>
                <button
                    type="button"
                    class="-mr-0.5 cursor-pointer p-1 text-gray-400 transition-transform hover:text-gray-600 sm:hidden dark:hover:text-gray-200"
                    :aria-label="isMobileExpanded ? 'Collapse health check' : 'Expand health check'"
                >
                    <Icon :name="isMobileExpanded ? 'chevronUp' : 'chevronDown'" class="h-4 w-4" />
                </button>
            </div>
        </div>

        <!-- Collapsible Content (Collapsed on mobile, always visible on sm:) -->
        <div :class="[isMobileExpanded ? 'block' : 'hidden sm:block']">
            <!-- Input Form -->
            <form @submit.prevent="checkDomain" class="flex flex-col items-center gap-2 sm:flex-row">
                <label for="hero-domain-input" class="sr-only">Domain or URL to check</label>
                <div class="relative w-full flex-1">
                    <input
                        ref="heroInputRef"
                        id="hero-domain-input"
                        v-model="domainInput"
                        type="text"
                        placeholder="Enter any domain or URL (e.g. google.com, myapp.io)..."
                        autocomplete="off"
                        class="w-full rounded-xl border-0 bg-gray-100/90 py-2.5 pr-8 pl-4 text-xs text-gray-900 placeholder-gray-400 transition-all focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:outline-none sm:text-sm dark:bg-gray-800/90 dark:text-white dark:placeholder-gray-500 dark:focus:bg-gray-800"
                    />
                    <button
                        v-if="domainInput"
                        type="button"
                        @click="
                            domainInput = '';
                            domainResult = null;
                            domainError = '';
                        "
                        class="absolute top-1/2 right-2.5 -translate-y-1/2 cursor-pointer rounded-full p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                        aria-label="Clear input"
                    >
                        <Icon name="x" class="h-3 w-3" />
                    </button>
                </div>

                <button
                    type="submit"
                    :disabled="domainChecking"
                    class="inline-flex w-full shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-blue-700 active:scale-95 disabled:opacity-70 sm:w-auto sm:text-sm"
                >
                    <Icon v-if="domainChecking" name="loader" class="h-3.5 w-3.5 animate-spin" />
                    <Icon v-else name="zap" class="h-3.5 w-3.5 text-white" />
                    <span>Check Now</span>
                </button>
            </form>

            <!-- Bottom Row: Try suggestions & CLI/API button -->
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-xs font-medium text-gray-400">Try:</span>
                    <button
                        v-for="ex in exampleDomains"
                        :key="ex"
                        type="button"
                        @click="tryExample(ex)"
                        class="cursor-pointer rounded-lg bg-gray-100/80 px-2.5 py-1 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-200/80 dark:bg-gray-800/80 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        {{ ex }}
                    </button>
                </div>

                <button
                    type="button"
                    @click="showApiSnippet = !showApiSnippet"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-gray-100/80 px-2.5 py-1 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-200/80 dark:bg-gray-800/80 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <Icon name="terminal" class="h-3 w-3 text-gray-500 dark:text-gray-400" />
                    <span>CLI / API</span>
                </button>
            </div>

            <!-- Error message if any -->
            <div v-if="domainError" class="mt-2 text-xs font-medium text-rose-500">
                {{ domainError }}
            </div>

            <!-- API Code Drawer (when toggled) -->
            <div
                v-if="showApiSnippet"
                class="mt-3 overflow-hidden rounded-xl border border-gray-200 bg-gray-950 p-3 text-left shadow-lg dark:border-gray-800"
            >
                <div class="mb-2 flex items-center justify-between border-b border-gray-800 pb-2 text-[11px] text-gray-400">
                    <div class="flex items-center gap-1.5 font-sans font-bold text-white">
                        <Icon name="code" class="h-3.5 w-3.5 text-emerald-400" />
                        <span>Instant Uptime API</span>
                        <span class="py-0.2 rounded bg-emerald-500/20 px-1.5 text-[9px] font-extrabold text-emerald-300">30 req/min free</span>
                    </div>
                    <span class="font-mono text-[10px] text-gray-400">GET /api/v1/check</span>
                </div>
                <div class="flex items-center justify-between gap-2 overflow-x-auto rounded-lg bg-black/60 p-2 font-mono text-xs text-emerald-400">
                    <span class="truncate text-gray-300 select-all">
                        curl -X GET "https://uptime.syofyanzuhad.dev/api/v1/check?url={{ domainInput || 'example.com' }}"
                    </span>
                    <button
                        type="button"
                        @click="copyApiCommand(domainInput || 'example.com')"
                        class="shrink-0 cursor-pointer rounded bg-white/10 px-2.5 py-1 text-xs font-bold text-white transition-colors hover:bg-white/20 active:scale-95"
                    >
                        {{ copiedApi ? 'Copied!' : 'Copy' }}
                    </button>
                </div>
            </div>

            <!-- Domain Check Result Card -->
            <div
                v-if="domainResult"
                class="mt-3 rounded-xl border p-3 text-left shadow-sm transition-all"
                :class="
                    domainResult.ok
                        ? 'border-emerald-200 bg-emerald-50/50 text-emerald-950 dark:border-emerald-800/40 dark:bg-emerald-950/40 dark:text-emerald-100'
                        : 'border-rose-200 bg-rose-50/50 text-rose-950 dark:border-rose-800/40 dark:bg-rose-950/40 dark:text-rose-100'
                "
            >
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded-lg font-bold text-white"
                            :class="domainResult.ok ? 'bg-emerald-500' : 'bg-rose-500'"
                        >
                            <Icon :name="domainResult.ok ? 'check' : 'x'" class="h-4 w-4" />
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-bold">{{ domainResult.host }}</span>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[9px] font-extrabold uppercase"
                                    :class="
                                        domainResult.ok
                                            ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300'
                                            : 'bg-rose-500/20 text-rose-700 dark:text-rose-300'
                                    "
                                >
                                    {{ domainResult.ok ? 'Operational' : 'Down' }}
                                </span>
                            </div>
                            <div class="mt-0.5 flex flex-wrap items-center gap-2 text-[10px] text-gray-500 dark:text-gray-400">
                                <span v-if="domainResult.status_code">HTTP {{ domainResult.status_code }}</span>
                                <span v-if="domainResult.response_time_ms">Latency: {{ domainResult.response_time_ms }}ms</span>
                            </div>
                        </div>
                    </div>
                    <Link
                        :href="`/monitors/create?url=${encodeURIComponent(domainResult.url || 'https://' + domainResult.host)}`"
                        class="self-end rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-blue-700 sm:self-auto"
                    >
                        Track Uptime
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
