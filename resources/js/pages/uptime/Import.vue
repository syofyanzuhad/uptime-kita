<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { DuplicateAction, ImportPreviewResult, ImportRow } from '@/types/import';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Uptime Monitor',
        href: '/monitor',
    },
    {
        title: 'Import',
        href: '/monitor/import',
    },
];

// Step management
const currentStep = ref<1 | 2>(1);

// File upload state
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFileName = ref<string>('');
const selectedFile = ref<File | null>(null);
const isUploading = ref(false);
const uploadError = ref<string>('');

// Preview state
const previewResult = ref<ImportPreviewResult | null>(null);

// Import form
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const importForm = useForm<any>({
    rows: [] as ImportRow[],
    duplicate_action: 'skip' as DuplicateAction,
    resolutions: {} as Record<number, DuplicateAction>,
});

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        selectedFile.value = file;
        selectedFileName.value = file.name;
        uploadError.value = '';
    }
};

const triggerFileInput = () => {
    fileInput.value?.click();
};

const uploadAndPreview = async () => {
    if (!selectedFile.value) return;

    isUploading.value = true;
    uploadError.value = '';

    const formData = new FormData();
    formData.append('import_file', selectedFile.value);

    try {
        const response = await axios.post(route('monitor.import.preview'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        previewResult.value = response.data;
        importForm.rows = response.data.rows;

        if (response.data.rows.length > 0) {
            currentStep.value = 2;
        } else {
            uploadError.value = 'File tidak berisi data monitor yang valid.';
        }
    } catch (error: unknown) {
        if (axios.isAxiosError(error) && error.response?.data?.errors) {
            uploadError.value = Object.values(error.response.data.errors).flat().join(', ');
        } else if (axios.isAxiosError(error) && error.response?.data?.message) {
            uploadError.value = error.response.data.message;
        } else {
            uploadError.value = 'Gagal memproses file. Pastikan format file benar.';
        }
    } finally {
        isUploading.value = false;
    }
};

const goBackToStep1 = () => {
    currentStep.value = 1;
    previewResult.value = null;
    importForm.reset();
};

const submitImport = () => {
    importForm.post(route('monitor.import.process'), {
        preserveScroll: true,
    });
};

// Computed properties for preview stats
const validRows = computed(() => previewResult.value?.rows.filter((r) => r._status === 'valid') || []);
const errorRows = computed(() => previewResult.value?.rows.filter((r) => r._status === 'error') || []);
const duplicateRows = computed(() => previewResult.value?.rows.filter((r) => r._status === 'duplicate') || []);

const canImport = computed(() => {
    return validRows.value.length > 0 || duplicateRows.value.length > 0;
});

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'valid':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'error':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        case 'duplicate':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
};

const getStatusLabel = (status: string) => {
    switch (status) {
        case 'valid':
            return 'Valid';
        case 'error':
            return 'Error';
        case 'duplicate':
            return 'Duplikat';
        default:
            return status;
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Import Monitor" />

        <div class="mx-auto max-w-4xl space-y-6 px-4 py-6">
            <!-- Step Indicator -->
            <div class="flex items-center justify-center space-x-4">
                <div class="flex items-center">
                    <div
                        :class="[
                            'flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium',
                            currentStep >= 1 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground',
                        ]"
                    >
                        1
                    </div>
                    <span class="ml-2 text-sm font-medium">Upload File</span>
                </div>
                <div class="bg-muted h-px w-12"></div>
                <div class="flex items-center">
                    <div
                        :class="[
                            'flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium',
                            currentStep >= 2 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground',
                        ]"
                    >
                        2
                    </div>
                    <span class="ml-2 text-sm font-medium">Preview & Import</span>
                </div>
            </div>

            <!-- Step 1: Upload File -->
            <div v-if="currentStep === 1" class="space-y-6">
                <HeadingSmall title="Import Monitor" description="Upload file CSV atau JSON untuk import monitor secara bulk" />

                <!-- Sample Templates -->
                <div class="flex flex-wrap gap-2">
                    <Button as="a" :href="route('monitor.import.sample.csv')" variant="outline" size="sm"> Download Template CSV </Button>
                    <Button as="a" :href="route('monitor.import.sample.json')" variant="outline" size="sm"> Download Template JSON </Button>
                </div>

                <!-- File Upload -->
                <div class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="import_file">File Import</Label>
                        <div class="flex gap-2">
                            <input
                                ref="fileInput"
                                id="import_file"
                                type="file"
                                accept=".csv,.json,.txt"
                                class="hidden"
                                @change="handleFileChange"
                            />
                            <Input
                                type="text"
                                :value="selectedFileName"
                                placeholder="Pilih file CSV atau JSON"
                                readonly
                                class="flex-1 cursor-pointer"
                                @click="triggerFileInput"
                            />
                            <Button type="button" variant="outline" @click="triggerFileInput"> Browse </Button>
                        </div>
                        <p class="text-muted-foreground text-sm">Format yang didukung: CSV, JSON (max 10MB)</p>
                        <InputError v-if="uploadError" :message="uploadError" />
                    </div>

                    <Button :disabled="!selectedFile || isUploading" @click="uploadAndPreview">
                        <template v-if="isUploading"> Memproses... </template>
                        <template v-else> Preview Data </template>
                    </Button>
                </div>

                <!-- Instructions -->
                <Alert>
                    <AlertTitle>Format File</AlertTitle>
                    <AlertDescription>
                        <div class="space-y-2">
                            <p><strong>CSV:</strong> Baris pertama harus berisi header kolom.</p>
                            <p><strong>JSON:</strong> Array of objects atau object dengan key "monitors".</p>
                            <p class="text-muted-foreground mt-2 text-sm">
                                Kolom yang didukung: url (wajib), display_name, uptime_check_enabled, certificate_check_enabled,
                                uptime_check_interval, is_public, sensitivity, expected_status_code, tags
                            </p>
                        </div>
                    </AlertDescription>
                </Alert>
            </div>

            <!-- Step 2: Preview & Import -->
            <div v-if="currentStep === 2 && previewResult" class="space-y-6">
                <HeadingSmall title="Preview Import" description="Review data sebelum import" />

                <!-- Summary Stats -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="rounded-lg border p-4 text-center">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ validRows.length }}</div>
                        <div class="text-muted-foreground text-sm">Valid</div>
                    </div>
                    <div class="rounded-lg border p-4 text-center">
                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ duplicateRows.length }}</div>
                        <div class="text-muted-foreground text-sm">Duplikat</div>
                    </div>
                    <div class="rounded-lg border p-4 text-center">
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ errorRows.length }}</div>
                        <div class="text-muted-foreground text-sm">Error</div>
                    </div>
                </div>

                <!-- Duplicate Action -->
                <div v-if="duplicateRows.length > 0" class="space-y-3 rounded-lg border p-4">
                    <Label>Penanganan URL Duplikat</Label>
                    <RadioGroup v-model="importForm.duplicate_action" class="flex flex-col space-y-2">
                        <div class="flex items-center space-x-2">
                            <RadioGroupItem id="skip" value="skip" />
                            <Label for="skip" class="font-normal">Lewati - Jangan import URL yang sudah ada</Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <RadioGroupItem id="update" value="update" />
                            <Label for="update" class="font-normal">Update - Perbarui monitor yang sudah ada</Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <RadioGroupItem id="create" value="create" />
                            <Label for="create" class="font-normal">Buat Baru - Import sebagai monitor baru (URL tetap sama)</Label>
                        </div>
                    </RadioGroup>
                </div>

                <!-- Preview Table -->
                <div class="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-16">#</TableHead>
                                <TableHead>URL</TableHead>
                                <TableHead>Display Name</TableHead>
                                <TableHead class="w-24">Status</TableHead>
                                <TableHead>Keterangan</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in previewResult.rows" :key="row._row_number">
                                <TableCell class="font-mono text-sm">{{ row._row_number }}</TableCell>
                                <TableCell class="max-w-xs truncate font-mono text-sm">{{ row.url }}</TableCell>
                                <TableCell>{{ row.display_name || '-' }}</TableCell>
                                <TableCell>
                                    <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-medium', getStatusBadgeClass(row._status)]">
                                        {{ getStatusLabel(row._status) }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-muted-foreground text-sm">
                                    <template v-if="row._status === 'error'">
                                        <ul class="list-inside list-disc text-red-600 dark:text-red-400">
                                            <li v-for="(error, idx) in row._errors" :key="idx">{{ error }}</li>
                                        </ul>
                                    </template>
                                    <template v-else-if="row._status === 'duplicate'"> URL sudah ada di database </template>
                                    <template v-else> Siap diimport </template>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between">
                    <Button variant="outline" @click="goBackToStep1"> Kembali </Button>
                    <Button :disabled="!canImport || importForm.processing" @click="submitImport">
                        <template v-if="importForm.processing"> Mengimport... </template>
                        <template v-else> Import {{ validRows.length + (importForm.duplicate_action !== 'skip' ? duplicateRows.length : 0) }} Monitor </template>
                    </Button>
                </div>

                <InputError v-if="importForm.errors.rows" :message="importForm.errors.rows" />
            </div>
        </div>
    </AppLayout>
</template>
