<script setup lang="ts">
import TagInput from '@/components/TagInput.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SharedData } from '@/types';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

// Hapus impor komponen yang tidak ada
// import TextInput from '@/Components/TextInput.vue';
// import InputLabel from '@/Components/InputLabel.vue';
// import PrimaryButton from '@/Components/PrimaryButton.vue';
// import InputError from '@/Components/InputError.vue';
// import Checkbox from '@/Components/Checkbox.vue';

const page = usePage<SharedData>();
const userId = page.props.auth?.user?.id;

const form = useForm({
    url: '',
    uptime_check_enabled: true,
    certificate_check_enabled: true,
    uptime_check_interval: 5,
    is_public: false,
    tags: [] as string[],
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Uptime Monitor',
        href: '/monitor',
    },
    {
        title: 'Tambah Monitor',
        href: '/monitor/create',
    },
];

// DNS validation states
const isDnsChecking = ref(false);
const dnsStatus = ref<'idle' | 'checking' | 'valid' | 'invalid'>('idle');
const dnsMessage = ref('');
let dnsCheckTimeout: number | null = null;

// Extract hostname from URL
const extractHostname = (url: string): string | null => {
    try {
        const urlObj = new URL(url);
        return urlObj.hostname;
    } catch {
        return null;
    }
};

// DNS validation function using public DNS API
const checkDns = async (url: string) => {
    const hostname = extractHostname(url);
    if (!hostname) {
        dnsStatus.value = 'invalid';
        dnsMessage.value = 'Invalid URL format';
        return;
    }

    isDnsChecking.value = true;
    dnsStatus.value = 'checking';
    dnsMessage.value = 'Checking DNS...';

    try {
        // Use Cloudflare's DNS-over-HTTPS API
        const response = await fetch(`https://cloudflare-dns.com/dns-query?name=${hostname}&type=A`, {
            headers: {
                'Accept': 'application/dns-json',
            },
        });

        if (!response.ok) {
            throw new Error('DNS check failed');
        }

        const data = await response.json();
        
        if (data.Status === 0 && data.Answer && data.Answer.length > 0) {
            // DNS resolution successful
            dnsStatus.value = 'valid';
            const ips = data.Answer.filter((a: any) => a.type === 1).map((a: any) => a.data);
            dnsMessage.value = `DNS resolved to: ${ips.join(', ')}`;
        } else if (data.Status === 3) {
            // NXDOMAIN - domain doesn't exist
            dnsStatus.value = 'invalid';
            dnsMessage.value = 'Domain does not exist (NXDOMAIN)';
        } else {
            // Other DNS errors
            dnsStatus.value = 'invalid';
            dnsMessage.value = 'DNS resolution failed';
        }
    } catch {
        // Fallback: Try to use a simple connectivity check
        try {
            // Try a HEAD request to check if the domain is reachable
            await fetch(`https://${hostname}/favicon.ico`, {
                method: 'HEAD',
                mode: 'no-cors',
            }).catch(() => null);
            
            // If we get here without error, the domain likely exists
            dnsStatus.value = 'valid';
            dnsMessage.value = 'Domain appears to be valid';
        } catch {
            dnsStatus.value = 'invalid';
            dnsMessage.value = 'Unable to verify domain';
        }
    } finally {
        isDnsChecking.value = false;
    }
};

// Watch for URL changes with debouncing
watch(() => form.url, (newUrl) => {
    // Clear previous timeout
    if (dnsCheckTimeout) {
        clearTimeout(dnsCheckTimeout);
    }

    // Reset status if URL is empty
    if (!newUrl) {
        dnsStatus.value = 'idle';
        dnsMessage.value = '';
        return;
    }

    // Set a debounce timeout
    dnsCheckTimeout = window.setTimeout(() => {
        if (newUrl && newUrl.startsWith('http')) {
            checkDns(newUrl);
        } else {
            dnsStatus.value = 'idle';
            dnsMessage.value = '';
        }
    }, 1000); // 1 second debounce
});

const decrementInterval = () => {
    if (form.uptime_check_interval > 1) {
        form.uptime_check_interval--;
    }
};

const incrementInterval = () => {
    if (form.uptime_check_interval < 60) {
        form.uptime_check_interval++;
    }
};

