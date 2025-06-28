// resources/js/types/monitor.d.ts

export interface Monitor {
    id: number;
    url: string;
    uptime_status: 'up' | 'down' | 'not yet checked';
    uptime_check_enabled: boolean;
    last_check_date: string | null;
    certificate_check_enabled: boolean;
    certificate_status: 'valid' | 'invalid' | 'not applicable' | null;
    certificate_expiration_date: string | null;
    down_for_events_count: number;
    uptime_check_interval: number;
    is_subscribed?: boolean;
    is_public?: boolean;
    favicon?: string;
    today_uptime_percentage?: number;
    uptime_status_last_change_date?: string | null;
    uptime_check_failure_reason?: string | null;
    created_at: string;
    updated_at: string;
    histories?: MonitorHistory[];
}

export interface FlashMessage {
    message: string;
    type: 'success' | 'error';
}

export interface MonitorHistory {
    id: number;
    monitor_id: number;
    uptime_status: 'up' | 'down' | 'not yet checked';
    message?: string;
    created_at: string;
    updated_at: string;
}

export interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginator<T> {
    data: T[];
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        links: PaginatorLink[];
        path: string;
        per_page: number;
        to: number;
        total: number;
    };
}
