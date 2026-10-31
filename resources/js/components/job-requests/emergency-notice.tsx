import { usePage } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';

export default function EmergencyNotice() {
    const { site } = usePage().props;

    return (
        <div className="flex gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-100">
            <TriangleAlert
                className="mt-0.5 size-4 shrink-0"
                aria-hidden="true"
            />
            <div className="space-y-2">
                <p className="font-medium">
                    If there's immediate danger, such as a gas leak, exposed
                    wiring or flooding, call 000 first.
                </p>
                <p>{site.emergencyResponse}</p>
                {site.phone && (
                    <p>
                        Need us in a hurry? Call{' '}
                        <a
                            href={`tel:${site.phone.replace(/[^\d+]/g, '')}`}
                            className="font-medium underline underline-offset-4"
                        >
                            {site.phone}
                        </a>
                        .
                    </p>
                )}
            </div>
        </div>
    );
}
