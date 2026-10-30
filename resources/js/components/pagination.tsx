import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

export default function Pagination({
    links,
    meta,
}: Pick<Paginated<unknown>, 'links' | 'meta'>) {
    if (meta.last_page <= 1) {
        return null;
    }

    return (
        <nav
            aria-label="Pagination"
            className="flex items-center justify-between text-sm"
        >
            <p className="text-muted-foreground">
                Page {meta.current_page} of {meta.last_page}
            </p>
            <div className="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    disabled={!links.prev}
                    asChild={!!links.prev}
                >
                    {links.prev ? (
                        <Link href={links.prev} rel="prev">
                            Previous
                        </Link>
                    ) : (
                        'Previous'
                    )}
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    disabled={!links.next}
                    asChild={!!links.next}
                >
                    {links.next ? (
                        <Link href={links.next} rel="next">
                            Next
                        </Link>
                    ) : (
                        'Next'
                    )}
                </Button>
            </div>
        </nav>
    );
}
