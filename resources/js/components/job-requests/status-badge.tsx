import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { Option } from '@/types';

const colours: Record<string, string> = {
    submitted: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
    reviewing:
        'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    quoted: 'bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300',
    scheduled: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
    completed:
        'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
    cancelled:
        'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
};

export default function StatusBadge({
    status,
    className,
}: {
    status: Option;
    className?: string;
}) {
    return (
        <Badge
            variant="secondary"
            className={cn(
                'border-transparent',
                colours[status.value],
                className,
            )}
        >
            {status.label}
        </Badge>
    );
}
