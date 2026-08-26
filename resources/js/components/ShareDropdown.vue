<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const props = defineProps<{ url: string; text: string }>();

const open = ref(false);
const copied = ref(false);
const root = ref<HTMLElement | null>(null);

const shareUrl = computed(() => props.url);
const shareText = computed(() => props.text);

function openWindow(link: string) { window.open(link, '_blank', 'width=550,height=420'); }
function toX() { openWindow(`https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText.value)}&url=${encodeURIComponent(shareUrl.value)}`); open.value = false; }
function toFb() { openWindow(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl.value)}`); open.value = false; }
function toLi() { openWindow(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl.value)}`); open.value = false; }
function toWa() { window.open(`https://wa.me/?text=${encodeURIComponent(shareText.value + ' ' + shareUrl.value)}`, '_blank'); open.value = false; }
async function copy() {
    try { await navigator.clipboard.writeText(shareUrl.value); copied.value = true; setTimeout(() => { copied.value = false; open.value = false; }, 1200); } catch {}
}
function onClickOutside(e: MouseEvent) {
    if (root.value && !root.value.contains(e.target as Node)) open.value = false;
}
onMounted(() => document.addEventListener('click', onClickOutside));
onUnmounted(() => document.removeEventListener('click', onClickOutside));
</script>

<template>
    <div ref="root" class="relative">
        <button @click.stop="open = !open" class="cursor-pointer rounded-full bg-gray-100 p-2 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600" title="Share">
            <Icon name="share2" class="h-4 w-4 text-gray-600 dark:text-gray-300" />
        </button>
        <div v-if="open" class="absolute right-0 z-50 mt-2 w-48 rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <button @click="toX" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"><Icon name="twitter" class="h-4 w-4" />Share on X</button>
            <button @click="toFb" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"><Icon name="facebook" class="h-4 w-4" />Share on Facebook</button>
            <button @click="toLi" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"><Icon name="linkedin" class="h-4 w-4" />Share on LinkedIn</button>
            <button @click="toWa" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"><Icon name="messageCircle" class="h-4 w-4" />Share on WhatsApp</button>
            <hr class="my-1 border-gray-200 dark:border-gray-700" />
            <button @click="copy" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"><Icon :name="copied ? 'check' : 'link'" class="h-4 w-4" />{{ copied ? 'Copied!' : 'Copy Link' }}</button>
        </div>
    </div>
</template>
