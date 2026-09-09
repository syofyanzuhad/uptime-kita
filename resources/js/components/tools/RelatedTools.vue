<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

export interface ToolItem {
    slug: string;
    title: string;
    description: string;
    icon: string;
    color: string;
    badge: string;
    href: string;
    queryParam?: 'domain' | 'url';
}

const ALL_TOOLS: ToolItem[] = [
    {
        slug: 'website-checker',
        title: 'Website & Uptime Checker',
        description: 'Verify uptime, HTTP status code, and latency in real time.',
        icon: 'zap',
        color: 'from-blue-600 to-indigo-600',
        badge: 'Instant Uptime',
        href: '/tools/website-checker',
        queryParam: 'url',
    },
    {
        slug: 'domain-expiration',
        title: 'Domain Expiration Checker',
        description: 'Check WHOIS expiration date, registrar, and days remaining.',
        icon: 'calendarClock',
        color: 'from-violet-500 to-purple-600',
        badge: 'WHOIS / RDAP',
        href: '/tools/domain-expiration',
        queryParam: 'domain',
    },
    {
        slug: 'ssl-checker',
        title: 'SSL Certificate Checker',
        description: 'Verify SSL validity, issuer, expiration, and SAN domains.',
        icon: 'lock',
        color: 'from-emerald-500 to-teal-600',
        badge: 'Instant',
        href: '/tools/ssl-checker',
        queryParam: 'domain',
    },
    {
        slug: 'dns-lookup',
        title: 'DNS Record Lookup',
        description: 'Inspect A, AAAA, MX, TXT, CNAME, and NS records with TTL.',
        icon: 'globe',
        color: 'from-blue-500 to-indigo-600',
        badge: 'DNS Records',
        href: '/tools/dns-lookup',
        queryParam: 'domain',
    },
    {
        slug: 'headers-checker',
        title: 'Security Headers Analyzer',
        description: 'Audit HSTS, CSP, and security headers with grading.',
        icon: 'shield',
        color: 'from-purple-500 to-violet-600',
        badge: 'Security Audit',
        href: '/tools/headers-checker',
        queryParam: 'url',
    },
    {
        slug: 'badge-generator',
        title: 'README Badge Generator',
        description: 'Generate dynamic live uptime status shield badges.',
        icon: 'tag',
        color: 'from-amber-500 to-orange-600',
        badge: 'Shields',
        href: '/tools/badge-generator',
        queryParam: 'domain',
    },
];

interface Props {
    currentSlug: string;
    targetValue?: string;
}

const props = defineProps<Props>();

const relatedTools = computed(() => {
    return ALL_TOOLS.filter((tool) => tool.slug !== props.currentSlug);
});

function getToolHref(tool: ToolItem): string {
    const rawVal = props.targetValue?.trim();
    if (!rawVal || !tool.queryParam) {
        return tool.href;
    }

    const cleanDomain = rawVal.replace(/^https?:\/\//i, '').replace(/\/.*$/, '');
    const cleanUrl = rawVal.startsWith('http://') || rawVal.startsWith('https://') ? rawVal : `https://${rawVal}`;

    const paramVal = tool.queryParam === 'url' ? cleanUrl : cleanDomain;
    return `${tool.href}?${tool.queryParam}=${encodeURIComponent(paramVal)}`;
}
</script>

<template>
    <section class="mt-10 border-t border-gray-200/80 pt-8 sm:mt-14 sm:pt-10 dark:border-gray-800/80">
        <div class="mb-5 flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
            <div>
                <div class="inline-flex items-center gap-1 text-[11px] font-bold tracking-wider text-blue-600 uppercase dark:text-blue-400">
                    <Icon name="sparkles" class="h-3 w-3" />
                    <span>Free Web Utilities</span>
                </div>
                <h2 class="text-base font-black text-gray-900 sm:text-xl dark:text-white">Other Free Developer Tools</h2>
            </div>
            <Link
                href="/tools"
                class="inline-flex items-center gap-1 text-xs font-bold text-gray-500 transition-colors hover:text-blue-600 sm:self-auto dark:text-gray-400 dark:hover:text-blue-400"
            >
                <span>View all tools</span>
                <Icon name="arrowRight" class="h-3.5 w-3.5" />
            </Link>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="tool in relatedTools"
                :key="tool.slug"
                :href="getToolHref(tool)"
                class="group flex flex-col justify-between rounded-2xl border border-gray-200/80 bg-white/80 p-4 shadow-2xs backdrop-blur-xs transition-all hover:-translate-y-0.5 hover:border-blue-400 hover:shadow-xs dark:border-gray-800/80 dark:bg-gray-900/80 dark:hover:border-blue-600"
            >
                <div>
                    <div class="mb-3 flex items-center justify-between">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br text-white shadow-xs transition-transform group-hover:scale-105"
                            :class="tool.color"
                        >
                            <Icon :name="tool.icon" class="h-4 w-4" />
                        </div>
                        <span
                            class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                        >
                            {{ tool.badge }}
                        </span>
                    </div>

                    <h3 class="text-sm font-bold text-gray-900 group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400">
                        {{ tool.title }}
                    </h3>
                    <p class="mt-1 text-xs leading-relaxed text-gray-500 line-clamp-2 dark:text-gray-400">
                        {{ tool.description }}
                    </p>
                </div>

                <div class="mt-3.5 flex items-center gap-1 text-[11px] font-bold text-blue-600 dark:text-blue-400">
                    <span>Try this tool</span>
                    <Icon name="arrowRight" class="h-3 w-3 transition-transform group-hover:translate-x-1" />
                </div>
            </Link>
        </div>
    </section>
</template>
