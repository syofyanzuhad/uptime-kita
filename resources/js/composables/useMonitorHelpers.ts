export function getStatusText(status?: string): string {
    switch (status) {
        case 'up': return 'Operational';
        case 'down': return 'Down';
        case 'not yet checked': return 'Not Yet Checked';
        case 'warning': return 'Degraded';
        default: return 'Unknown';
    }
}

export function getStatusIcon(status?: string): string {
    switch (status) {
        case 'up': return 'checkCircle';
        case 'down': return 'xCircle';
        case 'not yet checked': return 'clock';
        default: return 'alertCircle';
    }
}

export function getStatusColor(status?: string): string {
    switch (status?.toLowerCase()) {
        case 'up': return 'bg-green-500';
        case 'down': return 'bg-red-500';
        case 'warning': return 'bg-yellow-500';
        default: return 'bg-gray-400';
    }
}

export function getStatusTextColor(status?: string): string {
    switch (status?.toLowerCase()) {
        case 'up': return 'text-green-600';
        case 'down': return 'text-red-600';
        case 'warning': return 'text-yellow-600';
        default: return 'text-gray-600';
    }
}

export function getUptimeColor(pct: number): string {
    if (pct >= 99.5) return 'text-green-600 dark:text-green-400';
    if (pct >= 95) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
}

export function getResponseTimeColorClass(rt: number | null): string {
    if (rt === null) return 'text-gray-500 dark:text-gray-400';
    if (rt < 300) return 'text-green-600 dark:text-green-400';
    if (rt < 1000) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
}

export function getTagDisplayName(tag: any): string {
    if (!tag) return '';
    if (typeof tag.name === 'string') return tag.name;
    return tag.name?.en || tag.name || String(tag);
}

export function formatDuration(minutes: number): string {
    if (minutes < 60) return `${minutes} minute${minutes !== 1 ? 's' : ''}`;
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (h < 24) return m > 0 ? `${h} hour${h !== 1 ? 's' : ''} ${m} minute${m !== 1 ? 's' : ''}` : `${h} hour${h !== 1 ? 's' : ''}`;
    const d = Math.floor(h / 24);
    const rh = h % 24;
    return rh > 0 ? `${d} day${d !== 1 ? 's' : ''} ${rh} hour${rh !== 1 ? 's' : ''}` : `${d} day${d !== 1 ? 's' : ''}`;
}

export function formatRelativeTime(dateString: string): string {
    const diff = Math.floor((Date.now() - new Date(dateString).getTime()) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) { const m = Math.floor(diff / 60); return `${m} minute${m > 1 ? 's' : ''} ago`; }
    if (diff < 86400) { const h = Math.floor(diff / 3600); return `${h} hour${h > 1 ? 's' : ''} ago`; }
    if (diff < 604800) { const d = Math.floor(diff / 86400); return `${d} day${d > 1 ? 's' : ''} ago`; }
    return new Date(dateString).toLocaleDateString();
}

export function formatChecksCount(n: number): string {
    if (n >= 1e9) return (n / 1e9).toFixed(1) + 'B';
    if (n >= 1e6) return (n / 1e6).toFixed(1) + 'M';
    if (n >= 1e3) return (n / 1e3).toFixed(1) + 'K';
    return String(n);
}
