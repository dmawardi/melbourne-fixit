import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { Option } from '@/types';

const colours: Record<string, string> = {
    action_needed: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
    expiring_soon:
        'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    up_to_date:
        'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
};

export default function VendorDocumentStatus({ status }: { status: Option }) {
    return (
        <Badge
            variant="secondary"
            className={cn('border-transparent', colours[status.value])}
        >
            {status.label}
        </Badge>
    );
}
