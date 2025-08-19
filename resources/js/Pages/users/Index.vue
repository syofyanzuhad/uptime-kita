<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import type { BreadcrumbItem, User, SharedData } from '@/types';
import { ref } from 'vue';
import Pagination from '@/components/Pagination.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const props = defineProps<{ users: any }>(); // users is a paginator
const page = usePage<SharedData>();
const flash = page.props.flash?.success;

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Users', href: '/users' },
];

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
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Users</h2>
        <Link :href="route('users.create')"
          class="inline-flex cursor-pointer items-center px-4 py-2 bg-gray-800 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-gray-600 focus:bg-gray-700 dark:focus:bg-gray-600 active:bg-gray-900 dark:active:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
          Create User
        </Link>
      </div>
    </template>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div v-if="flash" class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ flash }}</div>
        <div class="bg-white dark:bg-gray-800 overflow-auto shadow-sm sm:rounded-lg p-6">
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
    <div v-if="isDeleteModalOpen" class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-30">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Delete User</h3>
        <p>Are you sure you want to delete user <strong>{{ userToDelete?.name }}</strong>?</p>
        <div class="flex justify-end gap-2 mt-6">
          <button @click="closeDeleteModal" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 rounded hover:bg-gray-400 dark:hover:bg-gray-500">Cancel</button>
          <button @click="confirmDeleteUser" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
