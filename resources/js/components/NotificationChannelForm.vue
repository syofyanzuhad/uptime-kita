<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import Select from '@/components/ui/input/Select.vue';
import { router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, watch } from 'vue';

const props = defineProps({
    channel: {
        type: Object,
        default: null,
    },
    isEdit: {
        type: Boolean,
        default: false,
    },
});

const typeOptions = [
    { label: 'Telegram', value: 'telegram' },
    { label: 'Slack', value: 'slack' },
    { label: 'Email', value: 'email' },
];

const form = useForm({
    type: props.channel?.type || '',
    destination: props.channel?.destination || '',
    is_enabled: props.channel?.is_enabled ?? true,
    metadata: props.channel?.metadata ? JSON.stringify(props.channel.metadata, null, 2) : '',
});

const isMetadataValid = computed(() => {
    if (!form.metadata) return true;
    try {
        JSON.parse(form.metadata);
        return true;
    } catch {
        return false;
    }
});

function clearMetadata() {
    form.metadata = '';
    nextTick(() => {
        const el = document.getElementById('metadata-textarea');
        if (el) el.style.height = 'auto';
    });
}

function autoResize(e: Event) {
    const el = e.target as HTMLTextAreaElement;
    el.style.height = 'auto';
    el.style.height = el.scrollHeight + 'px';
}

watch(
    () => props.channel,
    (newVal) => {
        if (newVal) {
            form.type = newVal.type || '';
            form.destination = newVal.destination || '';
            form.is_enabled = newVal.is_enabled ?? true;
            form.metadata = newVal.metadata ? JSON.stringify(newVal.metadata, null, 2) : '';
        }
    },
);

function validate() {
    let valid = true;
    if (!form.type) {
        form.setError('type', 'Type is required.');
        valid = false;
    }
    if (!form.destination) {
        form.setError('destination', 'Destination is required.');
        valid = false;
    }
    return valid;
}

function handleSubmit() {
    if (!validate()) return;

    let metadataObj = undefined;
    if (form.metadata) {
        try {
            metadataObj = JSON.parse(form.metadata);
        } catch {
            alert('Invalid JSON in metadata');
            return;
        }
    }

    // Update form data with parsed metadata
    form.metadata = metadataObj;

    // Submit the form - Inertia will handle the submission
    if (props.isEdit) {
        form.put(route('notifications.update', props.channel.id));
    } else {
        form.post(route('notifications.store'));
    }
}
</script>

<template>
    <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
            <label class="mb-1 block font-medium">Type<span class="text-red-500">*</span></label>
            <Select v-model="form.type" :items="typeOptions" placeholder="Select type" />
            <div v-if="form.errors.type" class="mt-1 text-sm text-red-500">{{ form.errors.type }}</div>
        </div>
        <div>
            <label class="mb-1 block font-medium">Destination<span class="text-red-500">*</span></label>
            <Input v-model="form.destination" type="text" class="w-full" placeholder="chat_id, email, webhook, etc." />
            <small class="mt-1 text-sm text-gray-500">
                (Telegram: chat_id, Slack: webhook url, Email: email)
                <span v-if="form.type === 'telegram'" class="text-gray-500">
                    <br />
                    <span class="text-sm">
                        Send a /start to the <a href="https://t.me/@uptime_kita_bot" target="_blank" class="text-blue-500">@uptime_kita_bot</a> to get
                        your chat_id.
                    </span>
                </span>
            </small>
            <div v-if="form.errors.destination" class="mt-1 text-sm text-red-500">{{ form.errors.destination }}</div>
        </div>
        <div class="flex items-center space-x-2">
            <Checkbox v-model="form.is_enabled" id="is_enabled" />
            <label for="is_enabled">Enabled</label>
        </div>
        <div>
            <label class="mb-1 block font-medium">Metadata (Optional)</label>
            <textarea
                id="metadata-textarea"
                v-model="form.metadata"
                class="w-full resize-none rounded border p-2"
                placeholder='{"note": "Optional metadata"}'
                @input="autoResize"
                :class="{ 'border-red-500': form.metadata && !isMetadataValid }"
            ></textarea>
            <div v-if="form.metadata && !isMetadataValid" class="mt-1 text-sm text-red-500">Invalid JSON format</div>
            <button type="button" @click="clearMetadata" class="mt-1 text-sm text-gray-500">Clear metadata</button>
        </div>
        <div class="flex space-x-2">
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Saving...' : isEdit ? 'Update Channel' : 'Create Channel' }}
            </Button>
            <Button type="button" variant="outline" @click="router.visit(route('notifications.index'))"> Cancel </Button>
        </div>
    </form>
</template>
