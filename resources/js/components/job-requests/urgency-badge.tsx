import { Badge } from '@/components/ui/badge';
import type { Option } from '@/types';

export default function UrgencyBadge({ urgency }: { urgency: Option }) {
    if (urgency.value !== 'emergency') {
        return null;
    }

    return <Badge variant="destructive">Emergency</Badge>;
}
