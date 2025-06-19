<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { Button } from '@/components/ui/button';
import { Sun, Moon } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';
import { computed } from 'vue';

withDefaults(defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>(),{
    breadcrumbs:()=>[]
});

const { appearance, updateAppearance } = useAppearance();
const isDark = computed(() => appearance.value === 'dark');
function toggleDarkMode() {
    updateAppearance(isDark.value ? 'light' : 'dark');
}
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2 flex-1">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <!-- Dark Mode Toggle Button -->
        <Button variant="ghost" size="icon" class="ml-auto group h-9 w-9 cursor-pointer" @click="toggleDarkMode">
            <span class="sr-only">Toggle dark mode</span>
            <component :is="isDark ? Sun : Moon" class="size-5 opacity-80 group-hover:opacity-100" />
        </Button>
    </header>
</template>
