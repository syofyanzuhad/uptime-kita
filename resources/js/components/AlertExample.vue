<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { ref } from 'vue';

// Example validation errors
const validationErrors = ref({
    email: ['Email is required', 'Email must be valid'],
    password: ['Password must be at least 8 characters'],
    name: ['Name is required'],
});

// Example error messages
const errorMessage = ref('Failed to save changes. Please try again.');
const warningMessage = ref('Your session will expire in 5 minutes.');
const successMessage = ref('Your changes have been saved successfully.');
const infoMessage = ref('New features are available. Check them out!');

// Dismissible alert state
const showDismissibleAlert = ref(true);

const dismissAlert = () => {
    showDismissibleAlert.value = false;
};
</script>

<template>
    <div class="space-y-4 p-4">
        <h2 class="mb-4 text-2xl font-bold">Alert Component Examples</h2>

        <!-- Validation Errors -->
        <div class="space-y-2">
            <h3 class="text-lg font-semibold">Validation Errors</h3>
            <Alert variant="destructive">
                <AlertTitle>Please fix the following errors:</AlertTitle>
                <AlertDescription>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        <li v-for="(errors, field) in validationErrors" :key="field">
                            <strong class="capitalize">{{ field }}:</strong>
                            <ul class="ml-4 list-inside list-disc">
                                <li v-for="error in errors" :key="error">{{ error }}</li>
                            </ul>
                        </li>
                    </ul>
                </AlertDescription>
            </Alert>
        </div>

        <!-- Error Message -->
        <div class="space-y-2">
            <h3 class="text-lg font-semibold">Error Message</h3>
            <Alert variant="destructive">
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{{ errorMessage }}</AlertDescription>
            </Alert>
        </div>

        <!-- Warning Message -->
        <div class="space-y-2">
            <h3 class="text-lg font-semibold">Warning Message</h3>
            <Alert variant="warning">
                <AlertTitle>Warning</AlertTitle>
                <AlertDescription>{{ warningMessage }}</AlertDescription>
            </Alert>
        </div>

        <!-- Success Message -->
        <div class="space-y-2">
            <h3 class="text-lg font-semibold">Success Message</h3>
            <Alert variant="success">
                <AlertTitle>Success</AlertTitle>
                <AlertDescription>{{ successMessage }}</AlertDescription>
            </Alert>
        </div>

        <!-- Info Message -->
        <div class="space-y-2">
            <h3 class="text-lg font-semibold">Info Message</h3>
            <Alert variant="info">
                <AlertTitle>Information</AlertTitle>
                <AlertDescription>{{ infoMessage }}</AlertDescription>
            </Alert>
        </div>

        <!-- Dismissible Alert -->
        <div class="space-y-2">
            <h3 class="text-lg font-semibold">Dismissible Alert</h3>
            <Alert v-if="showDismissibleAlert" variant="info" dismissible @dismiss="dismissAlert">
                <AlertTitle>Dismissible Alert</AlertTitle>
                <AlertDescription> This alert can be dismissed by clicking the X button. </AlertDescription>
            </Alert>
            <button
                v-if="!showDismissibleAlert"
                @click="showDismissibleAlert = true"
                class="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600"
            >
                Show Alert Again
            </button>
        </div>

        <!-- Simple Alert (without title) -->
        <div class="space-y-2">
            <h3 class="text-lg font-semibold">Simple Alert</h3>
            <Alert variant="default">
                <AlertDescription> This is a simple alert without a title, just a description. </AlertDescription>
            </Alert>
        </div>

        <!-- Custom Styled Alert -->
        <div class="space-y-2">
            <h3 class="text-lg font-semibold">Custom Styled Alert</h3>
            <Alert variant="warning" class="border-2 border-yellow-400 bg-yellow-50 dark:bg-yellow-900/20">
                <AlertTitle class="text-yellow-800 dark:text-yellow-200">Custom Styling</AlertTitle>
                <AlertDescription class="text-yellow-700 dark:text-yellow-300">
                    This alert has custom styling applied through the class prop.
                </AlertDescription>
            </Alert>
        </div>
    </div>
</template>
