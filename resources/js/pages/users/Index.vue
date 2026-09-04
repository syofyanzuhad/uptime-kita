<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input, Select } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, User } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle, Eye, Pencil, Plus, Search, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    users: any; // users is a paginator
    search?: string;
    perPage?: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: '/users' }];

const searchQuery = ref(props.search || '');
const selectedPerPage = ref(String(props.perPage || 15));

const perPageOptions = [
    { label: '5 / page', value: '5' },
    { label: '10 / page', value: '10' },
    { label: '15 / page', value: '15' },
    { label: '20 / page', value: '20' },
    { label: '50 / page', value: '50' },
    { label: '100 / page', value: '100' },
];

function submitSearch() {
    router.get(
        route('users.index'),
        {
            search: searchQuery.value || undefined,
            per_page: selectedPerPage.value || undefined,
        },
        { preserveState: true, only: ['users', 'search', 'perPage'] },
    );
}

function clearSearch() {
    searchQuery.value = '';
    submitSearch();
}

const isDeleteModalOpen = ref(false);
const userToDelete = ref<User | null>(null);

const openDeleteModal = (user: User) => {
    userToDelete.value = user;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    userToDelete.value = null;
};

const confirmDeleteUser = () => {
    if (userToDelete.value) {
        router.delete(route('users.destroy', userToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => closeDeleteModal(),
            onFinish: () => closeDeleteModal(),
        });
    }
};
</script>

<template>
    <Head title="Users" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-sm dark:border-gray-800 dark:bg-gray-900/80">
                    <!-- Header -->
                    <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl dark:text-white">Users</h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Manage registered users, permissions, and monitor access</p>
                        </div>
                        <div>
                            <Link :href="route('users.create')">
                                <Button
                                    size="sm"
                                    class="h-9 gap-1.5 rounded-xl bg-blue-600 px-4 text-xs font-semibold text-white shadow-md shadow-blue-500/20 transition-all hover:bg-blue-700 hover:shadow-blue-500/30 active:scale-95 sm:text-sm"
                                >
                                    <Plus class="h-4 w-4" />
                                    <span>Create User</span>
                                </Button>
                            </Link>
                        </div>
                    </div>

                    <!-- Search Bar & Filter Toolbar -->
                    <form @submit.prevent="submitSearch" class="mb-5 flex flex-wrap items-center gap-2.5">
                        <div class="relative min-w-[220px] flex-1 max-w-sm">
                            <Search class="pointer-events-none absolute top-1/2 left-3 z-10 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <Input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search users (min 3 characters)..."
                                class="h-9 w-full rounded-md pr-8 pl-9 text-sm"
                            />
                            <button
                                v-if="searchQuery"
                                type="button"
                                @click="clearSearch"
                                class="absolute top-1/2 right-2.5 z-10 -translate-y-1/2 cursor-pointer text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                title="Clear"
                            >
                                <X class="h-3.5 w-3.5" />
                            </button>
                        </div>

                        <Select
                            v-model="selectedPerPage"
                            :items="perPageOptions"
                            @update:model-value="submitSearch"
                            placeholder="Per Page"
                            class="h-9 w-36 shrink-0"
                        />
                        <Button
                            v-if="searchQuery"
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="clearSearch"
                            class="h-9"
                        >
                            Reset
                        </Button>
                        <Button type="submit" size="sm" class="h-9 gap-1.5 px-4">
                            Search
                        </Button>
                    </form>

                    <!-- Empty State or Table -->
                    <div v-if="props.users.data.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                        No users found.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-16">ID</TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead class="text-center">Monitors</TableHead>
                                    <TableHead class="text-center">Status Pages</TableHead>
                                    <TableHead class="text-center">Channels</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="user in props.users.data" :key="user.id" class="group">
                                    <TableCell class="font-mono text-xs text-muted-foreground">#{{ user.id }}</TableCell>
                                    <TableCell class="font-semibold whitespace-nowrap text-foreground">{{ user.name }}</TableCell>
                                    <TableCell class="font-mono text-xs whitespace-nowrap text-muted-foreground">{{ user.email }}</TableCell>
                                    <TableCell class="text-center whitespace-nowrap">
                                        <span class="inline-flex items-center justify-center rounded-md border border-gray-200/80 bg-gray-50 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-300">
                                            {{ user.monitors_count }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-center whitespace-nowrap">
                                        <span class="inline-flex items-center justify-center rounded-md border border-gray-200/80 bg-gray-50 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-300">
                                            {{ user.status_pages_count }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-center whitespace-nowrap">
                                        <span class="inline-flex items-center justify-center rounded-md border border-gray-200/80 bg-gray-50 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-300">
                                            {{ user.notification_channels_count }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-right whitespace-nowrap">
                                        <div class="inline-flex items-center justify-end gap-1">
                                            <Link :href="route('users.show', user.id)">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-8 gap-1 rounded-lg px-2 text-xs font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                                                    title="View user details"
                                                >
                                                    <Eye class="h-3.5 w-3.5" />
                                                    <span class="hidden xl:inline">View</span>
                                                </Button>
                                            </Link>
                                            <Link :href="route('users.edit', user.id)">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-8 gap-1 rounded-lg px-2 text-xs font-medium text-blue-600 hover:bg-blue-50 hover:text-blue-700 dark:text-blue-400 dark:hover:bg-blue-950/40"
                                                    title="Edit user"
                                                >
                                                    <Pencil class="h-3.5 w-3.5" />
                                                    <span class="hidden xl:inline">Edit</span>
                                                </Button>
                                            </Link>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="openDeleteModal(user)"
                                                class="h-8 gap-1 rounded-lg px-2 text-xs font-medium text-rose-600 hover:bg-rose-50 hover:text-rose-700 dark:text-rose-400 dark:hover:bg-rose-950/40"
                                                title="Delete user"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                                <span class="hidden xl:inline">Delete</span>
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        <Pagination :data="props.users" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="isDeleteModalOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Delete User</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this user? This action cannot be undone.<br />
                        <span v-if="userToDelete" class="mt-2 block text-sm font-semibold text-foreground">
                            <AlertTriangle class="mr-1 inline h-4 w-4 text-rose-500" />
                            {{ userToDelete.name }} ({{ userToDelete.email }})
                        </span>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="closeDeleteModal">Cancel</Button>
                    <Button variant="destructive" @click="confirmDeleteUser">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
