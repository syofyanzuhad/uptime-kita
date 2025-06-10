<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

// Hapus impor komponen yang tidak ada
// import TextInput from '@/Components/TextInput.vue';
// import InputLabel from '@/Components/InputLabel.vue';
// import PrimaryButton from '@/Components/PrimaryButton.vue';
// import InputError from '@/Components/InputError.vue';
// import Checkbox from '@/Components/Checkbox.vue';

const form = useForm({
  url: '',
  uptime_check_enabled: true,
  certificate_check_enabled: true,
  uptime_check_interval: 5,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Uptime Monitor',
        href: '/monitor',
    },
    {
        title: 'Tambah Monitor',
        href: '/monitor/create',
    },
];

const decrementInterval = () => {
  if (form.uptime_check_interval > 1) {
    form.uptime_check_interval--;
  }
};

const incrementInterval = () => {
  if (form.uptime_check_interval < 60) {
    form.uptime_check_interval++;
  }
};

const submit = () => {
  form.post(route('monitor.store'), {
    onFinish: () => form.reset('url'),
  });
};
</script>

<template>
  <Head title="Tambah Monitor Baru" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Tambah Monitor Baru</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
          <form @submit.prevent="submit">
            <div class="mb-4">
              <label for="url" class="block font-medium text-sm text-gray-700 dark:text-gray-300">URL Monitor</label>
              <input
                id="url"
                type="url"
                class="flex h-10 w-full rounded-md border border-input bg-background dark:bg-gray-700 px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-foreground dark:file:text-gray-200 file:text-sm file:font-medium placeholder:text-muted-foreground dark:placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-200"
                v-model="form.url"
                required
                autofocus
                autocomplete="url"
              />
              <div v-if="form.errors.url" class="text-sm text-red-600 dark:text-red-400 mt-2">{{ form.errors.url }}</div>
            </div>

            <div class="mb-4">
              <label for="uptime_check_interval" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Interval Pengecekan (menit)</label>
              <div class="flex items-center mt-1">
                <button
                  type="button"
                  @click="decrementInterval"
                  class="inline-flex items-center justify-center h-10 w-10 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                  <span class="sr-only">Kurangi</span>
                  <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                  </svg>
                </button>
                <input
                  id="uptime_check_interval"
                  type="number"
                  min="1"
                  max="60"
                  v-model="form.uptime_check_interval"
                  class="flex h-10 w-20 text-center border-t border-b border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
                <button
                  type="button"
                  @click="incrementInterval"
                  class="inline-flex items-center justify-center h-10 w-10 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                  <span class="sr-only">Tambah</span>
                  <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                  </svg>
                </button>
              </div>
              <div v-if="form.errors.uptime_check_interval" class="text-sm text-red-600 dark:text-red-400 mt-2">{{ form.errors.uptime_check_interval }}</div>
            </div>

            <div class="mb-4">
              <label class="flex items-center">
                <input type="checkbox" name="uptime_check_enabled" v-model="form.uptime_check_enabled" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 dark:text-indigo-400 shadow-sm focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Aktifkan Pengecekan Uptime</span>
              </label>
            </div>

            <div class="mb-4">
              <label class="flex items-center">
                <input type="checkbox" name="certificate_check_enabled" v-model="form.certificate_check_enabled" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 dark:text-indigo-400 shadow-sm focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Aktifkan Pengecekan Sertifikat SSL</span>
              </label>
            </div>

            <div class="flex items-center justify-end mt-4">
              <button type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="inline-flex cursor-pointer items-center px-4 py-2 bg-gray-800 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-gray-600 focus:bg-gray-700 dark:focus:bg-gray-600 active:bg-gray-900 dark:active:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Tambah Monitor
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
