<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Users', href: '/users' },
  { title: 'Create User', href: '/users/create' },
];

const submit = () => {
  form.post(route('users.store'));
};
</script>

<template>
  <Head title="Create User" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Create User</h2>
    </template>
    <div class="py-12">
      <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
          <form @submit.prevent="submit">
            <div class="mb-4">
              <label for="name" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Name</label>
              <input id="name" type="text" v-model="form.name" required autofocus autocomplete="name"
                class="flex h-10 w-full rounded-md border border-input bg-background dark:bg-gray-700 px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-200" />
              <div v-if="form.errors.name" class="text-sm text-red-600 dark:text-red-400 mt-2">{{ form.errors.name }}</div>
            </div>
            <div class="mb-4">
              <label for="email" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Email</label>
              <input id="email" type="email" v-model="form.email" required autocomplete="email"
                class="flex h-10 w-full rounded-md border border-input bg-background dark:bg-gray-700 px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-200" />
              <div v-if="form.errors.email" class="text-sm text-red-600 dark:text-red-400 mt-2">{{ form.errors.email }}</div>
            </div>
            <div class="mb-4">
              <label for="password" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Password</label>
              <input id="password" type="password" v-model="form.password" required autocomplete="new-password"
                class="flex h-10 w-full rounded-md border border-input bg-background dark:bg-gray-700 px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-200" />
              <div v-if="form.errors.password" class="text-sm text-red-600 dark:text-red-400 mt-2">{{ form.errors.password }}</div>
            </div>
            <div class="mb-4">
              <label for="password_confirmation" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Confirm Password</label>
              <input id="password_confirmation" type="password" v-model="form.password_confirmation" required autocomplete="new-password"
                class="flex h-10 w-full rounded-md border border-input bg-background dark:bg-gray-700 px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-200" />
            </div>
            <div class="flex items-center justify-end mt-4">
              <button type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing"
                class="inline-flex cursor-pointer items-center px-4 py-2 bg-gray-800 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-gray-600 focus:bg-gray-700 dark:focus:bg-gray-600 active:bg-gray-900 dark:active:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Create User
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
