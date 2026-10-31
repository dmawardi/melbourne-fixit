import { Form, Head } from '@inertiajs/react';
import { Pencil, Phone, Trash2, TriangleAlert } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import JobRequestController from '@/actions/App/Http/Controllers/Admin/JobRequestController';
import ActivityTimeline from '@/components/admin/activity-timeline';
import JobRequestFiles from '@/components/admin/job-request-files';
import JobRequestMessages from '@/components/admin/job-request-messages';
import JobRequestVendor from '@/components/admin/job-request-vendor';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import InputError from '@/components/input-error';
import JobRequestFields from '@/components/job-requests/job-request-fields';
import StatusBadge from '@/components/job-requests/status-badge';
import UrgencyBadge from '@/components/job-requests/urgency-badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatDate } from '@/lib/format';
import { index, show } from '@/routes/admin/job-requests';
import type {
    AdminJobRequest,
    AdminJobRequestOptions,
    VendorChoice,
} from '@/types';

type Props = {
    jobRequest: AdminJobRequest;
    vendors: VendorChoice[];
    options: AdminJobRequestOptions;
};

export default function Show({ jobRequest, vendors, options }: Props) {
    const [editing, setEditing] = useState(false);

    return (
        <>
            <Head title={jobRequest.title} />

            <div className="mx-auto w-full max-w-6xl space-y-8 p-4 md:p-6">
                {jobRequest.open_emergency && (
                    <div
                        role="alert"
                        className="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-100"
                    >
                        <p className="flex items-center gap-2 font-medium">
                            <TriangleAlert
                                className="size-4 shrink-0"
                                aria-hidden="true"
                            />
                            Emergency with no vendor assigned
                        </p>
                        {jobRequest.contact_phone ? (
                            <a
                                href={`tel:${jobRequest.contact_phone.replace(/[^\d+]/g, '')}`}
                                className="inline-flex items-center gap-1.5 font-medium underline underline-offset-4"
                            >
                                <Phone className="size-4" aria-hidden="true" />
                                Call {jobRequest.contact_name} on{' '}
                                {jobRequest.contact_phone}
                            </a>
                        ) : (
                            <a
                                href={`mailto:${jobRequest.contact_email}`}
                                className="underline underline-offset-4"
                            >
                                No phone number given. Email{' '}
                                {jobRequest.contact_email}
                            </a>
                        )}
                    </div>
                )}

                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div className="min-w-0 space-y-2">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-xl font-semibold tracking-tight break-words">
                                {jobRequest.title}
                            </h1>
                            <StatusBadge status={jobRequest.status} />
                            <UrgencyBadge urgency={jobRequest.urgency} />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {jobRequest.category.label} in {jobRequest.suburb} ·
                            Submitted {formatDate(jobRequest.created_at)}
                        </p>
                    </div>

                    {!editing && (
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                onClick={() => setEditing(true)}
                            >
                                <Pencil />
                                Edit details
                            </Button>
                            <ConfirmDeleteDialog
                                trigger={
                                    <Button variant="outline">
                                        <Trash2 />
                                        Delete
                                    </Button>
                                }
                                title="Delete this request?"
                                description="It will disappear from the admin list and from the customer's account."
                                action={JobRequestController.destroy.form(
                                    jobRequest.id,
                                )}
                                confirmLabel="Delete request"
                            />
                        </div>
                    )}
                </header>

                {editing ? (
                    <EditForm
                        jobRequest={jobRequest}
                        options={options}
                        onDone={() => setEditing(false)}
                    />
                ) : (
                    <div className="grid gap-8 lg:grid-cols-3">
                        <div className="min-w-0 space-y-8 lg:col-span-2">
                            <Section title="Status and notes">
                                <StatusForm
                                    jobRequest={jobRequest}
                                    options={options}
                                />
                            </Section>

                            <Section title="Updates">
                                <JobRequestMessages
                                    jobRequest={jobRequest}
                                    visibilities={options.visibilities}
                                />
                            </Section>

                            <Section title="Files">
                                <JobRequestFiles
                                    jobRequest={jobRequest}
                                    options={options}
                                />
                            </Section>
                        </div>

                        <div className="min-w-0 space-y-8">
                            <Section title="Vendor">
                                <JobRequestVendor
                                    jobRequest={jobRequest}
                                    vendors={vendors}
                                />
                            </Section>

                            <Section title="Details">
                                <Details jobRequest={jobRequest} />
                            </Section>

                            <Section title="Activity">
                                <ActivityTimeline
                                    activities={jobRequest.activities ?? []}
                                />
                            </Section>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

Show.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Requests', href: index() },
        { title: props.jobRequest.title, href: show(props.jobRequest.id) },
    ],
});

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="space-y-4">
            <h2 className="text-lg font-medium">{title}</h2>
            {children}
        </section>
    );
}

