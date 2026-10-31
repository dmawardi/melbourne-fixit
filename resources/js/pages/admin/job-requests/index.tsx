import { Form, Head, Link } from '@inertiajs/react';
import { ClipboardList } from 'lucide-react';
import JobRequestController from '@/actions/App/Http/Controllers/Admin/JobRequestController';
import Heading from '@/components/heading';
import StatusBadge from '@/components/job-requests/status-badge';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { formatDateTime } from '@/lib/format';
import { index, show } from '@/routes/admin/job-requests';
import type { AdminJobRequest, Option, Paginated } from '@/types';

type Props = {
    jobRequests: Paginated<AdminJobRequest>;
    filters: {
        search: string;
        status: string | null;
        category: string | null;
        claimed: string | null;
        vendor: string | null;
    };
    options: {
        statuses: Option[];
        categories: Option[];
    };
};

export default function Index({ jobRequests, filters, options }: Props) {
    const { data, links, meta } = jobRequests;

    return (
        <>
            <Head title="Requests" />

            <div className="mx-auto w-full max-w-6xl space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight">
                        Requests
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        All job requests, most recently active first.
                    </p>
                </div>

                <Form
                    {...JobRequestController.index.form()}
                    className="flex flex-wrap items-end gap-3"
                >
                    <div className="grid min-w-48 flex-1 gap-2">
                        <Label htmlFor="search">Search</Label>
                        <Input
                            id="search"
                            name="search"
                            type="search"
                            placeholder="Title, suburb, postcode, name or email"
                            defaultValue={filters.search}
                        />
                    </div>
                    <FilterSelect
                        id="status"
                        label="Status"
                        options={options.statuses}
                        value={filters.status}
                    />
                    <FilterSelect
                        id="category"
                        label="Type of job"
                        options={options.categories}
                        value={filters.category}
                    />
                    <FilterSelect
                        id="claimed"
                        label="Linked to account"
                        options={[
                            { value: 'yes', label: 'Yes' },
                            { value: 'no', label: 'No' },
                        ]}
                        value={filters.claimed}
                    />
                    <FilterSelect
                        id="vendor"
                        label="Vendor"
                        options={[
                            { value: 'needs_vendor', label: 'Needs a vendor' },
                            { value: 'assigned', label: 'Assigned' },
                        ]}
                        value={filters.vendor}
                    />
                    <Button type="submit" variant="outline">
                        Filter
                    </Button>
                </Form>

                {data.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center">
                        <ClipboardList
                            className="mx-auto size-10 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <Heading
                            variant="small"
                            title="No requests found"
                            description="New requests appear here, and you can change the filters to see others."
                        />
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b text-muted-foreground">
                                <tr>
                                    <th className="p-3 font-medium">Request</th>
                                    <th className="p-3 font-medium">Status</th>
                                    <th className="p-3 font-medium">Vendor</th>
                                    <th className="p-3 font-medium">
                                        Latest activity
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {data.map((jobRequest) => (
                                    <tr key={jobRequest.id}>
                                        <td className="p-3">
                                            <Link
                                                href={show(jobRequest.id)}
                                                className="font-medium hover:underline"
                                            >
                                                {jobRequest.title}
                                            </Link>
                                            <p className="text-muted-foreground">
                                                {jobRequest.category.label} ·{' '}
                                                {jobRequest.suburb} ·{' '}
                                                {jobRequest.contact_name}
                                            </p>
                                        </td>
                                        <td className="p-3">
                                            <StatusBadge
                                                status={jobRequest.status}
                                            />
                                        </td>
                                        <td className="p-3">
                                            {jobRequest.vendor?.name ?? (
                                                <span className="text-muted-foreground">
                                                    Unassigned
                                                </span>
                                            )}
                                        </td>
                                        <td className="p-3">
                                            {jobRequest.latest_activity?.event
                                                .label ?? 'None'}
                                            <p className="text-xs text-muted-foreground">
                                                {formatDateTime(
                                                    jobRequest.last_activity_at,
                                                )}
                                            </p>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={links} meta={meta} />
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [{ title: 'Requests', href: index() }],
};

function FilterSelect({
    id,
    label,
    options,
    value,
}: {
    id: string;
    label: string;
    options: Option[];
    value: string | null;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            <NativeSelect id={id} name={id} defaultValue={value ?? ''}>
                <option value="">All</option>
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </NativeSelect>
        </div>
    );
}
