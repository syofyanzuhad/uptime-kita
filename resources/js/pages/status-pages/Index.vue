<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import Icon from '@/components/Icon.vue';
import Button from '@/components/ui/button/Button.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    PaginationEllipsis,
    PaginationFirst,
    PaginationLast,
    PaginationList,
    PaginationListItem,
    PaginationNext,
    PaginationPrev,
    PaginationRoot,
} from 'reka-ui';

interface StatusPage {
    id: number;
    title: string;
    description: string;
    icon: string;
    path: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    statusPages: {
        data: StatusPage[];
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            from: number;
            to: number;
            links: any[];
        };
        links: any;
    };
}

const props = defineProps<Props>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

const deleteStatusPage = (statusPage: StatusPage) => {
    if (confirm(`Are you sure you want to delete "${statusPage.title}"?`)) {
        router.delete(route('status-pages.destroy', statusPage.id));
    }
};

const goToPage = (page: number) => {
    router.visit(route('status-pages.index', { page }), {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <Head title="Status Pages" />

    <AppLayout>
        <template #header>
            <Heading title="Status Pages" />
        </template>

        <div class="space-y-6 p-4">
            <!-- Header with Create Button -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Your Status Pages</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Manage and monitor your service status pages</p>
                </div>
                <Button as-child>
                    <Link :href="route('status-pages.create')" class="flex items-center">
                        <Icon name="plus" class="mr-2 h-4 w-4" />
                        Create Status Page
                    </Link>
                </Button>
            </div>

            <!-- Status Pages Grid -->
            <div v-if="props.statusPages.data.length > 0">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="statusPage in props.statusPages.data" :key="statusPage.id" class="transition-shadow hover:shadow-lg">
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100">
                                        <Icon :name="statusPage.icon" class="h-5 w-5 text-blue-600" />
                                    </div>
                                    <div>
                                        <CardTitle class="text-lg">{{ statusPage.title }}</CardTitle>
                                        <p class="text-sm text-gray-500">{{ statusPage.path }}</p>
                                    </div>
                                </div>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="sm">
                                            <Icon name="moreHorizontal" class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem as-child>
                                            <a
                                                :href="route('status-page.public', statusPage.path)"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="flex cursor-pointer items-center hover:bg-gray-100/30"
                                            >
                                                <Icon name="externalLink" class="mr-2 h-4 w-4" />
                                                View Public
                                            </a>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem as-child>
                                            <Link
                                                :href="route('status-pages.show', statusPage.id)"
                                                class="flex cursor-pointer items-center hover:bg-gray-100/30"
                                            >
                                                <Icon name="eye" class="mr-2 h-4 w-4" />
                                                View Details
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem as-child>
                                            <Link
                                                :href="route('status-pages.edit', statusPage.id)"
                                                class="flex cursor-pointer items-center hover:bg-gray-100/30"
                                            >
                                                <Icon name="edit" class="mr-2 h-4 w-4" />
                                                Edit
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            @click="deleteStatusPage(statusPage)"
                                            class="flex cursor-pointer items-center text-red-600 hover:bg-red-600/10"
                                        >
                                            <Icon name="trash" class="mr-2 h-4 w-4 text-red-600" />
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <p class="mb-4 text-sm text-gray-600">{{ statusPage.description }}</p>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Created {{ formatDate(statusPage.created_at) }}</span>
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="route('status-pages.show', statusPage.id)"> Manage </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                <!-- Pagination Controls -->
                <div v-if="props.statusPages.meta && props.statusPages.meta.last_page > 1" class="mt-6 flex justify-center">
                    <PaginationRoot
                        :total="props.statusPages.meta.total"
                        :items-per-page="props.statusPages.meta.per_page"
                        :default-page="props.statusPages.meta.current_page"
                        @update:page="goToPage"
                    >
                        <PaginationList v-slot="{ items }" class="flex items-center gap-1">
                            <PaginationFirst
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg bg-transparent text-gray-500 transition hover:bg-white disabled:opacity-50 dark:text-gray-400 dark:hover:bg-stone-700/70"
                            >
                                <Icon name="chevronsLeft" class="text-gray-500 dark:text-gray-400" />
                            </PaginationFirst>
                            <PaginationPrev
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg bg-transparent text-gray-500 transition hover:bg-white disabled:opacity-50 dark:text-gray-400 dark:hover:bg-stone-700/70"
                            >
                                <Icon name="chevronLeft" class="text-gray-500 dark:text-gray-400" />
                            </PaginationPrev>
                            <template v-for="(item, index) in items" :key="index">
                                <PaginationListItem
                                    v-if="item.type === 'page'"
                                    :value="item.value"
                                    :is-active="item.value === props.statusPages.meta.current_page"
                                    class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg bg-transparent text-gray-500 transition hover:bg-white disabled:opacity-50 dark:text-gray-400 dark:hover:bg-stone-700/70"
                                >
                                    {{ item.value }}
                                </PaginationListItem>
                                <PaginationEllipsis v-else :index="index">…</PaginationEllipsis>
                            </template>
                            <PaginationNext
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg bg-transparent text-gray-500 transition hover:bg-white disabled:opacity-50 dark:text-gray-400 dark:hover:bg-stone-700/70"
                            >
                                <Icon name="chevronRight" class="text-gray-500 dark:text-gray-400" />
                            </PaginationNext>
                            <PaginationLast
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg bg-transparent text-gray-500 transition hover:bg-white disabled:opacity-50 dark:text-gray-400 dark:hover:bg-stone-700/70"
                            >
                                <Icon name="chevronsRight" class="text-gray-500 dark:text-gray-400" />
                            </PaginationLast>
                        </PaginationList>
                    </PaginationRoot>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="py-12 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                    <Icon name="globe" class="h-8 w-8 text-gray-400 dark:text-gray-400" />
                </div>
                <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-gray-100">No status pages yet</h3>
                <p class="mb-6 text-gray-600 dark:text-gray-400">Create your first status page to share service updates with your users.</p>
                <Button as-child>
                    <Link :href="route('status-pages.create')" class="flex items-center">
                        <Icon name="plus" class="mr-2 h-4 w-4" />
                        Create Status Page
                    </Link>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
