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
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Uptime Monitor',
        href: '/uptime-monitor',
    },
    {
        title: 'Tambah Monitor',
        href: '/uptime-monitor/create',
    },
];

const submit = () => {
  form.post(route('uptime.store'), {
    onFinish: () => form.reset('url'),
  });
};
</script>

<template>
  <Head title="Tambah Monitor Baru" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Monitor Baru</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <form @submit.prevent="submit">
            <div class="mb-4">
              <label for="url" class="block font-medium text-sm text-gray-700">URL Monitor</label>
              <input
                id="url"
                type="url"
                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full"
                v-model="form.url"
                required
                autofocus
                autocomplete="url"
              />
              <div v-if="form.errors.url" class="text-sm text-red-600 mt-2">{{ form.errors.url }}</div>
            </div>

            <div class="mb-4">
              <label class="flex items-center">
                <input type="checkbox" name="uptime_check_enabled" v-model="form.uptime_check_enabled" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-600">Aktifkan Pengecekan Uptime</span>
              </label>
            </div>

            <div class="mb-4">
              <label class="flex items-center">
                <input type="checkbox" name="certificate_check_enabled" v-model="form.certificate_check_enabled" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-600">Aktifkan Pengecekan Sertifikat SSL</span>
              </label>
            </div>

            <div class="flex items-center justify-end mt-4">
              <button type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Tambah Monitor
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
