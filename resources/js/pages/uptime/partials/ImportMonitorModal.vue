<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import type { ImportPreviewResult, ImportRow, DuplicateAction } from '@/types/import';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits(['update:open']);

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

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
const importForm = useForm({
    rows: [] as ImportRow[],
    duplicate_action: 'update' as DuplicateAction, // Default to update (overwrite)
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
            uploadError.value = 'File does not contain valid monitor data.';
        }
    } catch (error: any) {
        if (axios.isAxiosError(error) && error.response?.data?.errors) {
            uploadError.value = Object.values(error.response.data.errors).flat().join(', ');
        } else if (axios.isAxiosError(error) && error.response?.data?.message) {
            uploadError.value = error.response.data.message;
        } else {
            uploadError.value = 'Failed to process file. Ensure the format is correct.';
        }
    } finally {
        isUploading.value = false;
    }
};

const reset = () => {
    currentStep.value = 1;
    previewResult.value = null;
    selectedFile.value = null;
    selectedFileName.value = '';
    uploadError.value = '';
    importForm.reset();
};

const submitImport = () => {
    importForm.post(route('monitor.import.process'), {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
            reset();
        },
    });
};

const validRows = computed(() => previewResult.value?.rows.filter((r) => r._status === 'valid') || []);
const errorRows = computed(() => previewResult.value?.rows.filter((r) => r._status === 'error') || []);
const duplicateRows = computed(() => previewResult.value?.rows.filter((r) => r._status === 'duplicate') || []);

