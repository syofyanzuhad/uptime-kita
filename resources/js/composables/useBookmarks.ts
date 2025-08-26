import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface BookmarkState {
    pinnedMonitors: number[];
    loadingMonitors: number[];
    lastSync: number | null;
}

// Global state - shared across all component instances
const pinnedMonitors = ref<Set<number>>(new Set());
const loadingMonitors = ref<Set<number>>(new Set());
const lastSync = ref<number | null>(null);

// Global event bus for refresh notifications
const refreshCallbacks = new Set<() => void>();

const STORAGE_KEY = 'uptime_kita_bookmarks';
const SYNC_INTERVAL = 5 * 60 * 1000; // 5 minutes

export function useBookmarks() {
    // Load bookmarks from localStorage on initialization
    const loadFromStorage = () => {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);
            if (stored) {
                const data: BookmarkState = JSON.parse(stored);
                pinnedMonitors.value = new Set(data.pinnedMonitors);
                lastSync.value = data.lastSync;
            }
        } catch (error) {
            console.warn('Failed to load bookmarks from localStorage:', error);
        }
    };

    // Save bookmarks to localStorage
    const saveToStorage = () => {
        try {
            const data: BookmarkState = {
                pinnedMonitors: Array.from(pinnedMonitors.value),
                loadingMonitors: Array.from(loadingMonitors.value),
                lastSync: lastSync.value,
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        } catch (error) {
            console.warn('Failed to save bookmarks to localStorage:', error);
        }
    };

    // Check if we need to sync with server
    const needsSync = computed(() => {
        if (!lastSync.value) return true;
        return Date.now() - lastSync.value > SYNC_INTERVAL;
    });

    // Sync bookmarks with server
    const syncWithServer = async () => {
        if (!needsSync.value) return;

        try {
            // This would be an API endpoint to get all pinned monitors for the user
            // For now, we'll just update the lastSync timestamp
            lastSync.value = Date.now();
            saveToStorage();
        } catch (error) {
            console.warn('Failed to sync bookmarks with server:', error);
        }
    };

    // Toggle pin status for a monitor
    const togglePin = async (monitorId: number) => {
        if (loadingMonitors.value.has(monitorId)) return;

        try {
            loadingMonitors.value.add(monitorId);
            const isCurrentlyPinned = pinnedMonitors.value.has(monitorId);
            const newPinStatus = !isCurrentlyPinned;

            // Optimistically update the UI
            if (newPinStatus) {
                pinnedMonitors.value.add(monitorId);
            } else {
                pinnedMonitors.value.delete(monitorId);
            }
            saveToStorage();

            // Send request to server using Inertia router
            await new Promise<void>((resolve, reject) => {
                router.post(
                    `/monitor/${monitorId}/toggle-pin`,
                    {
                        is_pinned: newPinStatus,
                    },
                    {
                        preserveScroll: true,
                        onSuccess: (page: any) => {
                            // Check if there's an error flash message
                            if (page?.props?.flash?.type === 'error') {
                                reject(new Error(page.props.flash.message));
                            } else {
                                // Success - the optimistic update was correct
                                resolve();
                            }
                        },
                        onError: () => {
                            reject(new Error('Failed to update pin status'));
                        },
                    },
                );
            });

            // Update lastSync timestamp
            lastSync.value = Date.now();
            saveToStorage();

            // Notify all components to refresh their monitor lists
            // Add a small delay to ensure Inertia response is fully processed
            setTimeout(() => {
                refreshCallbacks.forEach((callback) => {
                    try {
                        callback();
                    } catch (err) {
                        console.warn('Error in refresh callback:', err);
                    }
                });
            }, 100);
        } catch (error) {
            console.error('Error toggling pin:', error);
            // Revert optimistic update on error - restore to original state
            const isCurrentlyPinned = pinnedMonitors.value.has(monitorId);
            if (isCurrentlyPinned) {
                pinnedMonitors.value.delete(monitorId);
            } else {
                pinnedMonitors.value.add(monitorId);
            }
            saveToStorage();
            throw error;
        } finally {
            loadingMonitors.value.delete(monitorId);
        }
    };

    // Check if a monitor is pinned
    const isPinned = (monitorId: number): boolean => {
        return pinnedMonitors.value.has(monitorId);
    };

    // Get all pinned monitor IDs
    const getPinnedMonitors = computed(() => {
        return Array.from(pinnedMonitors.value);
    });

    // Clear all bookmarks (useful for logout)
    const clearBookmarks = () => {
        pinnedMonitors.value.clear();
        loadingMonitors.value.clear();
        lastSync.value = null;
        localStorage.removeItem(STORAGE_KEY);
    };

    // Register a callback to be called when pins change
    const onPinChanged = (callback: () => void) => {
        refreshCallbacks.add(callback);

        // Return a cleanup function
        return () => {
            refreshCallbacks.delete(callback);
        };
    };

    // Initialize the composable
    const initialize = () => {
        loadFromStorage();
        syncWithServer();
    };

    return {
        pinnedMonitors: computed(() => pinnedMonitors.value),
        loadingMonitors: computed(() => loadingMonitors.value),
        isPinned,
        togglePin,
        getPinnedMonitors,
        clearBookmarks,
        initialize,
        needsSync,
        syncWithServer,
        onPinChanged,
    };
}
