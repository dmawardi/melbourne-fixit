import { Head, Link } from '@inertiajs/react';
import { CircleCheck } from 'lucide-react';
import type { ReactNode } from 'react';
import VendorDocumentIssues from '@/components/admin/vendor-document-issues';
import VendorDocumentStatus from '@/components/admin/vendor-document-status';
import StatusBadge from '@/components/job-requests/status-badge';
import { Badge } from '@/components/ui/badge';
import { formatDate } from '@/lib/format';
import { index as dashboard } from '@/routes/admin';
import { index as requests, show } from '@/routes/admin/job-requests';
import { edit, index as vendors } from '@/routes/admin/vendors';
import type { AdminJobRequest, Vendor } from '@/types';

type RequestSection = {
    total: number;
    items: AdminJobRequest[];
};

type Props = {
    newRequests: RequestSection;
    unassignedRequests: RequestSection;
    vendorDocuments: {
        action_needed: number;
        expiring_soon: number;
        items: Vendor[];
    };
};

export default function Dashboard({
    newRequests,
    unassignedRequests,
    vendorDocuments,
}: Props) {
    const newRequestsHref = requests({ query: { status: 'submitted' } });
    const unassignedHref = requests({ query: { vendor: 'needs_vendor' } });

    return (
        <>
            <Head title="Dashboard" />

            <div className="mx-auto w-full max-w-6xl space-y-8 p-4 md:p-6">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight">
                        Dashboard
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        What needs your attention, most urgent first.
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <SummaryCard
                        href={newRequestsHref}
                        label="New requests"
                        value={newRequests.total}
                    />
                    <SummaryCard
                        href={unassignedHref}
                        label="Needs a vendor"
                        value={unassignedRequests.total}
                    />
                    <SummaryCard
                        href={vendors()}
                        label="Vendor documents"
                        value={vendorDocuments.action_needed}
                        detail={`action needed, plus ${vendorDocuments.expiring_soon} expiring soon`}
                    />
                </div>

                <Section
                    title="New requests"
                    description="Submitted and waiting for review."
                    total={newRequests.total}
                    href={newRequestsHref}
                    emptyMessage="No new requests to review."
                >
                    {newRequests.items.map((jobRequest) => (
                        <RequestRow
                            key={jobRequest.id}
                            jobRequest={jobRequest}
                        />
                    ))}
                </Section>

                <Section
                    title="Needs a vendor"
                    description="Reviewed and still open, with no vendor assigned."
                    total={unassignedRequests.total}
                    href={unassignedHref}
                    emptyMessage="Every reviewed request has a vendor."
                >
                    {unassignedRequests.items.map((jobRequest) => (
                        <RequestRow
                            key={jobRequest.id}
                            jobRequest={jobRequest}
                        />
                    ))}
                </Section>

                <Section
                    title="Vendor documents"
                    description="Missing, expired or expiring licences and insurance."
                    total={
                        vendorDocuments.action_needed +
                        vendorDocuments.expiring_soon
                    }
                    href={vendors()}
                    emptyMessage="All vendor documents are up to date."
                >
                    {vendorDocuments.items.map((vendor) => (
                        <li
                            key={vendor.id}
                            className="flex flex-wrap items-start justify-between gap-2 p-4"
                        >
                            <div className="min-w-0 space-y-1">
                                <Link
                                    href={edit(vendor.id)}
                                    className="font-medium break-words hover:underline"
                                >
                                    {vendor.name}
                                </Link>
                                <VendorDocumentIssues
                                    issues={vendor.document_issues}
                                />
                            </div>
                            <VendorDocumentStatus
                                status={vendor.document_status}
                            />
                        </li>
                    ))}
                </Section>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};

function SummaryCard({
    href,
    label,
    value,
    detail,
}: {
    href: Parameters<typeof Link>[0]['href'];
    label: string;
    value: number;
    detail?: string;
}) {
    return (
        <Link
            href={href}
            className="rounded-xl border bg-card p-4 transition-colors hover:border-primary"
        >
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className="mt-1 text-3xl font-semibold">{value}</p>
            {detail && (
                <p className="mt-1 text-xs text-muted-foreground">{detail}</p>
            )}
        </Link>
    );
}

function Section({
    title,
    description,
    total,
    href,
    emptyMessage,
    children,
}: {
    title: string;
    description: string;
    total: number;
    href: Parameters<typeof Link>[0]['href'];
    emptyMessage: string;
    children: ReactNode;
}) {
    return (
        <section className="space-y-3">
            <div className="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 className="font-medium">{title}</h2>
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                </div>
                {total > 0 && (
                    <Link
                        href={href}
                        className="text-sm font-medium underline underline-offset-4"
                    >
                        View all ({total})
                    </Link>
                )}
            </div>
            {total === 0 ? (
                <p className="flex items-center gap-2 rounded-xl border border-dashed p-4 text-sm text-muted-foreground">
                    <CircleCheck className="size-4" aria-hidden="true" />
                    {emptyMessage}
                </p>
            ) : (
                <ul className="divide-y rounded-xl border bg-card">
                    {children}
                </ul>
            )}
        </section>
    );
}

function RequestRow({ jobRequest }: { jobRequest: AdminJobRequest }) {
    return (
        <li className="flex flex-wrap items-start justify-between gap-2 p-4">
            <div className="min-w-0">
                <Link
                    href={show(jobRequest.id)}
                    className="font-medium break-words hover:underline"
                >
                    {jobRequest.title}
                </Link>
                <p className="text-sm text-muted-foreground">
                    {jobRequest.category.label} · {jobRequest.suburb} ·
                    Submitted {formatDate(jobRequest.created_at)}
                </p>
            </div>
            <div className="flex flex-wrap items-center gap-2">
                {jobRequest.urgency.value === 'emergency' && (
                    <Badge variant="destructive">Emergency</Badge>
                )}
                <StatusBadge status={jobRequest.status} />
            </div>
        </li>
    );
}
