import { formatDateTime } from '@/lib/format';
import type { JobRequestActivity } from '@/types';

// Logged values are stored as JSON, so anything that isn't text is left out of the summary.
function text(value: unknown): string {
    return typeof value === 'string' ? value : '';
}

// Summarises what an entry changed, using the details stored when it was logged.
function describe({ event, properties }: JobRequestActivity): string | null {
    switch (event.value) {
        case 'vendor_assigned':
            return properties.outside_categories
                ? `${text(properties.vendor_name)} (outside their usual job types)`
                : text(properties.vendor_name);
        case 'vendor_unassigned':
            return text(properties.vendor_name);
        case 'status_changed':
            return `${text(properties.from)} to ${text(properties.to)}`;
        case 'updated':
            return Object.keys(properties.changes ?? {})
                .join(', ')
                .replaceAll('_', ' ');
        case 'file_uploaded':
        case 'file_updated':
        case 'file_deleted':
            return text(properties.name) || null;
        case 'message_posted':
        case 'message_deleted':
            return text(properties.excerpt);
        default:
            return null;
    }
}

export default function ActivityTimeline({
    activities,
}: {
    activities: JobRequestActivity[];
}) {
    if (activities.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">No activity yet.</p>
        );
    }

    return (
        <ol className="space-y-4 border-l pl-4">
            {activities.map((activity) => {
                const details = describe(activity);

                return (
                    <li key={activity.id} className="relative">
                        <span
                            className="absolute top-1.5 -left-[21px] size-2.5 rounded-full bg-orange-600 ring-4 ring-background"
                            aria-hidden="true"
                        />
                        <p className="text-sm font-medium">
                            {activity.event.label}
                        </p>
                        {details && (
                            <p className="text-sm break-words text-muted-foreground">
                                {details}
                            </p>
                        )}
                        <p className="text-xs text-muted-foreground">
                            <time dateTime={activity.created_at ?? undefined}>
                                {formatDateTime(activity.created_at)}
                            </time>
                            {' · '}
                            {activity.user?.name ?? 'System'}
                        </p>
                    </li>
                );
            })}
        </ol>
    );
}
