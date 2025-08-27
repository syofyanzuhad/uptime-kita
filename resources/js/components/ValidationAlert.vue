<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { computed } from 'vue';

interface ValidationErrors {
    [key: string]: string[];
}

interface Props {
    errors?: ValidationErrors;
    title?: string;
    show?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Please fix the following errors:',
    show: true,
});

const hasErrors = computed(() => {
    return props.errors && Object.keys(props.errors).length > 0;
});

const errorCount = computed(() => {
    if (!props.errors) return 0;
    return Object.values(props.errors).flat().length;
});
</script>

<template>
    <Alert v-if="show && hasErrors" variant="destructive" :class="props.class">
        <AlertTitle>{{ title }}</AlertTitle>
        <AlertDescription>
            <div class="mt-2">
                <p class="mb-2 text-sm">{{ errorCount }} error{{ errorCount !== 1 ? 's' : '' }} found:</p>
                <ul class="list-inside list-disc space-y-1 text-sm">
                    <li v-for="(errors, field) in errors" :key="field" class="mb-2">
                        <span class="font-medium capitalize">{{ String(field).replace(/_/g, ' ') }}:</span>
                        <ul class="mt-1 ml-4 list-inside list-disc">
                            <li v-for="error in errors" :key="error" class="text-sm">
                                {{ error }}
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </AlertDescription>
    </Alert>
</template>
