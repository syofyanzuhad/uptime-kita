<template>
    <Head title="Edit Status Page" />

    <AppLayout>
        <template #header>
            <Heading title="Edit Status Page" />
        </template>

        <div class="mx-auto max-w-2xl">
            <Card>
                <CardHeader>
                    <CardTitle>Edit Status Page</CardTitle>
                    <CardDescription> Update your status page information and settings. </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <Label for="title">Title</Label>
                            <Input id="title" v-model="form.title" type="text" placeholder="My Service Status" required />
                            <InputError :message="form.errors.title" />
                        </div>

                        <div class="space-y-2">
                            <Label for="description">Description</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                placeholder="Brief description of your service"
                                required
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="space-y-2">
                            <Label for="icon">Icon</Label>
                            <Input id="icon" v-model="form.icon" type="text" placeholder="globe" required />
                            <p class="text-sm text-gray-500">Use icon names from Lucide React (e.g., globe, server, database)</p>
                            <InputError :message="form.errors.icon" />
                        </div>

                        <div class="space-y-2">
                            <Label for="path">URL Path</Label>
                            <Input id="path" v-model="form.path" type="text" placeholder="my-service" required />
                            <p class="text-sm text-gray-500">Your status page is available at /status/{{ form.path }}</p>
                            <InputError :message="form.errors.path" />
                        </div>

                        <div class="flex justify-end space-x-3">
                            <Button type="button" variant="outline" @click="router.visit(route('status-pages.show', statusPage.id))"> Cancel </Button>
                            <Button type="submit" :disabled="form.processing">
                                <Icon v-if="form.processing" name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                                Update Status Page
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Custom Domain Section -->
            <Card class="mt-6">
                <CardHeader>
                    <CardTitle>Custom Domain</CardTitle>
                    <CardDescription> Use your own domain for this status page </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitDomain" class="space-y-6">
                        <div class="space-y-2">
                            <Label for="custom_domain">Custom Domain</Label>
                            <Input id="custom_domain" v-model="domainForm.custom_domain" type="text" placeholder="status.example.com" />
                            <p class="text-sm text-gray-500">Enter your custom domain without http:// or https://</p>
                            <InputError :message="domainForm.errors.custom_domain" />
                        </div>

                        <div v-if="statusPage.custom_domain" class="space-y-4">
                            <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-sm font-medium">Current Domain</span>
                                    <span
                                        v-if="statusPage.custom_domain_verified"
                                        class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-300"
                                    >
                                        <Icon name="check-circle" class="mr-1 h-3 w-3" />
                                        Verified
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300"
                                    >
                                        <Icon name="alert-circle" class="mr-1 h-3 w-3" />
                                        Pending Verification
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ statusPage.custom_domain }}</p>
                            </div>

                            <div v-if="!statusPage.custom_domain_verified && dnsInstructions" class="space-y-4">
                                <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                                    <h4 class="mb-2 text-sm font-medium text-blue-900 dark:text-blue-300">DNS Configuration Required</h4>
                                    <p class="mb-3 text-sm text-blue-700 dark:text-blue-400">
                                        Add these DNS records to verify and connect your domain:
                                    </p>

                                    <div class="space-y-3">
                                        <div
                                            v-for="(record, index) in dnsInstructions.dns_records"
                                            :key="index"
                                            class="rounded border border-blue-200 bg-white p-3 dark:border-blue-800 dark:bg-gray-800"
                                        >
                                            <div class="grid grid-cols-2 gap-2 text-sm">
                                                <div>
                                                    <span class="text-gray-500 dark:text-gray-400">Type:</span>
                                                    <span class="ml-2 font-mono font-medium">{{ record.type }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500 dark:text-gray-400">TTL:</span>
                                                    <span class="ml-2 font-mono">{{ record.ttl }}</span>
                                                </div>
                                                <div class="col-span-2">
                                                    <span class="text-gray-500 dark:text-gray-400">Name:</span>
                                                    <span class="ml-2 font-mono text-xs break-all">{{ record.name }}</span>
                                                </div>
                                                <div class="col-span-2">
                                                    <span class="text-gray-500 dark:text-gray-400">Value:</span>
                                                    <span class="ml-2 font-mono text-xs break-all">{{ record.value }}</span>
                                                </div>
                                                <div v-if="record.note" class="col-span-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ record.note }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <Button type="button" @click="verifyDomain" :disabled="verifying">
                                        <Icon v-if="verifying" name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                                        <Icon v-else name="shield-check" class="mr-2 h-4 w-4" />
                                        Verify Domain
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2">
                            <input
                                id="force_https"
                                v-model="domainForm.force_https"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <Label for="force_https">Force HTTPS</Label>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <Button v-if="statusPage.custom_domain" type="button" variant="destructive" @click="removeDomain"> Remove Domain </Button>
                            <Button type="submit" :disabled="domainForm.processing">
                                <Icon v-if="domainForm.processing" name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                                {{ statusPage.custom_domain ? 'Update Domain' : 'Add Domain' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import Button from '@/components/ui/button/Button.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface DnsRecord {
    type: string;
    name: string;
    value: string;
    ttl: number;
    note?: string;
}

interface DnsInstructions {
    domain: string;
    verification_token: string;
    dns_records: DnsRecord[];
}

interface StatusPage {
    id: number;
    title: string;
    description: string;
    icon: string;
    path: string;
    custom_domain?: string;
    custom_domain_verified?: boolean;
    force_https?: boolean;
}

interface Props {
    statusPage: StatusPage;
}

const props = defineProps<Props>();

const form = useForm({
    title: props.statusPage.title,
    description: props.statusPage.description,
    icon: props.statusPage.icon,
    path: props.statusPage.path,
});

const domainForm = useForm({
    custom_domain: props.statusPage.custom_domain || '',
    force_https: props.statusPage.force_https ?? true,
});

const dnsInstructions = ref<DnsInstructions | null>(null);
const verifying = ref(false);

const submit = () => {
    form.put(route('status-pages.update', props.statusPage.id));
};

const submitDomain = () => {
    domainForm.post(route('status-pages.custom-domain.update', props.statusPage.id), {
        onSuccess: () => {
            if (domainForm.custom_domain) {
                fetchDnsInstructions();
            } else {
                dnsInstructions.value = null;
            }
        },
    });
};

const removeDomain = () => {
    if (confirm('Are you sure you want to remove the custom domain?')) {
        domainForm.custom_domain = '';
        domainForm.post(route('status-pages.custom-domain.update', props.statusPage.id), {
            onSuccess: () => {
                dnsInstructions.value = null;
            },
        });
    }
};

const verifyDomain = async () => {
    verifying.value = true;
    router.post(
        route('status-pages.custom-domain.verify', props.statusPage.id),
        {},
        {
            onFinish: () => {
                verifying.value = false;
            },
        },
    );
};

const fetchDnsInstructions = async () => {
    if (!props.statusPage.custom_domain) return;

    try {
        const response = await fetch(route('status-pages.custom-domain.dns', props.statusPage.id));
        if (response.ok) {
            dnsInstructions.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to fetch DNS instructions:', error);
    }
};

onMounted(() => {
    if (props.statusPage.custom_domain && !props.statusPage.custom_domain_verified) {
        fetchDnsInstructions();
    }
});
</script>
