import { Form, Head } from '@inertiajs/react';
import { FileText, Info, Pencil, Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import JobRequestController from '@/actions/App/Http/Controllers/JobRequests/JobRequestController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import EmergencyNotice from '@/components/job-requests/emergency-notice';
import JobRequestFields from '@/components/job-requests/job-request-fields';
import PhotoGallery from '@/components/job-requests/photo-gallery';
import StatusBadge from '@/components/job-requests/status-badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { formatDate, formatDateTime, formatFileSize } from '@/lib/format';
import { index, show } from '@/routes/job-requests';
import type {
    JobRequest,
    JobRequestFile,
    JobRequestMessage,
    JobRequestOptions,
} from '@/types';

type Props = {
    jobRequest: JobRequest;
    options: JobRequestOptions;
    maxPhotos: number;
};

export default function Show({ jobRequest, options, maxPhotos }: Props) {
    const [editing, setEditing] = useState(false);

    const files = jobRequest.files ?? [];
    const photos = files.filter((file) => file.is_image);
    const documents = files.filter((file) => !file.is_image);
    // Customers can only update the photos they uploaded themselves.
    const ownPhotos = photos.filter((photo) => photo.can.update).length;
    const remaining = jobRequest.can.update ? maxPhotos - ownPhotos : null;

    return (
        <>
            <Head title={jobRequest.title} />

            <div className="mx-auto w-full max-w-5xl space-y-8 p-4 md:p-6">
                {jobRequest.open_emergency && <EmergencyNotice />}

                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div className="min-w-0 space-y-2">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-xl font-semibold tracking-tight break-words">
                                {jobRequest.title}
                            </h1>
                            <StatusBadge status={jobRequest.status} />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {jobRequest.category.label} in {jobRequest.suburb} ·
                            Submitted {formatDate(jobRequest.created_at)}
                        </p>
                    </div>

                    {!editing && (
                        <div className="flex gap-2">
                            {jobRequest.can.update && (
                                <Button
                                    variant="outline"
                                    onClick={() => setEditing(true)}
                                >
                                    <Pencil />
                                    Edit details
                                </Button>
                            )}
                            {jobRequest.can.delete && (
                                <ConfirmDeleteDialog
                                    trigger={
                                        <Button variant="outline">
                                            <Trash2 />
                                            Delete
                                        </Button>
                                    }
                                    title="Delete this request?"
                                    description="We'll stop working on it and you won't be able to see it any more."
                                    action={JobRequestController.destroy.form(
                                        jobRequest.id,
                                    )}
                                    confirmLabel="Delete request"
                                />
                            )}
                        </div>
                    )}
                </header>

                {!jobRequest.can.update && (
                    <p className="flex items-start gap-2 rounded-lg border bg-muted/40 p-4 text-sm text-muted-foreground">
                        <Info
                            className="mt-0.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        This request is past the review stage, so its details
                        and photos can no longer be changed. Check the updates
                        below for the latest from our team.
                    </p>
                )}

                {editing ? (
                    <EditForm
                        jobRequest={jobRequest}
                        options={options}
                        onDone={() => setEditing(false)}
                    />
                ) : (
                    <div className="grid gap-8 lg:grid-cols-3">
                        <div className="space-y-8 lg:col-span-2">
                            <Section title="Updates">
                                <Updates messages={jobRequest.messages ?? []} />
                            </Section>

                            <Section title="Photos">
                                <PhotoGallery
                                    jobRequest={jobRequest}
                                    photos={photos}
                                    remaining={remaining}
                                    maxPhotos={maxPhotos}
                                />
                            </Section>

                            {documents.length > 0 && (
                                <Section title="Documents">
                                    <Documents documents={documents} />
                                </Section>
                            )}
                        </div>

                        <Section title="Details">
                            <Details jobRequest={jobRequest} />
                        </Section>
                    </div>
                )}
            </div>
        </>
    );
}

Show.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'My requests', href: index() },
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

function Updates({ messages }: { messages: JobRequestMessage[] }) {
    if (messages.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No updates yet. We'll post here as your request progresses.
            </p>
        );
    }

    return (
        <ol className="space-y-4 border-l pl-4">
            {messages.map((message) => (
                <li key={message.id} className="relative">
                    <span
                        className="absolute top-1.5 -left-[21px] size-2.5 rounded-full bg-cta ring-4 ring-background"
                        aria-hidden="true"
                    />
                    <time
                        dateTime={message.created_at ?? undefined}
                        className="text-xs text-muted-foreground"
                    >
                        {formatDateTime(message.created_at)}
                    </time>
                    <p className="mt-1 text-sm whitespace-pre-line">
                        {message.body}
                    </p>
                </li>
            ))}
        </ol>
    );
}

function Documents({ documents }: { documents: JobRequestFile[] }) {
    return (
        <ul className="divide-y rounded-lg border">
            {documents.map((document) => (
                <li key={document.id}>
                    <a
                        href={document.url}
                        className="flex items-center gap-3 p-3 transition-colors hover:bg-muted/50"
                    >
                        <FileText
                            className="size-5 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium">
                                {document.original_name ?? document.kind.label}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {document.kind.label}
                                {document.size !== null &&
                                    ` · ${formatFileSize(document.size)}`}
                                {document.caption && ` · ${document.caption}`}
                            </p>
                        </div>
                        <span className="text-sm text-muted-foreground">
                            Download
                        </span>
                    </a>
                </li>
            ))}
        </ul>
    );
}

function Details({ jobRequest }: { jobRequest: JobRequest }) {
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
    ];

    return (
        <div className="space-y-4 rounded-lg border p-4">
            <p className="text-sm whitespace-pre-line">
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
    jobRequest: JobRequest;
    options: JobRequestOptions;
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
                        Your email ({jobRequest.contact_email}) can't be
                        changed, because it links this request to your account.
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
