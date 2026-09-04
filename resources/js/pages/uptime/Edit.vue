<script setup lang="ts">
import TagInput from '@/components/TagInput.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input, Select } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SharedData } from '@/types';
import { type BreadcrumbItem } from '@/types';
import type { Monitor } from '@/types/monitor';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ChevronDown, Loader2, Minus, Plus, Save } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const page = usePage<SharedData>();
const userId = page.props.auth?.user?.id;

const props = defineProps<{
    monitor: {
        data: Monitor & {
            sensitivity?: string;
            confirmation_delay_seconds?: number | null;
            confirmation_retries?: number | null;
        };
    };
}>();

// State for collapsible advanced settings
const showAdvanced = ref(false);

// Extract tag names from the monitor data
const extractTagNames = (tags: any[]): string[] => {
    if (!tags || !Array.isArray(tags)) return [];
    return tags.map((tag) => (typeof tag === 'string' ? tag : tag.name));
};

// Store initial values for dirty checking
const initialValues = {
    url: props.monitor.data.url,
    uptime_check_enabled: props.monitor.data.uptime_check_enabled,
    certificate_check_enabled: props.monitor.data.certificate_check_enabled,
    domain_expiration_check_enabled: props.monitor.data.domain_expiration_check_enabled,
    uptime_check_interval: props.monitor.data.uptime_check_interval || 5,
    is_public: props.monitor.data.is_public ?? false,
    tags: extractTagNames(props.monitor.data.tags || []),
    sensitivity: props.monitor.data.sensitivity ?? 'medium',
    confirmation_delay_seconds: props.monitor.data.confirmation_delay_seconds ?? null,
    confirmation_retries: props.monitor.data.confirmation_retries ?? null,
};

// Inisialisasi form dengan data monitor yang ada
const form = useForm({
    url: props.monitor.data.url,
    uptime_check_enabled: props.monitor.data.uptime_check_enabled,
    certificate_check_enabled: props.monitor.data.certificate_check_enabled,
    domain_expiration_check_enabled: props.monitor.data.domain_expiration_check_enabled,
    uptime_check_interval: props.monitor.data.uptime_check_interval || 5,
    is_public: (props.monitor.data.is_public ?? false) as boolean,
    tags: extractTagNames(props.monitor.data.tags || []),
    sensitivity: props.monitor.data.sensitivity ?? 'medium',
    confirmation_delay_seconds: props.monitor.data.confirmation_delay_seconds ?? null,
    confirmation_retries: props.monitor.data.confirmation_retries ?? null,
});

const visibilityValue = computed({
    get: () => (form.is_public ? 'public' : 'private'),
    set: (val: string) => {
        form.is_public = val === 'public';
    },
});

const sensitivityOptions = [
    { label: 'Rendah (delay 60s, 5x retry)', value: 'low' },
    { label: 'Sedang (default, delay 30s, 3x retry)', value: 'medium' },
    { label: 'Tinggi (delay 15s, 2x retry)', value: 'high' },
];

// Add methods for interval control
const incrementInterval = () => {
    if (form.uptime_check_interval < 60) {
        form.uptime_check_interval += 1;
    }
};

const decrementInterval = () => {
    if (form.uptime_check_interval > 1) {
        form.uptime_check_interval -= 1;
    }
};

// Function to check if form is dirty
const isFormDirty = () => {
    const tagsChanged = JSON.stringify(form.tags.sort()) !== JSON.stringify(initialValues.tags.sort());

    return (
        form.url !== initialValues.url ||
        form.uptime_check_enabled !== initialValues.uptime_check_enabled ||
        form.certificate_check_enabled !== initialValues.certificate_check_enabled ||
        form.domain_expiration_check_enabled !== initialValues.domain_expiration_check_enabled ||
        form.uptime_check_interval !== initialValues.uptime_check_interval ||
        form.is_public !== initialValues.is_public ||
        form.sensitivity !== initialValues.sensitivity ||
        form.confirmation_delay_seconds !== initialValues.confirmation_delay_seconds ||
        form.confirmation_retries !== initialValues.confirmation_retries ||
        tagsChanged
    );
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Uptime Monitor',
        href: '/monitors',
    },
    {
        title: `Edit: ${props.monitor.data.url}`,
        href: `/monitors/${props.monitor.data.id}/edit`,
    },
];

