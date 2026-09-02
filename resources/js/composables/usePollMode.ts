import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export type PollMode = 'manual' | 'auto';

export function usePollMode() {
    let page: ReturnType<typeof usePage> | null = null;
    try {
        page = usePage();
    } catch {
        // Fallback when invoked outside Inertia component context
    }

    const pollMode = computed<PollMode>(() => {
        const inertiaProp = (page?.props as any)?.pollRequestApi;
        if (typeof inertiaProp === 'string') {
            return inertiaProp.toLowerCase() === 'auto' ? 'auto' : 'manual';
        }

        const viteEnv =
            import.meta.env.VITE_POLL_REQUEST_API ||
            import.meta.env.VITE_POLLING_MODE ||
            import.meta.env.VITE_POLL_MODE;

        if (typeof viteEnv === 'string') {
            return viteEnv.toLowerCase() === 'auto' ? 'auto' : 'manual';
        }

        return 'manual';
    });

    const isAutoPolling = computed(() => pollMode.value === 'auto');

    return {
        pollMode,
        isAutoPolling,
    };
}
