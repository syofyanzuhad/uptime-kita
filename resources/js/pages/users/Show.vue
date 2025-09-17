<script setup lang="ts">
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, User } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{ user: User }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
    { title: `User: ${props.user.name}`, href: `/users/${props.user.id}` },
];

const getStatusColor = (status: string) => {
    switch (status) {
        case 'up':
            return 'text-green-600 dark:text-green-400';
        case 'down':
            return 'text-red-600 dark:text-red-400';
        default:
            return 'text-gray-600 dark:text-gray-400';
    }
};

const getStatusBadge = (isEnabled: boolean) => {
    return isEnabled
        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
};
</script>

<template>
    <Head :title="`User: ${props.user.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl leading-tight font-semibold text-gray-800 dark:text-gray-200">User: {{ props.user.name }}</h2>
                <Link
                    :href="route('users.index')"
                    class="inline-flex cursor-pointer items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none active:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:bg-gray-600 dark:active:bg-gray-800"
                >
                    Back to Users
                </Link>
            </div>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- User Information -->
                <div class="mb-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">User Information</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div><strong class="text-gray-700 dark:text-gray-300">Name:</strong> {{ props.user.name }}</div>
                        <div><strong class="text-gray-700 dark:text-gray-300">Email:</strong> {{ props.user.email }}</div>
                        <div><strong class="text-gray-700 dark:text-gray-300">Created:</strong> {{ new Date(props.user.created_at).toLocaleDateString() }}</div>
                        <div><strong class="text-gray-700 dark:text-gray-300">Updated:</strong> {{ new Date(props.user.updated_at).toLocaleDateString() }}</div>
                    </div>
                </div>

                <!-- Monitors -->
                <div class="mb-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Monitors ({{ props.user.monitors?.length || 0 }})
                    </h3>
                    <div v-if="props.user.monitors && props.user.monitors.length > 0" class="overflow-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>URL</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Active</TableHead>
                                    <TableHead>Pinned</TableHead>
                                    <TableHead>Subscribed Since</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="monitor in props.user.monitors" :key="monitor.id">
                                    <TableCell class="font-medium">{{ monitor.display_name || monitor.url }}</TableCell>
                                    <TableCell>
                                        <a :href="monitor.url" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">
                                            {{ monitor.url }}
                                        </a>
                                    </TableCell>
                                    <TableCell>
                                        <span :class="getStatusColor(monitor.uptime_status)" class="font-medium capitalize">
                                            {{ monitor.uptime_status || 'pending' }}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <span class="rounded-full px-2 py-1 text-xs font-medium" :class="getStatusBadge(monitor.pivot.is_active)">
                                            {{ monitor.pivot.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="monitor.pivot.is_pinned" class="text-yellow-600 dark:text-yellow-400">
                                            📌 Pinned
                                        </span>
                                        <span v-else class="text-gray-500">-</span>
                                    </TableCell>
                                    <TableCell>{{ new Date(monitor.pivot.created_at).toLocaleDateString() }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                        No monitors subscribed
                    </div>
                </div>

                <!-- Status Pages -->
                <div class="mb-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Status Pages ({{ props.user.status_pages?.length || 0 }})
                    </h3>
                    <div v-if="props.user.status_pages && props.user.status_pages.length > 0" class="overflow-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Title</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead>Path</TableHead>
                                    <TableHead>Created</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="statusPage in props.user.status_pages" :key="statusPage.id">
                                    <TableCell class="font-medium">{{ statusPage.title }}</TableCell>
                                    <TableCell>{{ statusPage.description || '-' }}</TableCell>
                                    <TableCell>
                                        <code class="rounded bg-gray-100 px-2 py-1 text-sm dark:bg-gray-700">
                                            {{ statusPage.path }}
                                        </code>
                                    </TableCell>
                                    <TableCell>{{ new Date(statusPage.created_at).toLocaleDateString() }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                        No status pages created
                    </div>
                </div>

                <!-- Notification Channels -->
                <div class="mb-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Notification Channels ({{ props.user.notification_channels?.length || 0 }})
                    </h3>
                    <div v-if="props.user.notification_channels && props.user.notification_channels.length > 0" class="overflow-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Destination</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Created</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="channel in props.user.notification_channels" :key="channel.id">
                                    <TableCell>
                                        <span class="rounded bg-blue-100 px-2 py-1 text-xs font-medium uppercase text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ channel.type }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="font-mono text-sm">{{ channel.destination }}</TableCell>
                                    <TableCell>
                                        <span class="rounded-full px-2 py-1 text-xs font-medium" :class="getStatusBadge(channel.is_enabled)">
                                            {{ channel.is_enabled ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </TableCell>
                                    <TableCell>{{ new Date(channel.created_at).toLocaleDateString() }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                        No notification channels configured
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
