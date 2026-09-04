<script setup lang="ts">
import TagInput from '@/components/TagInput.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SharedData } from '@/types';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle2, Loader2, Minus, Plus } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    url?: string;
}>();

const page = usePage<SharedData>();
const userId = page.props.auth?.user?.id;

const getInitialUrl = (): string => {
    if (props.url) {
        return props.url;
    }
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        return params.get('url') || '';
    }
    return '';
};

const form = useForm({
    url: getInitialUrl(),
    uptime_check_enabled: true,
    certificate_check_enabled: true,
    domain_expiration_check_enabled: false,
    uptime_check_interval: 5,
    is_public: false,
    tags: [] as string[],
});

const visibilityValue = computed({
    get: () => (form.is_public ? 'public' : 'private'),
    set: (val: string) => {
        form.is_public = val === 'public';
    },
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Uptime Monitor',
        href: '/monitors',
    },
    {
        title: 'Tambah Monitor',
        href: '/monitors/create',
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
                Accept: 'application/dns-json',
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
watch(
    () => form.url,
    (newUrl) => {
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
    },
);

onMounted(() => {
    if (form.url && form.url.startsWith('http')) {
        checkDns(form.url);
    }
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

    form.post(route('monitors.store'), {
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
            <h2 class="text-xl leading-tight font-semibold text-foreground">Tambah Monitor Baru</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <Card>
                    <form @submit.prevent="submit">
                        <CardHeader>
                            <CardTitle>Detail Monitor</CardTitle>
                            <CardDescription>
                                Konfigurasikan URL situs web dan parameter pengecekan uptime secara berkala.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-6">
                            <!-- URL Input -->
                            <div class="space-y-2">
                                <Label for="url">URL Monitor</Label>
                                <div class="relative">
                                    <Input
                                        id="url"
                                        type="url"
                                        v-model="form.url"
                                        required
                                        autofocus
                                        autocomplete="url"
                                        placeholder="https://example.com"
                                        class="pr-10"
                                        :class="{
                                            'border-destructive focus-visible:ring-destructive/20': form.errors.url,
                                        }"
                                    />
                                    <!-- DNS Status Indicator -->
                                    <div v-if="dnsStatus !== 'idle'" class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                        <Loader2 v-if="dnsStatus === 'checking'" class="h-4 w-4 animate-spin text-muted-foreground" />
                                        <CheckCircle2 v-else-if="dnsStatus === 'valid'" class="h-4 w-4 text-emerald-500" />
                                        <AlertTriangle v-else-if="dnsStatus === 'invalid'" class="h-4 w-4 text-amber-500" />
                                    </div>
                                </div>
                                <!-- DNS Message -->
                                <div
                                    v-if="dnsMessage"
                                    class="flex items-center gap-1.5 text-xs"
                                    :class="{
                                        'text-muted-foreground': dnsStatus === 'checking',
                                        'text-emerald-600 dark:text-emerald-400': dnsStatus === 'valid',
                                        'text-amber-600 dark:text-amber-400': dnsStatus === 'invalid',
                                    }"
                                >
                                    <CheckCircle2 v-if="dnsStatus === 'valid'" class="h-3.5 w-3.5 shrink-0" />
                                    <AlertTriangle v-else-if="dnsStatus === 'invalid'" class="h-3.5 w-3.5 shrink-0" />
                                    <Loader2 v-else-if="dnsStatus === 'checking'" class="h-3.5 w-3.5 shrink-0 animate-spin" />
                                    <span>{{ dnsMessage }}</span>
                                </div>
                                <div v-if="form.errors.url" class="text-xs text-destructive">{{ form.errors.url }}</div>
                            </div>

                            <!-- Interval Input -->
                            <div class="space-y-2">
                                <Label for="uptime_check_interval">Interval Pengecekan</Label>
                                <div class="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        @click="decrementInterval"
                                        :disabled="form.uptime_check_interval <= 1"
                                    >
                                        <Minus class="h-4 w-4" />
                                        <span class="sr-only">Kurangi</span>
                                    </Button>
                                    <Input
                                        id="uptime_check_interval"
                                        type="number"
                                        min="1"
                                        max="60"
                                        v-model.number="form.uptime_check_interval"
                                        class="w-20 text-center font-medium"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        @click="incrementInterval"
                                        :disabled="form.uptime_check_interval >= 60"
                                    >
                                        <Plus class="h-4 w-4" />
                                        <span class="sr-only">Tambah</span>
                                    </Button>
                                    <span class="text-sm text-muted-foreground">menit</span>
                                </div>
                                <div v-if="form.errors.uptime_check_interval" class="text-xs text-destructive">
                                    {{ form.errors.uptime_check_interval }}
                                </div>
                            </div>

                            <!-- Tags -->
                            <div class="space-y-2">
                                <Label for="tags">Tags</Label>
                                <TagInput v-model="form.tags" placeholder="Tambah tag (contoh: production, api, critical)" />
                                <div v-if="form.errors.tags" class="text-xs text-destructive">{{ form.errors.tags }}</div>
                            </div>

                            <!-- Feature Checks Toggles -->
                            <div class="space-y-3">
                                <Label class="text-sm font-medium">Fitur Pengecekan</Label>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between rounded-lg border p-3.5 shadow-2xs dark:border-input/50">
                                        <div class="space-y-0.5">
                                            <Label for="uptime_check_enabled" class="cursor-pointer text-sm font-medium">Pengecekan Uptime</Label>
                                            <p class="text-xs text-muted-foreground">Periksa ketersediaan situs secara berkala sesuai interval</p>
                                        </div>
                                        <Switch id="uptime_check_enabled" v-model="form.uptime_check_enabled" />
                                    </div>

                                    <div class="flex items-center justify-between rounded-lg border p-3.5 shadow-2xs dark:border-input/50">
                                        <div class="space-y-0.5">
                                            <Label for="certificate_check_enabled" class="cursor-pointer text-sm font-medium">Sertifikat SSL</Label>
                                            <p class="text-xs text-muted-foreground">Pantau validitas dan masa berlaku sertifikat SSL/TLS</p>
                                        </div>
                                        <Switch id="certificate_check_enabled" v-model="form.certificate_check_enabled" />
                                    </div>

                                    <div class="flex items-center justify-between rounded-lg border p-3.5 shadow-2xs dark:border-input/50">
                                        <div class="space-y-0.5">
                                            <Label for="domain_expiration_check_enabled" class="cursor-pointer text-sm font-medium">Kedaluwarsa Domain</Label>
                                            <p class="text-xs text-muted-foreground">Peringatan sebelum domain Anda memasuki masa kedaluwarsa</p>
                                        </div>
                                        <Switch id="domain_expiration_check_enabled" v-model="form.domain_expiration_check_enabled" />
                                    </div>
                                </div>
                            </div>

                            <!-- Visibility (Admin only) -->
                            <div v-if="userId === 1" class="space-y-2">
                                <Label class="text-sm font-medium">Visibilitas Monitor</Label>
                                <RadioGroup v-model="visibilityValue" class="flex gap-6 pt-1">
                                    <div class="flex items-center space-x-2">
                                        <RadioGroupItem id="public" value="public" />
                                        <Label for="public" class="cursor-pointer font-normal">Publik</Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <RadioGroupItem id="private" value="private" />
                                        <Label for="private" class="cursor-pointer font-normal">Privat</Label>
                                    </div>
                                </RadioGroup>
                                <div v-if="form.errors.is_public" class="text-xs text-destructive">{{ form.errors.is_public }}</div>
                            </div>
                        </CardContent>

                        <CardFooter class="flex items-center justify-end gap-3 border-t pt-6">
                            <Button type="button" variant="outline" @click="router.visit('/monitors')">
                                Batal
                            </Button>
                            <Button type="submit" :disabled="form.processing">
                                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                <Plus v-else class="mr-2 h-4 w-4" />
                                Tambah Monitor
                            </Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
