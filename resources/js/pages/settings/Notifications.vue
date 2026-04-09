<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useWebNotification } from '@vueuse/core';

import HeadingSmall from '@/components/HeadingSmall.vue';
import Icon from '@/components/Icon.vue';
import NotificationChannelList from '@/components/NotificationChannelList.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { desktopNotificationsEnabled } from '@/composables/useToastNotifications';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    channels: any[];
    showForm?: boolean;
    isEdit?: boolean;
    editingChannel?: any;
}>();

const { isSupported, permission, show } = useWebNotification();

const handleToggleBrowserNotifications = async (checked: boolean) => {
    if (checked) {
        if (permission.value !== 'granted') {
            const result = await Notification.requestPermission();
            if (result !== 'granted') {
                desktopNotificationsEnabled.value = false;
                return;
            }
        }

        // Send a test notification to confirm it's working
        show({
            title: 'Uptime Kita',
            body: 'Browser notifications are now enabled!',
            icon: '/favicon.ico',
        });
    }

    desktopNotificationsEnabled.value = checked;
};

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Notification settings',
        href: '/settings/notifications',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Notification settings" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Notification settings" description="Manage your notification channels (Telegram, Slack, Email, etc.)" />

                <!-- Browser Notifications -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="monitor" class="h-5 w-5" />
                            Browser Notifications
                        </CardTitle>
                        <CardDescription>
                            Receive real-time notifications directly on your desktop when a service status changes.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="!isSupported" class="rounded-md bg-yellow-50 p-4 dark:bg-yellow-900/20">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <Icon name="alertTriangle" class="h-5 w-5 text-yellow-400" />
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Not supported</h3>
                                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                        <p>Your browser does not support desktop notifications.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="flex items-center justify-between">
                            <div class="space-y-0.5">
                                <Label for="browser-notifications">Enable browser notifications</Label>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Status: <span class="font-medium capitalize">{{ permission }}</span>
                                </p>
                            </div>
                            <Switch
                                id="browser-notifications"
                                :model-value="desktopNotificationsEnabled"
                                @update:model-value="handleToggleBrowserNotifications"
                            />
                        </div>
                    </CardContent>
                </Card>

                <hr class="border-gray-200 dark:border-gray-800" />

                <NotificationChannelList
                    :channels="props.channels"
                    :showForm="props.showForm"
                    :isEdit="props.isEdit"
                    :editingChannel="props.editingChannel"
                />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