const submit = () => {
    if (!isFormDirty()) {
        alert('Tidak ada perubahan yang terdeteksi.');
        return;
    }

    form.put(route('monitors.update', props.monitor.data.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Edit Monitor: ${form.url}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-foreground">Edit Monitor: {{ form.url }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <Card>
                    <form @submit.prevent="submit">
                        <CardHeader>
                            <CardTitle>Pengaturan Monitor</CardTitle>
                            <CardDescription>
                                Perbarui konfigurasi URL, interval, dan pemeriksaan kesehatan untuk monitor ini.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-6">
                            <!-- URL Input -->
                            <div class="space-y-2">
                                <Label for="url">URL Monitor</Label>
                                <Input
                                    id="url"
                                    type="url"
                                    v-model="form.url"
                                    required
                                    autofocus
                                    autocomplete="url"
                                    placeholder="https://example.com"
                                    :class="{
                                        'border-destructive focus-visible:ring-destructive/20': form.errors.url,
                                    }"
                                />
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

                            <!-- Advanced Settings Section - Collapsible -->
                            <div class="rounded-xl border p-4 dark:border-input/50">
                                <button
                                    @click="showAdvanced = !showAdvanced"
                                    type="button"
                                    class="flex w-full cursor-pointer items-center justify-between text-left"
                                >
                                    <div>
                                        <h3 class="text-sm font-semibold text-foreground">Pengaturan Lanjutan</h3>
                                        <p class="text-xs text-muted-foreground">Konfigurasi toleransi retry dan sensitivitas delay</p>
                                    </div>
                                    <ChevronDown
                                        :class="{ 'rotate-180': showAdvanced }"
                                        class="h-4 w-4 text-muted-foreground transition-transform duration-200"
                                    />
                                </button>

                                <div v-show="showAdvanced" class="mt-4 space-y-4 border-t pt-4">
                                    <!-- Sensitivity -->
                                    <div class="space-y-1.5">
                                        <Label for="sensitivity">Sensitivitas</Label>
                                        <Select
                                            v-model="form.sensitivity"
                                            :items="sensitivityOptions"
                                            placeholder="Pilih sensitivitas"
                                        />
                                        <p class="text-xs text-muted-foreground">Sensitivitas rendah cocok untuk jaringan dengan latency fluktuatif</p>
                                    </div>

                                    <!-- Custom Confirmation Delay -->
                                    <div class="space-y-1.5">
                                        <Label for="confirmation_delay_seconds">Delay Konfirmasi (detik)</Label>
                                        <Input
                                            id="confirmation_delay_seconds"
                                            type="number"
                                            v-model.number="form.confirmation_delay_seconds"
                                            min="5"
                                            max="300"
                                            placeholder="Gunakan default dari sensitivitas"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            Waktu tunggu sebelum mengonfirmasi status down (kosongkan untuk default)
                                        </p>
                                    </div>

                                    <!-- Custom Retries -->
                                    <div class="space-y-1.5">
                                        <Label for="confirmation_retries">Jumlah Retry</Label>
                                        <Input
                                            id="confirmation_retries"
                                            type="number"
                                            v-model.number="form.confirmation_retries"
                                            min="1"
                                            max="10"
                                            placeholder="Gunakan default dari sensitivitas"
                                        />
                                        <p class="text-xs text-muted-foreground">Jumlah percobaan ulang sebelum konfirmasi insiden down</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>

                        <CardFooter class="flex items-center justify-end gap-3 border-t pt-6">
                            <Button type="button" variant="outline" @click="router.visit('/monitors')">
                                Batal
                            </Button>
                            <Button type="submit" :disabled="form.processing">
                                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                <Save v-else class="mr-2 h-4 w-4" />
                                Perbarui Monitor
                            </Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
