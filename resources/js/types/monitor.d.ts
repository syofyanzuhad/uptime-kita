// resources/js/types/monitor.d.ts

export interface Monitor {
    id: number;
    url: string;
    uptime_status: 'up' | 'down' | 'not yet checked';
    last_check_date: string | null;
    certificate_check_enabled: boolean;
    certificate_status: 'valid' | 'invalid' | 'not applicable' | null;
    certificate_expiration_date: string | null;
    down_for_events_count: number;
    uptime_check_enabled: boolean;
    uptime_check_interval: number;
    is_subscribed?: boolean;
    is_public?: boolean;
  }

  export interface FlashMessage {
    message: string;
    type: 'success' | 'error';
  }

  export interface MonitorHistory {
    id: number;
    uptime_status: 'up' | 'down' | 'not yet checked';
    created_at: string;
    message?: string;
  }
