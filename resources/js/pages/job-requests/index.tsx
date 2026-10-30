import { Head, Link } from '@inertiajs/react';
import { ChevronRight, ClipboardList, Image, MapPin, Plus } from 'lucide-react';
import Heading from '@/components/heading';
import StatusBadge from '@/components/job-requests/status-badge';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { create, index, show } from '@/routes/job-requests';
import type { JobRequest, Paginated } from '@/types';

export default function Index({
    jobRequests,
}: {
    jobRequests: Paginated<JobRequest>;
}) {
    const { data, links, meta } = jobRequests;

    return (
        <>
            <Head title="My requests" />

            <div className="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            My requests
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Add photos, check quotes and follow updates on your
                            jobs.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            New request
                        </Link>
                    </Button>
                </div>

                {data.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center">
                        <ClipboardList
                            className="mx-auto size-10 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <Heading
                            variant="small"
                            title="No requests yet"
                            description="Requests you submit with this account's email address appear here."
                        />
                        <Button className="mt-4" asChild>
                            <Link href={create()}>Request a quote</Link>
                        </Button>
                    </div>
                ) : (
                    <ul className="divide-y rounded-xl border">
                        {data.map((jobRequest) => (
                            <li key={jobRequest.id}>
                                <Link
                                    href={show(jobRequest.id)}
                                    className="flex items-center gap-4 p-4 transition-colors hover:bg-muted/50"
                                >
                                    <div className="min-w-0 flex-1 space-y-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h2 className="truncate font-medium">
                                                {jobRequest.title}
                                            </h2>
                                            <StatusBadge
                                                status={jobRequest.status}
                                            />
                                        </div>
                                        <p className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                                            <span>
                                                {jobRequest.category.label}
                                            </span>
                                            <span className="inline-flex items-center gap-1">
                                                <MapPin
                                                    className="size-3.5"
                                                    aria-hidden="true"
                                                />
                                                {jobRequest.suburb}
                                            </span>
                                            <span className="inline-flex items-center gap-1">
                                                <Image
                                                    className="size-3.5"
                                                    aria-hidden="true"
                                                />
                                                {jobRequest.files_count ?? 0}{' '}
                                                {jobRequest.files_count === 1
                                                    ? 'file'
                                                    : 'files'}
                                            </span>
                                            <span>
                                                Submitted{' '}
                                                {formatDate(
                                                    jobRequest.created_at,
                                                )}
                                            </span>
                                        </p>
                                    </div>
                                    <ChevronRight
                                        className="size-4 shrink-0 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination links={links} meta={meta} />
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [{ title: 'My requests', href: index() }],
};
