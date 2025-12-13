import { ref, computed } from 'vue';

export interface ToastNotification {
    id: string;
    type: 'success' | 'error' | 'warning' | 'info' | 'status-up' | 'status-down';
    title: string;
    message: string;
    duration: number; // ms, 0 = persistent
    timestamp: Date;
    data?: Record<string, unknown>;
}

const toasts = ref<ToastNotification[]>([]);
const maxToasts = 5;

export function useToastNotifications() {
    const visibleToasts = computed(() => toasts.value.slice(0, maxToasts));

    const addToast = (toast: Omit<ToastNotification, 'id' | 'timestamp'>) => {
        const id = `toast_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        const newToast: ToastNotification = {
            ...toast,
            id,
            timestamp: new Date(),
            duration: toast.duration ?? 5000,
        };

        toasts.value = [newToast, ...toasts.value];

        // Auto-remove after duration
        if (newToast.duration > 0) {
            setTimeout(() => {
                removeToast(id);
            }, newToast.duration);
        }

        return id;
    };

    const removeToast = (id: string) => {
        toasts.value = toasts.value.filter((t) => t.id !== id);
    };

    const clearAllToasts = () => {
        toasts.value = [];
    };

    // Convenience methods for monitor status changes
    const addStatusChangeToast = (change: {
        monitor_name: string;
        old_status: 'up' | 'down';
        new_status: 'up' | 'down';
        changed_at: string;
        favicon?: string | null;
    }) => {
        const isRecovered = change.new_status === 'up';

        return addToast({
            type: isRecovered ? 'status-up' : 'status-down',
            title: isRecovered ? 'Service Recovered' : 'Service Down',
            message: `${change.monitor_name} is now ${isRecovered ? 'operational' : 'experiencing issues'}`,
            duration: 8000, // 8 seconds for status changes
            data: {
                ...change,
            },
        });
    };

    return {
        toasts: visibleToasts,
        addToast,
        removeToast,
        clearAllToasts,
        addStatusChangeToast,
    };
}

// Export a singleton for global use
export const globalToasts = useToastNotifications();
