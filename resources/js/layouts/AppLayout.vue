<script setup lang="ts">
import FlashMessage from '@/components/FlashMessage.vue';
import ValidationAlert from '@/components/ValidationAlert.vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

const page = usePage();

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Flash Message -->
        <FlashMessage />

        <!-- Validation Alert -->
        <ValidationAlert :errors="(page.props as any).errors" />

        <!-- Main content -->
        <slot />
    </AppLayout>
</template>
