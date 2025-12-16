// resources/js/types/import.d.ts

export interface ImportRow {
    url: string;
    display_name?: string;
    uptime_check_enabled?: boolean;
    certificate_check_enabled?: boolean;
    uptime_check_interval?: number;
    is_public?: boolean;
    sensitivity?: 'low' | 'medium' | 'high';
    expected_status_code?: number;
    tags?: string[];
    _row_number: number;
    _status: 'valid' | 'error' | 'duplicate';
    _errors?: string[];
    _existing_monitor_id?: number;
    _existing_monitor_name?: string;
}

export interface ImportPreviewResult {
    rows: ImportRow[];
    valid_count: number;
    error_count: number;
    duplicate_count: number;
}

export type DuplicateAction = 'skip' | 'update' | 'create';

export interface ImportFormData {
    rows: ImportRow[];
    duplicate_action: DuplicateAction;
    resolutions: Record<number, DuplicateAction>;
}
