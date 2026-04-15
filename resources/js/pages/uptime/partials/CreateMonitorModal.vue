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
    uptime_check_interval: 5,
    is_public: false,
    tags: [] as string[],
});

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

    try {
        const response = await fetch(`https://cloudflare-dns.com/dns-query?name=${hostname}&type=A`, {
            headers: {
                'Accept': 'application/dns-json',
            },
        });

        if (!response.ok) throw new Error('DNS check failed');

        const data = await response.json();
        if (data.Status === 0 && data.Answer && data.Answer.length > 0) {
            dnsStatus.value = 'valid';
            const ips = data.Answer.filter((a: any) => a.type === 1).map((a: any) => a.data);
            dnsMessage.value = `DNS resolved to: ${ips.join(', ')}`;
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

watch(() => form.url, (newUrl) => {
    if (dnsCheckTimeout) clearTimeout(dnsCheckTimeout);
    if (!newUrl) {
        dnsStatus.value = 'idle';
        dnsMessage.value = '';
        return;
    }
    dnsCheckTimeout = window.setTimeout(() => {
        if (newUrl && newUrl.startsWith('http')) {
            checkDns(newUrl);
        }
    }, 1000);
});

const decrementInterval = () => {
    if (form.uptime_check_interval > 1) form.uptime_check_interval--;
};

const incrementInterval = () => {
    if (form.uptime_check_interval < 60) form.uptime_check_interval++;
};

const submit = () => {
    if (dnsStatus.value === 'invalid') {
        if (!confirm('The domain DNS could not be verified. Do you want to continue?')) return;
    }
    
    form.post(route('monitor.store'), {
        onSuccess: () => {
            form.reset();
            dnsStatus.value = 'idle';
            dnsMessage.value = '';
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
                <DialogDescription>
                    Tambahkan URL website atau API yang ingin Anda pantau.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4 py-4">
                <div>
                    <label for="url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL Monitor</label>
                    <div class="relative mt-1">
                        <input
                            id="url"
                            type="url"
                            class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 pr-10 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                            v-model="form.url"
                            required
                            placeholder="https://example.com"
                        />
                        <div v-if="dnsStatus !== 'idle'" class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <Icon v-if="dnsStatus === 'checking'" name="clock" class="h-4 w-4 animate-spin text-gray-400" />
                            <Icon v-else-if="dnsStatus === 'valid'" name="checkCircle" class="h-4 w-4 text-green-500" />
                            <Icon v-else-if="dnsStatus === 'invalid'" name="alertTriangle" class="h-4 w-4 text-yellow-500" />
                        </div>
                    </div>
                    <div v-if="dnsMessage" class="mt-1 text-xs" :class="{
                        'text-gray-500': dnsStatus === 'checking',
                        'text-green-600': dnsStatus === 'valid',
                        'text-yellow-600': dnsStatus === 'invalid',
                    }">
                        {{ dnsMessage }}
                    </div>
                    <div v-if="form.errors.url" class="mt-1 text-xs text-red-600">{{ form.errors.url }}</div>
                </div>

                <div>
                    <label for="interval" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Interval Pengecekan (menit)</label>
                    <div class="mt-1 flex items-center">
                        <button type="button" @click="decrementInterval" class="flex h-10 w-10 items-center justify-center rounded-l-md border border-gray-300 bg-gray-50 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800">
                            <Icon name="minus" size="16" />
                        </button>
                        <input
                            id="interval"
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

                <DialogFooter class="mt-6">
                    <Button type="button" variant="outline" @click="close">Batal</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : 'Tambah Monitor' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
