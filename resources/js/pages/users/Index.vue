<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData, User } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    users: any; // users is a paginator
    search?: string;
    perPage?: number;
}>();
const page = usePage<SharedData>();
const flash = page.props.flash?.success;

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: '/users' }];

const search = ref(props.search || '');
const perPage = ref((props.perPage as number) || 15);

function submitSearch() {
    router.get(
        route('users.index'),
        {
            search: search.value,
            per_page: perPage.value,
        },
        { preserveState: true, only: ['users', 'search', 'perPage'] },
    );
}

function clearSearch() {
    search.value = '';
    submitSearch();
}

function onPerPageChange() {
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
            onSuccess: () => closeDeleteModal(),
            onFinish: () => closeDeleteModal(),
        });
    }
};
</script>

<template>
    <Head title="Users" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl leading-tight font-semibold text-gray-800 dark:text-gray-200">Users</h2>
                <Link
                    :href="route('users.create')"
                    class="inline-flex cursor-pointer items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none active:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:bg-gray-600 dark:active:bg-gray-800"
                >
                    Create User
                </Link>
            </div>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="flash" class="mb-4 rounded bg-green-100 p-4 text-green-800">{{ flash }}</div>
                <div class="overflow-auto bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <!-- Search Bar & Filter -->
                    <form @submit.prevent="submitSearch" class="mb-4 flex items-center gap-2 overflow-auto">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search users (min 3 characters)..."
                            class="w-full max-w-xs min-w-52 rounded border border-gray-300 px-3 py-2 focus:border-blue-400 focus:ring focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        />
                        <select
                            v-model.number="perPage"
                            @change="onPerPageChange"
                            class="rounded border border-gray-300 px-2 py-2 focus:border-blue-400 focus:ring focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        >
                            <option :value="5">5 / page</option>
                            <option :value="10">10 / page</option>
                            <option :value="15">15 / page</option>
                            <option :value="20">20 / page</option>
                            <option :value="50">50 / page</option>
                            <option :value="100">100 / page</option>
                        </select>
                        <button
                            v-if="search"
                            type="button"
                            @click="clearSearch"
                            class="ml-1 rounded bg-gray-200 px-2 py-1 text-xs text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                        >
                            Clear
                        </button>
                        <button type="submit" class="rounded bg-blue-500 px-3 py-2 text-white hover:bg-blue-600">Search</button>
                    </form>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>ID</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Monitors</TableHead>
                                <TableHead>Status Pages</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="user in props.users.data" :key="user.id">
                                <TableCell>{{ user.id }}</TableCell>
                                <TableCell>{{ user.name }}</TableCell>
                                <TableCell>{{ user.email }}</TableCell>
                                <TableCell>{{ user.monitors_count }}</TableCell>
                                <TableCell>{{ user.status_pages_count }}</TableCell>
                                <TableCell class="flex gap-2">
                                    <Link :href="route('users.show', user.id)" class="text-blue-600 hover:underline">View</Link>
                                    <Link :href="route('users.edit', user.id)" class="text-yellow-600 hover:underline">Edit</Link>
                                    <button @click="openDeleteModal(user)" class="text-red-600 hover:underline">Delete</button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <!-- Pagination -->
                    <div class="mt-6">
                        <Pagination :data="props.users" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Delete Modal -->
        <div v-if="isDeleteModalOpen" class="bg-opacity-30 fixed inset-0 z-50 flex items-center justify-center bg-black">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Delete User</h3>
                <p>
                    Are you sure you want to delete user <strong>{{ userToDelete?.name }}</strong
                    >?
                </p>
                <div class="mt-6 flex justify-end gap-2">
                    <button @click="closeDeleteModal" class="rounded bg-gray-300 px-4 py-2 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500">
                        Cancel
                    </button>
                    <button @click="confirmDeleteUser" class="rounded bg-red-600 px-4 py-2 text-white hover:bg-red-700">Delete</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
