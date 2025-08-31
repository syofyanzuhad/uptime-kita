<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage<SharedData>();
const expandedItems = ref<Set<string>>(new Set());

// Auto-expand submenus when current page matches
const initializeExpandedItems = () => {
    props.items.forEach((item) => {
        if (item.items && isItemActive(item)) {
            expandedItems.value.add(item.title);
        }
    });
};

// Initialize expanded items on mount
onMounted(() => {
    initializeExpandedItems();
});

// Watch for page changes and update expanded items
watch(
    () => page.url,
    () => {
        initializeExpandedItems();
    },
);

const toggleItem = (itemTitle: string) => {
    if (expandedItems.value.has(itemTitle)) {
        expandedItems.value.delete(itemTitle);
    } else {
        expandedItems.value.add(itemTitle);
    }
};

const isItemActive = (item: NavItem): boolean => {
    if (item.href && item.href === page.url) return true;
    if (item.items) {
        return item.items.some((subItem) => subItem.href === page.url);
    }
    return false;
};

const isSubItemActive = (subItem: NavItem): boolean => {
    return subItem.href === page.url;
};
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in props.items" :key="item.title">
                <!-- Regular menu item without subitems -->
                <template v-if="!item.items">
                    <SidebarMenuButton as-child :is-active="isItemActive(item)" :tooltip="item.title">
                        <Link :href="item.href!">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </template>

                <!-- Menu item with subitems (collapsible) -->
                <template v-else>
                    <SidebarMenuButton
                        :is-active="isItemActive(item)"
                        :tooltip="item.title"
                        @click="toggleItem(item.title)"
                        class="group/collapsible cursor-pointer"
                    >
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                        <ChevronRight class="ml-auto transition-transform duration-200" :class="{ 'rotate-90': expandedItems.has(item.title) }" />
                    </SidebarMenuButton>

                    <!-- Submenu -->
                    <SidebarMenuSub v-show="expandedItems.has(item.title)">
                        <SidebarMenuSubItem v-for="subItem in item.items" :key="subItem.title">
                            <SidebarMenuSubButton as-child :is-active="isSubItemActive(subItem)">
                                <Link :href="subItem.href!">
                                    <component :is="subItem.icon" v-if="subItem.icon" />
                                    <span>{{ subItem.title }}</span>
                                </Link>
                            </SidebarMenuSubButton>
                        </SidebarMenuSubItem>
                    </SidebarMenuSub>
                </template>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