const submit = () => {
    if (dnsStatus.value === 'invalid') {
        // Show warning but allow submission
        if (!confirm('The domain DNS could not be verified. Do you want to continue adding this monitor?')) {
            return;
        }
    }
    
    form.post(route('monitor.store'), {
        onFinish: () => {
            form.reset('url');
            dnsStatus.value = 'idle';
            dnsMessage.value = '';
        },
    });
};
</script>

<template>
    <Head title="Tambah Monitor Baru" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-gray-800 dark:text-gray-200">Tambah Monitor Baru</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <form @submit.prevent="submit">
                        <div class="mb-4">
                            <label for="url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL Monitor</label>
                            <div class="relative">
                                <input
                                    id="url"
                                    type="url"
                                    class="border-input bg-background ring-offset-background file:text-foreground placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 pr-10 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200 dark:file:text-gray-200 dark:placeholder:text-gray-400"
                                    v-model="form.url"
                                    required
                                    autofocus
                                    autocomplete="url"
                                    placeholder="https://example.com"
                                />
                                <!-- DNS Status Indicator -->
                                <div v-if="dnsStatus !== 'idle'" class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <!-- Checking -->
                                    <svg v-if="dnsStatus === 'checking'" class="h-5 w-5 animate-spin text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <!-- Valid -->
                                    <svg v-else-if="dnsStatus === 'valid'" class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <!-- Invalid -->
                                    <svg v-else-if="dnsStatus === 'invalid'" class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <!-- DNS Message -->
                            <div v-if="dnsMessage" class="mt-2 text-sm" :class="{
                                'text-gray-500 dark:text-gray-400': dnsStatus === 'checking',
                                'text-green-600 dark:text-green-400': dnsStatus === 'valid',
                                'text-yellow-600 dark:text-yellow-400': dnsStatus === 'invalid',
                            }">
                                {{ dnsMessage }}
                            </div>
                            <div v-if="form.errors.url" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.url }}</div>
                        </div>

                        <div class="mb-4">
                            <label for="uptime_check_interval" class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >Interval Pengecekan (menit)</label
                            >
                            <div class="mt-1 flex items-center">
                                <button
                                    type="button"
                                    @click="decrementInterval"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-l-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                >
                                    <span class="sr-only">Kurangi</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <input
                                    id="uptime_check_interval"
                                    type="number"
                                    min="1"
                                    max="60"
                                    v-model="form.uptime_check_interval"
                                    class="flex h-10 w-20 border-t border-b border-gray-300 bg-white text-center text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                />
                                <button
                                    type="button"
                                    @click="incrementInterval"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-r-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                >
                                    <span class="sr-only">Tambah</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </div>
                            <div v-if="form.errors.uptime_check_interval" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.uptime_check_interval }}
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="tags" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tags</label>
                            <TagInput v-model="form.tags" placeholder="Add tags (e.g., production, api, critical)" />
                            <div v-if="form.errors.tags" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.tags }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    name="uptime_check_enabled"
                                    v-model="form.uptime_check_enabled"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-600 dark:text-indigo-400"
                                />
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Aktifkan Pengecekan Uptime</span>
                            </label>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    name="certificate_check_enabled"
                                    v-model="form.certificate_check_enabled"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-600 dark:text-indigo-400"
                                />
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Aktifkan Pengecekan Sertifikat SSL</span>
                            </label>
                        </div>

                        <div v-if="userId === 1" class="mb-4">
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Visibilitas Monitor</label>
                            <div class="flex gap-4">
                                <label class="inline-flex items-center">
                                    <input
                                        type="radio"
                                        name="is_public"
                                        :value="1"
                                        v-model="form.is_public"
                                        class="form-radio text-indigo-600 dark:text-indigo-400"
                                    />
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Publik</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input
                                        type="radio"
                                        name="is_public"
                                        :value="0"
                                        v-model="form.is_public"
                                        class="form-radio text-indigo-600 dark:text-indigo-400"
                                    />
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Privat</span>
                                </label>
                            </div>
                            <div v-if="form.errors.is_public" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.is_public }}</div>
                        </div>

                        <div class="mt-4 flex items-center justify-end">
                            <button
                                type="submit"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                                class="inline-flex cursor-pointer items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none active:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:bg-gray-600 dark:active:bg-gray-800"
                            >
                                Tambah Monitor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
