<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    statusPagePath: string;
    statusPageTitle: string;
}>();

const isOpen = ref(false);
const successMessage = ref<string | null>(null);

const form = useForm({
    email: '',
});

function openModal() {
    isOpen.value = true;
    successMessage.value = null;
    form.clearErrors();
    form.reset();
}

function closeModal() {
    isOpen.value = false;
    form.reset();
    successMessage.value = null;
}

function submit() {
    form.post(`/status/${props.statusPagePath}/subscribe`, {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = 'Verification email sent! Please check your inbox to confirm subscription.';
            form.reset();
        },
    });
}
</script>

<template>
    <div>
        <button
            @click="openModal"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 shadow-sm transition-all hover:bg-blue-100 active:scale-95 dark:border-blue-900/50 dark:bg-blue-950/40 dark:text-blue-300 dark:hover:bg-blue-900/60"
        >
            <Icon name="bell" class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" />
            <span>Subscribe</span>
        </button>

        <!-- Modal Backdrop -->
        <Teleport to="body">
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm transition-opacity"
                @click.self="closeModal"
            >
                <div
                    class="relative w-full max-w-md overflow-hidden rounded-3xl border border-gray-200 bg-white p-6 shadow-2xl transition-all dark:border-gray-800 dark:bg-gray-900"
                >
                    <!-- Header -->
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 dark:bg-blue-950 dark:text-blue-400"
                            >
                                <Icon name="bell" class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Subscribe to Updates</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Get status alerts for {{ statusPageTitle }}</p>
                            </div>
                        </div>
                        <button
                            @click="closeModal"
                            type="button"
                            class="rounded-xl p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                        >
                            <Icon name="x" class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Success State -->
                    <div
                        v-if="successMessage"
                        class="mt-5 rounded-2xl bg-emerald-50 p-4 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300"
                    >
                        <div class="flex items-start gap-3">
                            <Icon name="checkCircle" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                            <div class="text-xs font-medium">
                                {{ successMessage }}
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button
                                @click="closeModal"
                                type="button"
                                class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500"
                            >
                                Done
                            </button>
                        </div>
                    </div>

                    <!-- Form State -->
                    <form v-else @submit.prevent="submit" class="mt-5 space-y-4">
                        <div>
                            <label for="subscriber-email" class="block text-xs font-semibold text-gray-700 dark:text-gray-300"> Email Address </label>
                            <div class="relative mt-1.5">
                                <input
                                    id="subscriber-email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    placeholder="you@example.com"
                                    class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                                />
                            </div>
                            <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-500">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            We will send a confirmation email. You can unsubscribe at any time with one click.
                        </p>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button
                                type="button"
                                @click="closeModal"
                                class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50"
                            >
                                <span v-if="form.processing">Subscribing...</span>
                                <span v-else>Subscribe</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
