import { ref, onMounted, onUnmounted, watch, type Ref } from 'vue';

export interface StatusChange {
    id: string;
    monitor_id: number;
    monitor_name: string;
    monitor_url: string;
    old_status: 'up' | 'down';
    new_status: 'up' | 'down';
    changed_at: string;
    favicon: string | null;
    status_page_ids?: number[];
}

export interface UseMonitorStatusStreamOptions {
    monitorIds?: number[];
    statusPageId?: number;
    onStatusChange?: (change: StatusChange) => void;
    enabled?: boolean | Ref<boolean>;
}

export function useMonitorStatusStream(options: UseMonitorStatusStreamOptions = {}) {
    const { monitorIds = [], statusPageId, onStatusChange } = options;

    // Handle enabled as either a boolean or a ref
    const enabledRef = typeof options.enabled === 'object' ? options.enabled : ref(options.enabled ?? true);

    const isConnected = ref(false);
    const isConnecting = ref(false);
    const error = ref<string | null>(null);
    const lastEventId = ref<string | null>(null);
    const statusChanges = ref<StatusChange[]>([]);

    let eventSource: EventSource | null = null;
    let reconnectTimeout: ReturnType<typeof setTimeout> | null = null;
    let reconnectAttempts = 0;
    const maxReconnectAttempts = 5;
    const baseReconnectDelay = 1000; // 1 second

    const buildUrl = (): string => {
        const params = new URLSearchParams();

        if (monitorIds.length > 0) {
            params.set('monitor_ids', monitorIds.join(','));
        }

        if (statusPageId) {
            params.set('status_page_id', statusPageId.toString());
        }

        if (lastEventId.value) {
            params.set('last_event_id', lastEventId.value);
        }

        const queryString = params.toString();
        return `/api/monitor-status-stream${queryString ? `?${queryString}` : ''}`;
    };

    const connect = () => {
        if (!enabledRef.value || eventSource) return;

        isConnecting.value = true;
        error.value = null;

        try {
            eventSource = new EventSource(buildUrl());

            eventSource.onopen = () => {
                isConnected.value = true;
                isConnecting.value = false;
                reconnectAttempts = 0;
                error.value = null;
            };

            eventSource.addEventListener('status_change', (event: MessageEvent) => {
                try {
                    const change: StatusChange = JSON.parse(event.data);
                    lastEventId.value = event.lastEventId || change.id;

                    // Add to local list (keep last 50)
                    statusChanges.value = [change, ...statusChanges.value].slice(0, 50);

                    // Call callback if provided
                    if (onStatusChange) {
                        onStatusChange(change);
                    }
                } catch (e) {
                    console.error('Failed to parse status change:', e);
                }
            });

            eventSource.addEventListener('heartbeat', () => {
                // Heartbeat received, connection is healthy
            });

            eventSource.addEventListener('reconnect', () => {
                // Server requested reconnect
                disconnect();
                scheduleReconnect();
            });

            eventSource.addEventListener('end', () => {
                // Stream ended, reconnect
                disconnect();
                scheduleReconnect();
            });

            eventSource.onerror = () => {
                isConnected.value = false;
                isConnecting.value = false;

                if (eventSource?.readyState === EventSource.CLOSED) {
                    disconnect();
                    scheduleReconnect();
                }
            };
        } catch (e) {
            isConnecting.value = false;
            error.value = 'Failed to establish SSE connection';
            scheduleReconnect();
        }
    };

    const disconnect = () => {
        if (eventSource) {
            eventSource.close();
            eventSource = null;
        }
        isConnected.value = false;
        isConnecting.value = false;

        if (reconnectTimeout) {
            clearTimeout(reconnectTimeout);
            reconnectTimeout = null;
        }
    };

    const scheduleReconnect = () => {
        if (!enabledRef.value) return;

        if (reconnectAttempts >= maxReconnectAttempts) {
            error.value = 'Max reconnection attempts reached';
            return;
        }

        const delay = baseReconnectDelay * Math.pow(2, reconnectAttempts);
        reconnectAttempts++;

        reconnectTimeout = setTimeout(() => {
            connect();
        }, delay);
    };

    const clearStatusChanges = () => {
        statusChanges.value = [];
    };

    // Watch for enabled changes
    watch(enabledRef, (newEnabled) => {
        if (newEnabled) {
            connect();
        } else {
            disconnect();
        }
    });

    onMounted(() => {
        if (enabledRef.value) {
            connect();
        }
    });

    onUnmounted(() => {
        disconnect();
    });

    return {
        isConnected,
        isConnecting,
        error,
        statusChanges,
        clearStatusChanges,
        connect,
        disconnect,
    };
}