function StatusForm({
    jobRequest,
    options,
}: {
    jobRequest: AdminJobRequest;
    options: AdminJobRequestOptions;
}) {
    return (
        <Form
            {...JobRequestController.update.form(jobRequest.id)}
            options={{ preserveScroll: true }}
            className="space-y-3 rounded-lg border p-4"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="status">Status</Label>
                        <NativeSelect
                            id="status"
                            name="status"
                            required
                            defaultValue={jobRequest.status.value}
                            aria-invalid={!!errors.status}
                        >
                            {options.statuses.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </NativeSelect>
                        <InputError message={errors.status} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="admin_notes">Notes (admin only)</Label>
                        <Textarea
                            id="admin_notes"
                            name="admin_notes"
                            rows={4}
                            maxLength={20000}
                            defaultValue={jobRequest.admin_notes ?? ''}
                            aria-invalid={!!errors.admin_notes}
                        />
                        <InputError message={errors.admin_notes} />
                    </div>
                    <Button type="submit" disabled={processing}>
                        {processing && <Spinner />}
                        Save
                    </Button>
                </>
            )}
        </Form>
    );
}

function Details({ jobRequest }: { jobRequest: AdminJobRequest }) {
    const address = [
        jobRequest.address_line,
        `${jobRequest.suburb} VIC ${jobRequest.postcode}`,
    ]
        .filter(Boolean)
        .join(', ');

    const rows: [string, ReactNode][] = [
        ['Type of job', jobRequest.category.label],
        ['How soon', jobRequest.urgency.label],
        ['Budget', jobRequest.budget_range?.label ?? 'Not sure'],
        ['Property', jobRequest.property_type?.label ?? 'Not given'],
        ['Address', address],
        ['Contact', jobRequest.contact_name],
        ['Email', jobRequest.contact_email],
        ['Phone', jobRequest.contact_phone ?? 'Not given'],
        ['Account', jobRequest.owner?.name ?? 'Not linked'],
    ];

    return (
        <div className="space-y-4 rounded-lg border p-4">
            <p className="text-sm break-words whitespace-pre-line">
                {jobRequest.description}
            </p>
            <dl className="space-y-3 border-t pt-4 text-sm">
                {rows.map(([label, value]) => (
                    <div key={label}>
                        <dt className="text-muted-foreground">{label}</dt>
                        <dd className="break-words">{value}</dd>
                    </div>
                ))}
            </dl>
        </div>
    );
}

function EditForm({
    jobRequest,
    options,
    onDone,
}: {
    jobRequest: AdminJobRequest;
    options: AdminJobRequestOptions;
    onDone: () => void;
}) {
    return (
        <Form
            {...JobRequestController.update.form(jobRequest.id)}
            options={{ preserveScroll: true }}
            onSuccess={onDone}
            className="max-w-3xl space-y-10"
        >
            {({ processing, errors }) => (
                <>
                    <JobRequestFields
                        mode="edit"
                        options={options}
                        errors={errors}
                        defaults={{
                            ...jobRequest,
                            category: jobRequest.category.value,
                            urgency: jobRequest.urgency.value,
                            budget_range: jobRequest.budget_range?.value,
                            property_type: jobRequest.property_type?.value,
                        }}
                    />
                    <p className="text-sm text-muted-foreground">
                        The contact email ({jobRequest.contact_email}) can't be
                        changed, because it links this request to an account.
                    </p>
                    <div className="flex gap-2 border-t pt-6">
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Save changes
                        </Button>
                        <Button type="button" variant="ghost" onClick={onDone}>
                            Cancel
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
