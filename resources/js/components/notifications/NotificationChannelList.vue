<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import NotificationChannelForm from './NotificationChannelForm.vue';
import NotificationChannelItem from './NotificationChannelItem.vue';

const props = defineProps<{
    channels: any[];
    showForm?: boolean;
    isEdit?: boolean;
    editingChannel?: any;
}>();

const channels = ref([...props.channels]);
console.log('%cresources/js/components/NotificationChannelList.vue:32 object', 'color: #007acc;', channels);
const showForm = ref(props.showForm || false);
const isEdit = ref(props.isEdit || false);
const editingChannel = ref(props.editingChannel || undefined);

// Watch for prop changes
watch(
    () => props.channels,
    (newVal) => {
        channels.value = [...newVal];
    },
);

watch(
    () => props.showForm,
    (newVal) => {
        showForm.value = newVal || false;
    },
);

watch(
    () => props.isEdit,
    (newVal) => {
        isEdit.value = newVal || false;
    },
);

watch(
    () => props.editingChannel,
    (newVal) => {
        editingChannel.value = newVal || undefined;
    },
);

function handleAdd(): void {
    router.visit(route('notifications.create'));
}

function handleEdit(channel: any): void {
    router.visit(route('notifications.edit', channel.id));
}

async function handleDelete(channel: any): Promise<void> {
    if (!confirm('Delete this channel?')) return;

    router.delete(route('notifications.destroy', channel.id));
}

async function handleToggle(channel: any): Promise<void> {
    router.patch(route('notifications.toggle', channel.id));
}
</script>

<template>
    <div>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-bold">Notification Channels</h2>
            <Button @click="handleAdd" variant="default">Add Channel</Button>
        </div>
        <div v-if="channels.length === 0" class="text-gray-500">No notification channels configured.</div>
        <div v-if="showForm" class="my-6">
            <NotificationChannelForm :channel="editingChannel" :isEdit="isEdit" />
        </div>
        <div>
            <NotificationChannelItem
                v-for="channel in channels"
                :key="channel.id"
                :channel="channel"
                @edit="handleEdit"
                @delete="handleDelete"
                @toggle="handleToggle"
            />
        </div>
    </div>
</template>
