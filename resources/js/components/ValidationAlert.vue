<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { computed } from 'vue';

type ErrorValue = string | string[] | Record<string, any>;

interface ValidationErrors {
    [key: string]: ErrorValue;
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

const normalizedErrors = computed<Record<string, string[]>>(() => {
    if (!props.errors || typeof props.errors !== 'object') {
        return {};
    }

    const result: Record<string, string[]> = {};

    for (const [field, value] of Object.entries(props.errors)) {
        if (value === null || value === undefined || value === '') {
            continue;
        }

        if (Array.isArray(value)) {
            result[field] = value.map((v) => String(v));
        } else if (typeof value === 'string') {
            result[field] = [value];
        } else if (typeof value === 'object') {
            result[field] = Object.values(value).map((v) => String(v));
        } else {
            result[field] = [String(value)];
        }
    }

    return result;
});

const hasErrors = computed(() => {
    return Object.keys(normalizedErrors.value).length > 0;
});

const errorCount = computed(() => {
    return Object.values(normalizedErrors.value).reduce((total, errs) => total + errs.length, 0);
});
</script>

<template>
    <Alert v-if="show && hasErrors" variant="destructive" :class="props.class">
        <AlertTitle>{{ title }}</AlertTitle>
        <AlertDescription>
            <div class="mt-2">
                <p class="mb-2 text-sm">{{ errorCount }} error{{ errorCount !== 1 ? 's' : '' }} found:</p>
                <ul class="list-inside list-disc space-y-1 text-sm">
                    <li v-for="(fieldErrors, field) in normalizedErrors" :key="field" class="mb-2">
                        <span class="font-medium capitalize">{{ String(field).replace(/_/g, ' ') }}:</span>
                        <ul class="mt-1 ml-4 list-inside list-disc">
                            <li v-for="(error, idx) in fieldErrors" :key="idx" class="text-sm break-words whitespace-pre-wrap">
                                {{ error }}
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </AlertDescription>
    </Alert>
</template>