const canImport = computed(() => {
    return validRows.value.length > 0 || duplicateRows.value.length > 0;
});
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[700px] max-h-[90vh] flex flex-col p-0 overflow-hidden">
            <DialogHeader class="p-6 pb-0">
                <DialogTitle class="text-xl font-black uppercase tracking-widest">Import Monitors</DialogTitle>
                <DialogDescription class="text-[10px] font-bold uppercase tracking-widest text-gray-500">
                    Upload CSV or JSON file to bulk import monitors.
                </DialogDescription>
            </DialogHeader>

            <div class="flex-1 overflow-y-auto p-6">
                <!-- Step 1: Upload -->
                <div v-if="currentStep === 1" class="space-y-6">
                    <div class="grid gap-4">
                        <div class="space-y-2">
                            <Label class="text-[10px] font-bold uppercase tracking-widest">Select File</Label>
                            <div class="flex gap-2">
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept=".csv,.json"
                                    class="hidden"
                                    @change="handleFileChange"
                                />
                                <Input
                                    type="text"
                                    :value="selectedFileName"
                                    placeholder="CHOOSE CSV OR JSON FILE..."
                                    readonly
                                    class="h-10 rounded-lg bg-gray-50 text-[10px] font-bold uppercase tracking-widest dark:bg-gray-900 cursor-pointer"
                                    @click="triggerFileInput"
                                />
                                <Button type="button" variant="outline" @click="triggerFileInput" class="h-10 text-[10px] font-bold uppercase tracking-widest px-4">
                                    BROWSE
                                </Button>
                            </div>
                            <InputError v-if="uploadError" :message="uploadError" />
                        </div>

                        <div class="flex gap-2">
                            <Button as="a" :href="route('monitor.import.sample.csv')" variant="ghost" class="h-8 text-[9px] font-bold uppercase tracking-widest px-2">
                                <Icon name="download" class="mr-1" size="12" /> CSV TEMPLATE
                            </Button>
                            <Button as="a" :href="route('monitor.import.sample.json')" variant="ghost" class="h-8 text-[9px] font-bold uppercase tracking-widest px-2">
                                <Icon name="download" class="mr-1" size="12" /> JSON TEMPLATE
                            </Button>
                        </div>
                    </div>

                    <Alert class="bg-blue-50/50 dark:bg-blue-950/20 border-blue-100 dark:border-blue-900">
                        <Icon name="info" class="text-blue-500" size="16" />
                        <AlertTitle class="text-[10px] font-bold uppercase tracking-widest text-blue-700 dark:text-blue-400">Import Mode</AlertTitle>
                        <AlertDescription class="text-[10px] font-medium text-blue-600 dark:text-blue-500 uppercase tracking-tight">
                            By default, this will overwrite existing monitors if the URL matches.
                        </AlertDescription>
                    </Alert>
                </div>

                <!-- Step 2: Preview -->
                <div v-else-if="currentStep === 2 && previewResult" class="space-y-6">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="rounded-lg bg-green-50 dark:bg-green-950/20 p-3 text-center border border-green-100 dark:border-green-900">
                            <div class="text-lg font-black text-green-600">{{ validRows.length }}</div>
                            <div class="text-[9px] font-bold uppercase tracking-widest text-green-700">VALID</div>
                        </div>
                        <div class="rounded-lg bg-yellow-50 dark:bg-yellow-950/20 p-3 text-center border border-yellow-100 dark:border-yellow-900">
                            <div class="text-lg font-black text-yellow-600">{{ duplicateRows.length }}</div>
                            <div class="text-[9px] font-bold uppercase tracking-widest text-yellow-700">EXISTING</div>
                        </div>
                        <div class="rounded-lg bg-red-50 dark:bg-red-950/20 p-3 text-center border border-red-100 dark:border-red-900">
                            <div class="text-lg font-black text-red-600">{{ errorRows.length }}</div>
                            <div class="text-[9px] font-bold uppercase tracking-widest text-red-700">ERROR</div>
                        </div>
                    </div>

                    <div class="rounded-lg border dark:border-gray-800 overflow-hidden">
                        <Table>
                            <TableHeader class="bg-gray-50 dark:bg-gray-900">
                                <TableRow>
                                    <TableHead class="text-[9px] font-black uppercase tracking-widest h-8 w-12 text-center">#</TableHead>
                                    <TableHead class="text-[9px] font-black uppercase tracking-widest h-8">URL</TableHead>
                                    <TableHead class="text-[9px] font-black uppercase tracking-widest h-8 w-24">STATUS</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="row in previewResult.rows.slice(0, 50)" :key="row._row_number" class="h-10">
                                    <TableCell class="text-[10px] font-mono text-center text-gray-500">{{ row._row_number }}</TableCell>
                                    <TableCell class="text-[10px] font-medium truncate max-w-[200px]">{{ row.url }}</TableCell>
                                    <TableCell>
                                        <span v-if="row._status === 'valid'" class="text-[9px] font-bold text-green-600 uppercase">READY</span>
                                        <span v-else-if="row._status === 'duplicate'" class="text-[9px] font-bold text-yellow-600 uppercase">OVERWRITE</span>
                                        <span v-else class="text-[9px] font-bold text-red-600 uppercase">ERROR</span>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="previewResult.rows.length > 50">
                                    <TableCell colspan="3" class="text-center text-[9px] font-bold text-gray-400 uppercase py-4">
                                        ... AND {{ previewResult.rows.length - 50 }} MORE ROWS
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-gray-50 dark:bg-gray-950 flex justify-between gap-3 border-t dark:border-gray-800">
                <Button variant="ghost" @click="isOpen = false" class="text-[10px] font-bold uppercase tracking-widest">
                    CANCEL
                </Button>
                
                <div class="flex gap-2">
                    <Button v-if="currentStep === 2" variant="outline" @click="currentStep = 1" class="text-[10px] font-bold uppercase tracking-widest">
                        BACK
                    </Button>
                    
                    <Button 
                        v-if="currentStep === 1"
                        :disabled="!selectedFile || isUploading" 
                        @click="uploadAndPreview"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold uppercase tracking-widest px-6"
                    >
                        <template v-if="isUploading">PROCESSING...</template>
                        <template v-else>PREVIEW DATA</template>
                    </Button>

                    <Button 
                        v-else
                        :disabled="!canImport || importForm.processing" 
                        @click="submitImport"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold uppercase tracking-widest px-6"
                    >
                        <template v-if="importForm.processing">IMPORTING...</template>
                        <template v-else>IMPORT DATA</template>
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
