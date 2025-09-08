<template>
    <div class="relative">
        <Tooltip>
            <TooltipTrigger asChild>
                <button
                    @click="showShareMenu = !showShareMenu"
                    class="cursor-pointer rounded-full bg-gray-100 p-2 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600"
                >
                    <Icon name="share2" class="h-4 w-4 text-gray-600 dark:text-gray-300" />
                </button>
            </TooltipTrigger>
            <TooltipContent> Share this monitor </TooltipContent>
        </Tooltip>

        <!-- Share Menu Dropdown -->
        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="showShareMenu"
                @click.stop
                class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-gray-800 dark:ring-gray-700"
            >
                <div class="py-1">
                    <!-- Copy Link -->
                    <button
                        @click="copyLink"
                        class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <Icon name="link" class="mr-2 h-4 w-4" />
                        {{ linkCopied ? 'Link Copied!' : 'Copy Link' }}
                    </button>

                    <!-- Share on Twitter -->
                    <a
                        :href="twitterShareUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        @click="showShareMenu = false"
                        class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <Icon name="twitter" class="mr-2 h-4 w-4" />
                        Share on Twitter
                    </a>

                    <!-- Share on Facebook -->
                    <a
                        :href="facebookShareUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        @click="showShareMenu = false"
                        class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <Icon name="facebook" class="mr-2 h-4 w-4" />
                        Share on Facebook
                    </a>

                    <!-- Share on LinkedIn -->
                    <a
                        :href="linkedinShareUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        @click="showShareMenu = false"
                        class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <Icon name="linkedin" class="mr-2 h-4 w-4" />
                        Share on LinkedIn
                    </a>

                    <!-- Share via Email -->
                    <a
                        :href="emailShareUrl"
                        @click="showShareMenu = false"
                        class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <Icon name="mail" class="mr-2 h-4 w-4" />
                        Share via Email
                    </a>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Props {
    title: string;
    url?: string;
    status?: string;
    uptime?: number;
    responseTime?: number;
    sslStatus?: string | null;
}

const props = defineProps<Props>();

const showShareMenu = ref(false);
const linkCopied = ref(false);

// Handle click outside to close menu
const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.relative')) {
        showShareMenu.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const shareUrl = computed(() => props.url || window.location.href);
const shareTitle = computed(() => `${props.title} - Monitor Status`);

// Build detailed share text with stats
const buildShareText = () => {
    let text = `Uptime Status: ${props.title}`;

    if (props.status) {
        const statusEmoji = props.status === 'up' ? '✅' : props.status === 'down' ? '❌' : '⚠️';
        text += `\n${statusEmoji} Status: ${props.status === 'up' ? 'Operational' : props.status === 'down' ? 'Down' : 'Unknown'}`;
    }

    if (props.uptime !== undefined && props.uptime !== null) {
        text += `\n📊 Uptime: ${props.uptime}%`;
    }

    if (props.responseTime !== undefined && props.responseTime !== null) {
        text += `\n⚡ Avg Response: ${props.responseTime}ms`;
    }

    if (props.sslStatus) {
        const sslEmoji = props.sslStatus === 'valid' ? '🔒' : '⚠️';
        text += `\n${sslEmoji} SSL: ${props.sslStatus === 'valid' ? 'Valid' : props.sslStatus}`;
    }

    return text;
};

const twitterShareUrl = computed(() => {
    const text = encodeURIComponent(buildShareText());
    const url = encodeURIComponent(shareUrl.value);
    return `https://twitter.com/intent/tweet?text=${text}&url=${url}`;
});

const facebookShareUrl = computed(() => {
    const url = encodeURIComponent(shareUrl.value);
    return `https://www.facebook.com/sharer/sharer.php?u=${url}`;
});

const linkedinShareUrl = computed(() => {
    const url = encodeURIComponent(shareUrl.value);
    const title = encodeURIComponent(shareTitle.value);
    const summary = encodeURIComponent(buildShareText());
    return `https://www.linkedin.com/sharing/share-offsite/?url=${url}&title=${title}&summary=${summary}`;
});

const emailShareUrl = computed(() => {
    const subject = encodeURIComponent(shareTitle.value);
    const body = encodeURIComponent(`${buildShareText()}\n\nView details: ${shareUrl.value}`);
    return `mailto:?subject=${subject}&body=${body}`;
});

const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(shareUrl.value);
        linkCopied.value = true;
        setTimeout(() => {
            linkCopied.value = false;
            showShareMenu.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy link:', err);
    }
};
</script>
