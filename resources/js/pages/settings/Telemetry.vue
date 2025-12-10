<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface TelemetrySettings {
    enabled: boolean;
    endpoint: string;
    frequency: string;
    instance_id: string;
    install_date: string;
    last_ping: string | null;
    debug: boolean;
}

interface Props {
    settings: TelemetrySettings;
    previewData: Record<string, unknown>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Telemetry', href: '/settings/telemetry' },
];

const showPreview = ref(false);
const sendingPing = ref(false);
const regenerating = ref(false);

const previewJson = computed(() => {
    return JSON.stringify(props.previewData, null, 2);
});

function getBadgeClass(variant: 'default' | 'success' | 'secondary' | 'destructive'): string {
    const base = 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium';
    switch (variant) {
        case 'success':
            return `${base} bg-green-500 text-white`;
        case 'destructive':
            return `${base} bg-red-500 text-white`;
        case 'secondary':
            return `${base} bg-gray-200 dark:bg-gray-700`;
        default:
            return `${base} bg-primary text-primary-foreground`;
    }
}

async function sendTestPing() {
    sendingPing.value = true;
    try {
        const response = await fetch('/settings/telemetry/test-ping', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
        const data = await response.json();
        alert(data.message);
    } catch {
        alert('Failed to send test ping');
    } finally {
        sendingPing.value = false;
    }
}

async function regenerateId() {
    if (!confirm('Are you sure? This will generate a new anonymous instance ID.')) {
        return;
    }
    regenerating.value = true;
    try {
        const response = await fetch('/settings/telemetry/regenerate-id', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
        const data = await response.json();
        alert(data.message);
        router.reload();
    } catch {
        alert('Failed to regenerate instance ID');
    } finally {
        regenerating.value = false;
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Telemetry Settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    title="Anonymous Telemetry"
                    description="Help improve Uptime-Kita by sharing anonymous usage statistics"
                />

                <!-- Privacy Notice -->
                <Card class="border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950">
                    <CardContent class="pt-4">
                        <div class="flex gap-3">
                            <Icon name="Shield" class="h-5 w-5 shrink-0 mt-0.5 text-blue-600 dark:text-blue-400" />
                            <div class="text-sm text-blue-800 dark:text-blue-200">
                                <p class="mb-1 font-medium">Privacy First</p>
                                <p>
                                    We only collect anonymous, aggregate statistics. No URLs, email addresses, IP
                                    addresses, or any personally identifiable information is ever transmitted.
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Status Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm font-medium">
                            <Icon name="Radio" class="h-4 w-4" />
                            Telemetry Status
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Status</span>
                                <span :class="getBadgeClass(settings.enabled ? 'success' : 'secondary')">
                                    {{ settings.enabled ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Frequency</span>
                                <span class="capitalize">{{ settings.frequency }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Last Ping</span>
                                <span>{{ settings.last_ping || 'Never' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Install Date</span>
                                <span>{{ settings.install_date }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Instance ID</span>
                                <code class="bg-muted max-w-[200px] truncate rounded px-2 py-1 text-xs">
                                    {{ settings.instance_id.substring(0, 16) }}...
                                </code>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Debug Mode</span>
                                <span :class="getBadgeClass(settings.debug ? 'destructive' : 'secondary')">
                                    {{ settings.debug ? 'ON' : 'OFF' }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Configuration Guide -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm font-medium">
                            <Icon name="Settings" class="h-4 w-4" />
                            Configuration
                        </CardTitle>
                        <CardDescription> Configure telemetry via environment variables </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <pre class="bg-muted overflow-x-auto rounded p-4 text-xs"><code># Enable telemetry (opt-in)
TELEMETRY_ENABLED=true

# Custom endpoint (optional)
TELEMETRY_ENDPOINT={{ settings.endpoint }}

# Frequency: hourly, daily, weekly
TELEMETRY_FREQUENCY={{ settings.frequency }}

# Debug mode (logs data instead of sending)
TELEMETRY_DEBUG=false</code></pre>
                    </CardContent>
                </Card>

                <!-- Data Preview -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2 text-sm font-medium">
                                    <Icon name="Eye" class="h-4 w-4" />
                                    Data Preview
                                </CardTitle>
                                <CardDescription> See exactly what data would be sent </CardDescription>
                            </div>
                            <Button variant="outline" size="sm" @click="showPreview = !showPreview">
                                {{ showPreview ? 'Hide' : 'Show' }} Data
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent v-if="showPreview">
                        <pre class="bg-muted max-h-96 overflow-x-auto rounded p-4 text-xs"><code>{{ previewJson }}</code></pre>
                    </CardContent>
                </Card>

                <!-- Actions -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm font-medium">
                            <Icon name="Zap" class="h-4 w-4" />
                            Actions
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-wrap gap-3">
                            <Button
                                variant="outline"
                                @click="sendTestPing"
                                :disabled="!settings.enabled || sendingPing"
                            >
                                <Icon name="Send" class="mr-2 h-4 w-4" />
                                {{ sendingPing ? 'Sending...' : 'Send Test Ping' }}
                            </Button>
                            <Button variant="outline" @click="regenerateId" :disabled="regenerating">
                                <Icon name="RefreshCw" class="mr-2 h-4 w-4" />
                                {{ regenerating ? 'Regenerating...' : 'Regenerate Instance ID' }}
                            </Button>
                        </div>
                        <p class="text-muted-foreground mt-3 text-xs">
                            Note: Test ping only works when telemetry is enabled in your .env file.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
