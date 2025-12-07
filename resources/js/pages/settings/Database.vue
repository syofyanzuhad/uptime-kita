<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    databaseSize: number;
    databaseExists: boolean;
    essentialRecordCount: number;
    essentialTables: string[];
    excludedTables: string[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Database settings',
        href: '/settings/database',
    },
];

const form = useForm({
    database: null as File | null,
});

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFileName = ref<string>('');

const formatBytes = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatNumber = (num: number): string => {
    return num.toLocaleString();
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        form.database = file;
        selectedFileName.value = file.name;
    }
};

const submitRestore = () => {
    if (!form.database) return;

    form.post(route('database.restore'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            selectedFileName.value = '';
            if (fileInput.value?.$el) {
                (fileInput.value.$el as HTMLInputElement).value = '';
            }
        },
    });
};

const triggerFileInput = () => {
    (fileInput.value?.$el as HTMLInputElement)?.click();
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Database settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Database backup" description="Download a backup of your essential data" />

                <div class="space-y-4">
                    <div class="flex items-center justify-between rounded-lg border p-4">
                        <div>
                            <p class="text-sm font-medium">Essential data backup</p>
                            <p class="text-muted-foreground text-sm">
                                <template v-if="databaseExists">
                                    {{ formatNumber(essentialRecordCount) }} records from {{ essentialTables.length }} tables
                                </template>
                                <template v-else>
                                    No database found
                                </template>
                            </p>
                        </div>
                        <Button
                            as="a"
                            :href="route('database.download')"
                            :disabled="!databaseExists"
                        >
                            Download backup
                        </Button>
                    </div>

                    <Alert>
                        <AlertTitle>What's included in the backup?</AlertTitle>
                        <AlertDescription>
                            <p class="mb-2">
                                The backup includes only essential data that cannot be regenerated:
                            </p>
                            <ul class="text-muted-foreground list-inside list-disc text-sm">
                                <li>Users and authentication data</li>
                                <li>Monitors configuration</li>
                                <li>Notification channels</li>
                                <li>Status pages</li>
                                <li>Tags and monitor assignments</li>
                                <li>Incident history</li>
                            </ul>
                            <p class="text-muted-foreground mt-2 text-sm">
                                <strong>Excluded:</strong> Monitoring history, statistics, and cache data (these are regenerated automatically).
                            </p>
                        </AlertDescription>
                    </Alert>

                    <div class="text-muted-foreground rounded-lg border border-dashed p-4 text-sm">
                        <p class="font-medium">Full database size: {{ formatBytes(databaseSize) }}</p>
                        <p>The backup file will be much smaller as it only contains essential configuration data.</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Restore database" description="Upload a backup file to restore your data" />

                <Alert variant="warning">
                    <AlertTitle>Warning</AlertTitle>
                    <AlertDescription>
                        Restoring will replace data in the backed-up tables. A temporary backup is created before restoring.
                        You will need to log in again after restoring.
                    </AlertDescription>
                </Alert>

                <form @submit.prevent="submitRestore" class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="database">Database file</Label>
                        <div class="flex gap-2">
                            <Input
                                ref="fileInput"
                                id="database"
                                type="file"
                                accept=".sql,.sqlite,.db,.sqlite3"
                                class="hidden"
                                @change="handleFileChange"
                            />
                            <Input
                                type="text"
                                :value="selectedFileName"
                                placeholder="No file selected"
                                readonly
                                class="flex-1 cursor-pointer"
                                @click="triggerFileInput"
                            />
                            <Button type="button" variant="outline" @click="triggerFileInput">
                                Browse
                            </Button>
                        </div>
                        <p class="text-muted-foreground text-sm">
                            Accepted formats: .sql (recommended), .sqlite, .sqlite3, .db (max 500MB)
                        </p>
                        <InputError :message="form.errors.database" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="form.processing || !form.database"
                        >
                            <template v-if="form.processing">
                                Restoring...
                            </template>
                            <template v-else>
                                Restore database
                            </template>
                        </Button>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
