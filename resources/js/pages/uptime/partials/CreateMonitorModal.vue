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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

defineProps<{
    open: boolean;
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
    domain_expiration_check_enabled: false,
    uptime_check_interval: 5,
    is_public: false,
    tags: [] as string[],
});

// DNS validation states
const isDnsChecking = ref(false);
const dnsStatus = ref<'idle' | 'checking' | 'valid' | 'invalid'>('idle');
const dnsMessage = ref('');
const showDnsWarningDismissed = ref(false);
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

// DNS validation function
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
    showDnsWarningDismissed.value = false;

    try {
        const response = await fetch(`https://cloudflare-dns.com/dns-query?name=${hostname}&type=A`, {
            headers: {
                Accept: 'application/dns-json',
            },
        });

        if (!response.ok) throw new Error('DNS check failed');

        const data = await response.json();
        if (data.Status === 0 && data.Answer && data.Answer.length > 0) {
            dnsStatus.value = 'valid';
            const ips = data.Answer.filter((a: any) => a.type === 1).map((a: any) => a.data);
            dnsMessage.value = `DNS resolved: ${ips.join(', ')}`;
        } else if (data.Status === 3) {
            dnsStatus.value = 'invalid';
            dnsMessage.value = 'Domain does not exist (NXDOMAIN)';
        } else {
            dnsStatus.value = 'invalid';
            dnsMessage.value = 'DNS resolution failed';
        }
    } catch {
        dnsStatus.value = 'invalid';
        dnsMessage.value = 'Unable to verify domain';
    } finally {
        isDnsChecking.value = false;
    }
};

watch(
    () => form.url,
    (newUrl) => {
        if (dnsCheckTimeout) clearTimeout(dnsCheckTimeout);
        if (!newUrl) {
            dnsStatus.value = 'idle';
            dnsMessage.value = '';
            showDnsWarningDismissed.value = false;
            return;
        }
        dnsCheckTimeout = window.setTimeout(() => {
            if (newUrl && newUrl.startsWith('http')) {
                checkDns(newUrl);
            }
        }, 800);
    },
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
    if (dnsStatus.value === 'invalid' && !showDnsWarningDismissed.value) {
        showDnsWarningDismissed.value = true;
        return;
    }

    form.post(route('monitors.store'), {
        onSuccess: () => {
            form.reset();
            dnsStatus.value = 'idle';
            dnsMessage.value = '';
            showDnsWarningDismissed.value = false;
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
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Tambah Monitor Baru</DialogTitle>
                <DialogDescription> Tambahkan URL website atau API yang ingin Anda pantau. </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4 py-2">
                <div class="space-y-1.5">
                    <Label for="url">URL Monitor</Label>
                    <div class="relative">
                        <Input
                            id="url"
                            type="url"
                            class="h-10 pr-10"
                            v-model="form.url"
                            required
                            placeholder="https://example.com"
                        />
                        <div v-if="dnsStatus !== 'idle'" class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <Icon v-if="dnsStatus === 'checking'" name="clock" class="text-muted-foreground h-4 w-4 animate-spin" />
                            <Icon v-else-if="dnsStatus === 'valid'" name="checkCircle" class="h-4 w-4 text-emerald-500" />
                            <Icon v-else-if="dnsStatus === 'invalid'" name="alertTriangle" class="h-4 w-4 text-amber-500" />
                        </div>
                    </div>
                    <div
                        v-if="dnsMessage"
                        class="text-xs transition-colors"
                        :class="{
                            'text-muted-foreground': dnsStatus === 'checking',
                            'text-emerald-600 dark:text-emerald-400': dnsStatus === 'valid',
                            'text-amber-600 dark:text-amber-400': dnsStatus === 'invalid',
                        }"
                    >
                        {{ dnsMessage }}
                    </div>
                    <div v-if="form.errors.url" class="text-destructive text-xs">{{ form.errors.url }}</div>
                </div>

                <!-- Inline DNS warning card if unverified -->
                <div
                    v-if="dnsStatus === 'invalid' && showDnsWarningDismissed"
                    class="border-amber-500/30 bg-amber-50/70 dark:bg-amber-950/30 rounded-md border p-3 text-xs text-amber-800 dark:text-amber-200"
                >
                    <div class="flex items-start gap-2">
                        <Icon name="alertTriangle" class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
                        <div>
                            <span class="font-medium">DNS belum terverifikasi:</span>
                            Monitor tetap dapat disimpan, namun pemeriksaan kemungkinan akan gagal sampai DNS dapat diakses. Tekan "Tambah Monitor" lagi untuk melanjutkan.
                        </div>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label for="interval">Interval Pengecekan (menit)</Label>
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
                            id="interval"
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
                            id="create-uptime-check"
                            :checked="form.uptime_check_enabled"
                            @update:checked="form.uptime_check_enabled = $event"
                        />
                        <Label for="create-uptime-check" class="cursor-pointer text-sm font-normal">
                            Aktifkan Pengecekan Uptime
                        </Label>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="create-cert-check"
                            :checked="form.certificate_check_enabled"
                            @update:checked="form.certificate_check_enabled = $event"
                        />
                        <Label for="create-cert-check" class="cursor-pointer text-sm font-normal">
                            Aktifkan Pengecekan Sertifikat SSL
                        </Label>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="create-domain-check"
                            :checked="form.domain_expiration_check_enabled"
                            @update:checked="form.domain_expiration_check_enabled = $event"
                        />
                        <Label for="create-domain-check" class="cursor-pointer text-sm font-normal">
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
                            <RadioGroupItem id="create-vis-public" value="public" />
                            <Label for="create-vis-public" class="cursor-pointer text-sm font-normal">Publik</Label>
                        </div>
                        <div class="flex items-center gap-2">
                            <RadioGroupItem id="create-vis-private" value="private" />
                            <Label for="create-vis-private" class="cursor-pointer text-sm font-normal">Privat</Label>
                        </div>
                    </RadioGroup>
                </div>

                <DialogFooter class="pt-4">
                    <Button type="button" variant="outline" @click="close">Batal</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : (dnsStatus === 'invalid' && !showDnsWarningDismissed ? 'Verifikasi & Tambah' : 'Tambah Monitor') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
