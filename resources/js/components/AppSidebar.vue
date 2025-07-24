<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, CheckCircle, Coffee, Github, LayoutGrid, LucideMonitorSmartphone, SquareActivity } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage<SharedData>();
const isAuthenticated = computed(() => !!page.props.auth.user);

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Monitor',
        href: '/monitor',
        icon: CheckCircle,
    },
    {
        title: 'Status Pages',
        href: '/status-pages',
        icon: SquareActivity,
    },
    {
        title: 'Notifications',
        href: '/settings/notifications',
        icon: LucideMonitorSmartphone,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Buy me a coffee',
        href: 'https://www.buymeacoffee.com/syofyanzuhad',
        icon: Coffee,
    },
    {
        title: 'Github Repo ⭐️',
        href: 'https://github.com/syofyanzuhad/uptime-kita',
        icon: Github,
    },
    {
        title: 'Documentation',
        href: 'https://github.com/syofyanzuhad/uptime-kita/blob/main/README.md',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <div class="text-xs text-gray-500 dark:text-gray-400 px-4 pt-1 pb-2">
              Last update: {{ page.props.lastUpdate }}
            </div>
            <hr v-if="isAuthenticated" class="my-2 border-sidebar-border/70 dark:border-sidebar-border" />
            <NavUser v-if="isAuthenticated" />
            <div v-else class="p-2">
                <Link
                    :href="route('login')"
                    class="flex w-full items-center justify-center rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                >
                    Log in
                </Link>
            </div>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
