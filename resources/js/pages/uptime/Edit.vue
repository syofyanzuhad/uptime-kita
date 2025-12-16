<script setup lang="ts">
import TagInput from '@/components/TagInput.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

import type { SharedData } from '@/types';
import type { Monitor } from '@/types/monitor';

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
    uptime_check_interval: props.monitor.data.uptime_check_interval || 5,
    is_public: props.monitor.data.is_public ?? false,
    tags: extractTagNames(props.monitor.data.tags || []),
    sensitivity: props.monitor.data.sensitivity ?? 'medium',
    confirmation_delay_seconds: props.monitor.data.confirmation_delay_seconds ?? null,
    confirmation_retries: props.monitor.data.confirmation_retries ?? null,
});
// console.log(form.url);

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
        href: '/monitor',
    },
    {
        title: `Edit: ${props.monitor.data.url}`,
        href: `/monitor/${props.monitor.data.id}/edit`,
    },
];

const submit = () => {
    if (!isFormDirty()) {
        // If form is not dirty, show a message or handle as needed
        alert('No changes detected. Nothing to update.');
        return;
    }

    form.put(route('monitor.update', props.monitor.data.id), {
        onFinish: () => {},
    });
};
</script>

<template>
    <Head :title="`Edit Monitor: ${form.url}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-gray-800 dark:text-gray-200">Edit Monitor: {{ form.url }}</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <form @submit.prevent="submit">
                        <div class="mb-4">
                            <label for="url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL Monitor</label>
                            <input
                                id="url"
                                type="url"
                                class="border-input bg-background ring-offset-background file:text-foreground placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200"
                                v-model="form.url"
                                required
                                autofocus
                                autocomplete="url"
                            />
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

                        <!-- Advanced Settings Section - Collapsible -->
                        <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-700">
                            <button
                                @click="showAdvanced = !showAdvanced"
                                type="button"
                                class="flex w-full items-center justify-between text-left"
                            >
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Pengaturan Lanjutan</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Konfigurasi untuk mengurangi false positive
                                    </p>
                                </div>
                                <svg
                                    :class="showAdvanced ? 'rotate-180' : ''"
                                    class="h-5 w-5 text-gray-500 transition-transform duration-200"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </button>

                            <div v-show="showAdvanced" class="mt-4 space-y-4">
                                <!-- Sensitivity -->
                                <div>
                                    <label for="sensitivity" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Sensitivitas
                                    </label>
                                    <select
                                        id="sensitivity"
                                        v-model="form.sensitivity"
                                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                    >
                                        <option value="low">Rendah (lebih toleran, delay 60s, 5x retry)</option>
                                        <option value="medium">Sedang (default, delay 30s, 3x retry)</option>
                                        <option value="high">Tinggi (cepat alert, delay 15s, 2x retry)</option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Sensitivitas rendah cocok untuk jaringan tidak stabil
                                    </p>
                                </div>

                                <!-- Custom Confirmation Delay -->
                                <div>
                                    <label for="confirmation_delay_seconds" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Delay Konfirmasi (detik)
                                    </label>
                                    <input
                                        id="confirmation_delay_seconds"
                                        type="number"
                                        v-model="form.confirmation_delay_seconds"
                                        min="5"
                                        max="300"
                                        placeholder="Gunakan default dari sensitivitas"
                                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Waktu tunggu sebelum mengonfirmasi status down (kosongkan untuk gunakan default sensitivitas)
                                    </p>
                                </div>

                                <!-- Custom Retries -->
                                <div>
                                    <label for="confirmation_retries" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Jumlah Retry
                                    </label>
                                    <input
                                        id="confirmation_retries"
                                        type="number"
                                        v-model="form.confirmation_retries"
                                        min="1"
                                        max="10"
                                        placeholder="Gunakan default dari sensitivitas"
                                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Berapa kali percobaan ulang sebelum konfirmasi down
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-end">
                            <button
                                type="submit"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                                class="inline-flex cursor-pointer items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none active:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:bg-gray-600 dark:active:bg-gray-800"
                            >
                                Perbarui Monitor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
