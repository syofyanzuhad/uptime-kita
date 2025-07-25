<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import type { BreadcrumbItem, User } from '@/types';

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
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">User: {{ props.user.name }}</h2>
    </template>
    <div class="py-12">
      <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
          <div class="mb-4">
            <strong>Name:</strong> {{ props.user.name }}
          </div>
          <div class="mb-4">
            <strong>Email:</strong> {{ props.user.email }}
          </div>
          <div class="mb-4">
            <strong>Created At:</strong> {{ props.user.created_at }}
          </div>
          <div class="mb-4">
            <strong>Updated At:</strong> {{ props.user.updated_at }}
          </div>
          <div class="flex items-center justify-end mt-4">
            <Link :href="route('users.index')"
              class="inline-flex cursor-pointer items-center px-4 py-2 bg-gray-800 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-gray-600 focus:bg-gray-700 dark:focus:bg-gray-600 active:bg-gray-900 dark:active:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
              Back to Users
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
