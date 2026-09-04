<script setup lang="ts">
import PublicLayout from '@/components/PublicLayout.vue';

defineProps<{
    title: string;
    description: string;
    brandTitle?: string;
    ogImage?: string;
    canonicalUrl?: string;
    shareUrl: string;
    shareText: string;
    showServerStats?: boolean;
    jsonLd?: Record<string, any>;
    containerClass?: string;
}>();
</script>

<template>
    <PublicLayout v-bind="$props">
        <template v-for="(_, slotName) in $slots" #[slotName]="slotData">
            <slot :name="slotName" v-bind="slotData" />
        </template>
    </PublicLayout>
</template>
