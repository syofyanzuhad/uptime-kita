<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PublicMonitorsCard from '../components/PublicMonitorsCard.vue';
import { ref } from 'vue';
import Icon from '@/components/Icon.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const searchQuery = ref('');
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Search Bar -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Icon name="search" class="h-5 w-5 text-gray-400" />
                </div>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari monitor berdasarkan domain atau URL..."
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <div v-if="searchQuery" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <button
                        @click="searchQuery = ''"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <Icon name="x" class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min"> -->
                <PublicMonitorsCard :search-query="searchQuery" />
            <!-- </div> -->
        </div>
    </AppLayout>
</template>
