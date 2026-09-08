<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import TagInput from '@/components/TagInput.vue';
import Button from '@/components/ui/button/Button.vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import DialogDescription from '@/components/ui/dialog/DialogDescription.vue';
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue';
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue';
import { Input, Select } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import type { Monitor } from '@/types/monitor';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

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
    uptime_check_enabled: true as boolean,
    certificate_check_enabled: true as boolean,
    domain_expiration_check_enabled: false as boolean,
    uptime_check_interval: 5,
    is_public: false as boolean,
    tags: [] as string[],
    sensitivity: 'medium',
    confirmation_delay_seconds: null as number | null,
    confirmation_retries: null as number | null,
});

const showAdvanced = ref(false);

const sensitivityOptions = [
    { label: 'Rendah', value: 'low' },
    { label: 'Sedang', value: 'medium' },
    { label: 'Tinggi', value: 'high' },
];

const extractTagNames = (tags: any[]): string[] => {
    if (!tags || !Array.isArray(tags)) return [];
    return tags.map((tag) => (typeof tag === 'string' ? tag : tag.name));
};

watch(
    () => props.monitor,
    (newMonitor) => {
        if (newMonitor) {
            form.url = newMonitor.url;
            form.uptime_check_enabled = newMonitor.uptime_check_enabled;
            form.certificate_check_enabled = newMonitor.certificate_check_enabled;
            form.domain_expiration_check_enabled = newMonitor.domain_expiration_check_enabled;
            form.uptime_check_interval = newMonitor.uptime_check_interval || 5;
            form.is_public = newMonitor.is_public ?? false;
            form.tags = extractTagNames(newMonitor.tags || []);
            form.sensitivity = (newMonitor as any).sensitivity ?? 'medium';
            form.confirmation_delay_seconds = (newMonitor as any).confirmation_delay_seconds ?? null;
            form.confirmation_retries = (newMonitor as any).confirmation_retries ?? null;
        }
    },
    { immediate: true },
);

const decrementInterval = () => {
    if (form.uptime_check_interval > 1) form.uptime_check_interval--;
};

const incrementInterval = () => {
    if (form.uptime_check_interval < 60) form.uptime_check_interval++;
};

const clampInterval = () => {
    if (form.uptime_check_interval < 1) form.uptime_check_interval = 1;
    if (form.uptime_check_interval > 60) form.uptime_check_interval = 60;
};

const submit = () => {
    if (!props.monitor) return;

    form.put(route('monitors.update', props.monitor.id), {
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
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Edit Monitor</DialogTitle>
                <DialogDescription> Perbarui konfigurasi monitor untuk {{ monitor?.url }}. </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4 py-2">
                <div class="space-y-1.5">
                    <Label for="edit-url">URL Monitor</Label>
                    <Input id="edit-url" type="url" class="h-10" v-model="form.url" required />
                    <div v-if="form.errors.url" class="text-destructive text-xs">{{ form.errors.url }}</div>
                </div>

                <div class="space-y-1.5">
                    <Label for="edit-interval">Interval Pengecekan (menit)</Label>
                    <div class="flex items-center">
                        <button
                            type="button"
                            @click="decrementInterval"
                            class="border-input bg-muted hover:bg-accent text-foreground flex h-10 w-10 items-center justify-center rounded-l-md border transition-colors"
                            aria-label="Kurangi interval"
                        >
                            <Icon name="minus" size="16" />
                        </button>
                        <input
                            id="edit-interval"
                            type="number"
                            v-model.number="form.uptime_check_interval"
                            @blur="clampInterval"
                            min="1"
                            max="60"
                            class="border-input dark:bg-input/30 text-foreground h-10 w-20 border-t border-b bg-transparent text-center text-sm focus:outline-none"
                        />
                        <button
                            type="button"
                            @click="incrementInterval"
                            class="border-input bg-muted hover:bg-accent text-foreground flex h-10 w-10 items-center justify-center rounded-r-md border transition-colors"
                            aria-label="Tambah interval"
                        >
                            <Icon name="plus" size="16" />
                        </button>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label>Tags</Label>
                    <div>
                        <TagInput v-model="form.tags" placeholder="Tambahkan tags..." />
                    </div>
                </div>

                <div class="space-y-2.5 pt-1">
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="edit-uptime-check"
                            :checked="form.uptime_check_enabled"
                            @update:checked="form.uptime_check_enabled = $event"
                        />
                        <Label for="edit-uptime-check" class="cursor-pointer text-sm font-normal">
                            Aktifkan Pengecekan Uptime
                        </Label>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="edit-cert-check"
                            :checked="form.certificate_check_enabled"
                            @update:checked="form.certificate_check_enabled = $event"
                        />
                        <Label for="edit-cert-check" class="cursor-pointer text-sm font-normal">
                            Aktifkan Pengecekan Sertifikat SSL
                        </Label>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="edit-domain-check"
                            :checked="form.domain_expiration_check_enabled"
                            @update:checked="form.domain_expiration_check_enabled = $event"
                        />
                        <Label for="edit-domain-check" class="cursor-pointer text-sm font-normal">
                            Aktifkan Pengecekan Kedaluwarsa Domain
                        </Label>
                    </div>
                </div>

                <div v-if="userId === 1" class="space-y-1.5 pt-1">
                    <Label>Visibilitas</Label>
                    <RadioGroup
                        :model-value="form.is_public ? 'public' : 'private'"
                        @update:model-value="form.is_public = $event === 'public'"
                        class="flex items-center gap-4"
                    >
                        <div class="flex items-center gap-2">
                            <RadioGroupItem id="edit-vis-public" value="public" />
                            <Label for="edit-vis-public" class="cursor-pointer text-sm font-normal">Publik</Label>
                        </div>
                        <div class="flex items-center gap-2">
                            <RadioGroupItem id="edit-vis-private" value="private" />
                            <Label for="edit-vis-private" class="cursor-pointer text-sm font-normal">Privat</Label>
                        </div>
                    </RadioGroup>
                </div>

                <div class="border-border border-t pt-4">
                    <button
                        type="button"
                        @click="showAdvanced = !showAdvanced"
                        class="text-muted-foreground hover:text-foreground flex items-center gap-2 text-sm font-medium transition-colors"
                    >
                        <Icon :name="showAdvanced ? 'chevronDown' : 'chevronRight'" size="16" />
                        Pengaturan Lanjutan
                    </button>

                    <div v-if="showAdvanced" class="mt-4 space-y-4">
                        <div class="space-y-1.5">
                            <Label for="sensitivity">Sensitivitas</Label>
                            <Select id="sensitivity" v-model="form.sensitivity" :items="sensitivityOptions" class="w-full" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="delay">Delay Konfirmasi (detik)</Label>
                            <Input id="delay" type="number" v-model="form.confirmation_delay_seconds" placeholder="misal: 30" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="retries">Jumlah Retry</Label>
                            <Input id="retries" type="number" v-model="form.confirmation_retries" placeholder="misal: 3" />
                        </div>
                    </div>
                </div>

                <DialogFooter class="pt-4">
                    <Button type="button" variant="outline" @click="close">Batal</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : 'Perbarui Monitor' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
