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

import type { Monitor } from '@/types/monitor';

const props = defineProps<{
  monitor: Monitor; // Menerima data monitor yang akan diedit
}>();

// Inisialisasi form dengan data monitor yang ada
const form = useForm({
  url: props.monitor.url,
  uptime_check_enabled: props.monitor.uptime_check_enabled,
  certificate_check_enabled: props.monitor.certificate_check_enabled,
});
// console.log(form.url);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Uptime Monitor',
        href: '/monitor',
    },
    {
        title: `Edit: ${props.monitor.url}`,
        href: `/monitor/${props.monitor.id}/edit`,
    },
];

const submit = () => {
  form.put(route('monitor.update', props.monitor.id), {
    onFinish: () => {},
  });
};
</script>

<template>
  <Head :title="`Edit Monitor: ${form.url}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Monitor: {{ form.url }}</h2>
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
                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full text-gray-700"
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
                Perbarui Monitor
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
