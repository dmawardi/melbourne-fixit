// Pinned to Melbourne so server-rendered and hydrated output always match.
const TIME_ZONE = 'Australia/Melbourne';

const dateFormatter = new Intl.DateTimeFormat('en-AU', {
    dateStyle: 'medium',
    timeZone: TIME_ZONE,
});

const dateTimeFormatter = new Intl.DateTimeFormat('en-AU', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: TIME_ZONE,
});

export function formatDate(value: string | null): string {
    return value ? dateFormatter.format(new Date(value)) : '';
}

export function formatDateTime(value: string | null): string {
    return value ? dateTimeFormatter.format(new Date(value)) : '';
}

export function formatFileSize(bytes: number | null): string {
    if (bytes === null) {
        return '';
    }

    if (bytes < 1024 * 1024) {
        return `${Math.max(1, Math.round(bytes / 1024))} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}
