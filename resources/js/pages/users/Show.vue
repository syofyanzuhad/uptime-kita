<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, User } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{ user: User }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
    { title: `User: ${props.user.name}`, href: `/users/${props.user.id}` },
];
</script>

<template>
    <Head :title="`User: ${props.user.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-gray-800 dark:text-gray-200">User: {{ props.user.name }}</h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="mb-4"><strong>Name:</strong> {{ props.user.name }}</div>
                    <div class="mb-4"><strong>Email:</strong> {{ props.user.email }}</div>
                    <div class="mb-4"><strong>Created At:</strong> {{ props.user.created_at }}</div>
                    <div class="mb-4"><strong>Updated At:</strong> {{ props.user.updated_at }}</div>
                    <div class="mt-4 flex items-center justify-end">
                        <Link
                            :href="route('users.index')"
                            class="inline-flex cursor-pointer items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none active:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:bg-gray-600 dark:active:bg-gray-800"
                        >
                            Back to Users
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
