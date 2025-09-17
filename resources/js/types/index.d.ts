import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href?: string;
    icon?: LucideIcon;
    isActive?: boolean;
    items?: NavItem[];
}

export interface SharedData extends PageProps {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    csrf_token: string;
    flash?: {
        success?: string;
        error?: string;
        warning?: string;
        info?: string;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    is_admin: boolean;
    monitors?: Monitor[];
    status_pages?: StatusPage[];
    notification_channels?: NotificationChannel[];
}

export interface Monitor {
    id: number;
    display_name: string;
    url: URL;
    uptime_status: string;
    created_at: string;
    raw_url: string;
    host: string;
    pivot: {
        is_active: boolean;
        is_pinned: boolean;
        created_at: string;
    };
}

export interface StatusPage {
    id: number;
    user_id: number;
    title: string;
    description: string | null;
    path: string;
    created_at: string;
}

export interface NotificationChannel {
    id: number;
    user_id: number;
    type: string;
    destination: string;
    is_enabled: boolean;
    created_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
