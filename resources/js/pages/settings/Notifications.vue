<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import NotificationChannelList from '@/components/NotificationChannelList.vue';

const props = defineProps<{
    channels: any[];
    showForm?: boolean;
    isEdit?: boolean;
    editingChannel?: any;
}>();

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
