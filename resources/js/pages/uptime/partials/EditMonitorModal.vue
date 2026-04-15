<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import TagInput from '@/components/TagInput.vue';
import Button from '@/components/ui/button/Button.vue';
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import DialogDescription from '@/components/ui/dialog/DialogDescription.vue';
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue';
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import type { Monitor } from '@/types/monitor';

const props = defineProps<{
    open: boolean;
    monitor: Monitor | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

const page = usePage();
const userId = (page.props.auth as any)?.user?.id;

const form = useForm({
    url: '',
    uptime_check_enabled: true,
    certificate_check_enabled: true,
    uptime_check_interval: 5,
    is_public: false,
    tags: [] as string[],
    sensitivity: 'medium',
    confirmation_delay_seconds: null as number | null,
    confirmation_retries: null as number | null,
});

const showAdvanced = ref(false);

const extractTagNames = (tags: any[]): string[] => {
    if (!tags || !Array.isArray(tags)) return [];
    return tags.map((tag) => (typeof tag === 'string' ? tag : tag.name));
};

watch(() => props.monitor, (newMonitor) => {
    if (newMonitor) {
        form.url = newMonitor.url;
        form.uptime_check_enabled = newMonitor.uptime_check_enabled;
        form.certificate_check_enabled = newMonitor.certificate_check_enabled;
        form.uptime_check_interval = newMonitor.uptime_check_interval || 5;
        form.is_public = newMonitor.is_public ?? false;
        form.tags = extractTagNames(newMonitor.tags || []);
        form.sensitivity = (newMonitor as any).sensitivity ?? 'medium';
        form.confirmation_delay_seconds = (newMonitor as any).confirmation_delay_seconds ?? null;
        form.confirmation_retries = (newMonitor as any).confirmation_retries ?? null;
    }
}, { immediate: true });

const decrementInterval = () => {
    if (form.uptime_check_interval > 1) form.uptime_check_interval--;
};

const incrementInterval = () => {
    if (form.uptime_check_interval < 60) form.uptime_check_interval++;
};

const submit = () => {
    if (!props.monitor) return;
    
    form.put(route('monitor.update', props.monitor.id), {
        onSuccess: () => {
            emit('update:open', false);
            emit('success');
        },
    });
};

const close = () => {
    emit('update:open', false);
};
</script>

<template>
    <Dialog :open="open" @update:open="close">
        <DialogContent class="sm:max-w-lg overflow-y-auto max-h-[90vh]">
            <DialogHeader>
                <DialogTitle>Edit Monitor</DialogTitle>
                <DialogDescription>
                    Perbarui konfigurasi monitor untuk {{ monitor?.url }}.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4 py-4">
                <div>
                    <label for="edit-url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL Monitor</label>
                    <input
                        id="edit-url"
                        type="url"
                        class="mt-1 flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        v-model="form.url"
                        required
                    />
                    <div v-if="form.errors.url" class="mt-1 text-xs text-red-600">{{ form.errors.url }}</div>
                </div>

                <div>
                    <label for="edit-interval" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Interval Pengecekan (menit)</label>
                    <div class="mt-1 flex items-center">
                        <button type="button" @click="decrementInterval" class="flex h-10 w-10 items-center justify-center rounded-l-md border border-gray-300 bg-gray-50 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800">
                            <Icon name="minus" size="16" />
                        </button>
                        <input
                            id="edit-interval"
                            type="number"
                            v-model="form.uptime_check_interval"
                            class="h-10 w-20 border-t border-b border-gray-300 text-center text-sm focus:outline-none dark:border-gray-700 dark:bg-gray-900"
                        />
                        <button type="button" @click="incrementInterval" class="flex h-10 w-10 items-center justify-center rounded-r-md border border-gray-300 bg-gray-50 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800">
                            <Icon name="plus" size="16" />
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tags</label>
                    <div class="mt-1">
                        <TagInput v-model="form.tags" placeholder="Add tags..." />
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" v-model="form.uptime_check_enabled" class="rounded border-gray-300 text-blue-600" />
                        Aktifkan Pengecekan Uptime
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" v-model="form.certificate_check_enabled" class="rounded border-gray-300 text-blue-600" />
                        Aktifkan Pengecekan Sertifikat SSL
                    </label>
                </div>

                <div v-if="userId === 1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Visibilitas</label>
                    <div class="mt-1 flex gap-4">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" :value="true" v-model="form.is_public" class="text-blue-600" />
                            Publik
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" :value="false" v-model="form.is_public" class="text-blue-600" />
                            Privat
                        </label>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" @click="showAdvanced = !showAdvanced" class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <Icon :name="showAdvanced ? 'chevronDown' : 'chevronRight'" size="16" />
                        Pengaturan Lanjutan
                    </button>
                    
                    <div v-if="showAdvanced" class="mt-4 space-y-4">
                        <div>
                            <label for="sensitivity" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sensitivitas</label>
                            <select id="sensitivity" v-model="form.sensitivity" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900">
                                <option value="low">Rendah</option>
                                <option value="medium">Sedang</option>
                                <option value="high">Tinggi</option>
                            </select>
                        </div>
                        <div>
                            <label for="delay" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Delay Konfirmasi (detik)</label>
                            <input id="delay" type="number" v-model="form.confirmation_delay_seconds" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900" />
                        </div>
                        <div>
                            <label for="retries" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jumlah Retry</label>
                            <input id="retries" type="number" v-model="form.confirmation_retries" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900" />
                        </div>
                    </div>
                </div>

                <DialogFooter class="mt-6">
                    <Button type="button" variant="outline" @click="close">Batal</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : 'Perbarui Monitor' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
