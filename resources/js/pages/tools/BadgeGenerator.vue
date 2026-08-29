<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import PublicLayout from '@/components/PublicLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    initialDomain?: string;
    appUrl: string;
}

const props = defineProps<Props>();

const domainInput = ref(props.initialDomain || 'google.com');
const copiedMarkdown = ref(false);
const copiedHtml = ref(false);
const copiedUrl = ref(false);

const cleanDomain = computed(() => {
    let d = domainInput.value.trim();
    d = d.replace(/^https?:\/\//, '').replace(/\/.*$/, '');
    return d || 'example.com';
});

const badgeUrl = computed(() => `${props.appUrl}/badge/${cleanDomain.value}`);
const targetMonitorUrl = computed(() => `${props.appUrl}/m/${cleanDomain.value}`);

const markdownCode = computed(() => `[![Uptime Status](${badgeUrl.value})](${targetMonitorUrl.value})`);
const htmlCode = computed(() => `<a href="${targetMonitorUrl.value}"><img src="${badgeUrl.value}" alt="Uptime Status" /></a>`);

const pageTitle = 'Free GitHub Status Badge Generator - Uptime Kita';
const pageDescription = 'Generate dynamic live uptime status shield badges for your GitHub README, documentation, and open-source repositories.';
const shareUrl = `${props.appUrl}/tools/badge-generator`;
const shareText = 'Generate live uptime shield badges for your GitHub README with Uptime Kita!';

function copy(text: string, type: 'md' | 'html' | 'url') {
    navigator.clipboard.writeText(text);
    if (type === 'md') {
        copiedMarkdown.value = true;
        setTimeout(() => (copiedMarkdown.value = false), 2000);
    } else if (type === 'html') {
        copiedHtml.value = true;
        setTimeout(() => (copiedHtml.value = false), 2000);
    } else {
        copiedUrl.value = true;
        setTimeout(() => (copiedUrl.value = false), 2000);
    }
}
</script>

<template>
    <PublicLayout :title="pageTitle" :description="pageDescription" :share-url="shareUrl" :share-text="shareText" container-class="max-w-4xl">
        <!-- Breadcrumb -->
        <div class="mb-4 flex items-center gap-2 text-xs font-semibold text-gray-500 dark:text-gray-400">
            <Link href="/tools" class="hover:text-blue-600 dark:hover:text-blue-400">Free Tools</Link>
            <span>/</span>
            <span class="text-gray-900 dark:text-white">Badge Generator</span>
        </div>

        <!-- Header -->
        <div class="mb-6 text-center sm:mb-8">
            <div
                class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-md"
            >
                <Icon name="tag" class="h-6 w-6" />
            </div>
            <h1 class="mt-3 text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">README Status Badge Generator</h1>
            <p class="mx-auto mt-1.5 max-w-xl text-xs text-gray-500 sm:text-sm dark:text-gray-400">
                Embed live SVG uptime status badges into your GitHub repositories, documentation, and websites.
            </p>
        </div>

        <!-- Configuration Card -->
        <Card class="mb-6 rounded-3xl border border-gray-200/80 bg-white/80 shadow-sm backdrop-blur-sm dark:border-gray-800/80 dark:bg-gray-900/80">
            <CardContent class="p-4 sm:p-6">
                <div class="space-y-4">
                    <div>
                        <label for="badge-domain" class="block text-xs font-bold text-gray-700 dark:text-gray-300"> Domain Name to Monitor </label>
                        <div class="relative mt-1.5">
                            <Icon name="globe" class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <input
                                id="badge-domain"
                                v-model="domainInput"
                                type="text"
                                placeholder="example.com"
                                class="w-full rounded-xl border border-gray-200/80 bg-gray-50/50 py-2.5 pr-4 pl-10 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-gray-700/80 dark:bg-gray-800/50 dark:text-white dark:focus:bg-gray-800"
                            />
                        </div>
                    </div>

                    <!-- Live Badge Preview -->
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/60 p-4 text-center dark:border-gray-800 dark:bg-gray-800/40">
                        <span class="block text-[11px] font-bold tracking-wider text-gray-400 uppercase">Live Badge Preview</span>
                        <div class="mt-3 flex items-center justify-center">
                            <img :src="badgeUrl" alt="Uptime Status Badge" class="h-6" />
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Code Snippets -->
        <div class="space-y-4">
            <!-- Markdown Code -->
            <Card class="rounded-3xl border border-gray-200/80 bg-white/80 dark:border-gray-800/80 dark:bg-gray-900/80">
                <CardContent class="p-4 sm:p-5">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2 dark:border-gray-800">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Markdown (for README.md)</span>
                        <button
                            type="button"
                            @click="copy(markdownCode, 'md')"
                            class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-600 transition-colors hover:bg-blue-100 dark:bg-blue-950/50 dark:text-blue-400"
                        >
                            {{ copiedMarkdown ? '✓ Copied' : 'Copy Markdown' }}
                        </button>
                    </div>
                    <pre
                        class="mt-3 overflow-x-auto rounded-xl bg-gray-900 p-3 font-mono text-xs text-emerald-400"
                    ><code>{{ markdownCode }}</code></pre>
                </CardContent>
            </Card>

            <!-- HTML Code -->
            <Card class="rounded-3xl border border-gray-200/80 bg-white/80 dark:border-gray-800/80 dark:bg-gray-900/80">
                <CardContent class="p-4 sm:p-5">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2 dark:border-gray-800">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">HTML Code</span>
                        <button
                            type="button"
                            @click="copy(htmlCode, 'html')"
                            class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-600 transition-colors hover:bg-blue-100 dark:bg-blue-950/50 dark:text-blue-400"
                        >
                            {{ copiedHtml ? '✓ Copied' : 'Copy HTML' }}
                        </button>
                    </div>
                    <pre class="mt-3 overflow-x-auto rounded-xl bg-gray-900 p-3 font-mono text-xs text-emerald-400"><code>{{ htmlCode }}</code></pre>
                </CardContent>
            </Card>

            <!-- Direct URL -->
            <Card class="rounded-3xl border border-gray-200/80 bg-white/80 dark:border-gray-800/80 dark:bg-gray-900/80">
                <CardContent class="p-4 sm:p-5">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2 dark:border-gray-800">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Direct Badge Image URL</span>
                        <button
                            type="button"
                            @click="copy(badgeUrl, 'url')"
                            class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-600 transition-colors hover:bg-blue-100 dark:bg-blue-950/50 dark:text-blue-400"
                        >
                            {{ copiedUrl ? '✓ Copied' : 'Copy URL' }}
                        </button>
                    </div>
                    <pre class="mt-3 overflow-x-auto rounded-xl bg-gray-900 p-3 font-mono text-xs text-emerald-400"><code>{{ badgeUrl }}</code></pre>
                </CardContent>
            </Card>
        </div>
    </PublicLayout>
</template>
