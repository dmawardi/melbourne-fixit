import { Form } from '@inertiajs/react';
import { FileText, Trash2 } from 'lucide-react';
import VendorFileController from '@/actions/App/Http/Controllers/Admin/VendorFileController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { formatDate, formatFileSize } from '@/lib/format';
import type { Option, Vendor, VendorFile, VendorOptions } from '@/types';

type Props = {
    vendor: Vendor;
    files: VendorFile[];
    // The documents to collect from this vendor, in display order.
    documents: Option[];
    options: VendorOptions;
};

export default function VendorFiles({
    vendor,
    files,
    documents,
    options,
}: Props) {
    const documentValues = documents.map((document) => document.value);
    // Files with no document, or for a document the vendor no longer needs.
    const otherFiles = files.filter(
        (file) =>
            file.document === null || !documentValues.includes(file.document),
    );
    const labels = Object.fromEntries(
        options.documents.map((document) => [document.value, document.label]),
    );

    return (
        <section
            aria-labelledby="vendor-files-heading"
            className="max-w-2xl space-y-4 border-t pt-6"
        >
            <div className="space-y-2">
                <h2 id="vendor-files-heading" className="font-medium">
                    Document copies
                </h2>
                <p className="text-sm text-muted-foreground">
                    Upload certificates of currency, licence cards and
                    registration certificates. Only admins can see them. Keep
                    older copies, as they show what was on file when work was
                    assigned.
                </p>
            </div>

            <Form
                {...VendorFileController.store.form(vendor.id)}
                options={{ preserveScroll: true }}
                resetOnSuccess
                className="space-y-3 rounded-lg border p-4"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="vendor-files">Files</Label>
                            <Input
                                id="vendor-files"
                                name="files[]"
                                type="file"
                                multiple
                                required
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                aria-invalid={!!errors.files}
                            />
                            <InputError
                                message={errors.files ?? errors['files.0']}
                            />
                        </div>
                        <div className="flex flex-wrap items-end justify-between gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="vendor-file-document">
                                    Document
                                </Label>
                                <NativeSelect
                                    id="vendor-file-document"
                                    name="document"
                                    aria-invalid={!!errors.document}
                                >
                                    {documents.map((document) => (
                                        <option
                                            key={document.value}
                                            value={document.value}
                                        >
                                            {document.label}
                                        </option>
                                    ))}
                                    <option value="">Other</option>
                                </NativeSelect>
                                <InputError message={errors.document} />
                            </div>
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Upload
                            </Button>
                        </div>
                    </>
                )}
            </Form>

            <div className="divide-y rounded-lg border">
                {documents.map((document) => (
                    <FileGroup
                        key={document.value}
                        title={document.label}
                        vendor={vendor}
                        files={files.filter(
                            (file) => file.document === document.value,
                        )}
                        labels={labels}
                    />
                ))}
                {otherFiles.length > 0 && (
                    <FileGroup
                        title="Other"
                        vendor={vendor}
                        files={otherFiles}
                        labels={labels}
                    />
                )}
            </div>
        </section>
    );
}

function FileGroup({
    title,
    vendor,
    files,
    labels,
}: {
    title: string;
    vendor: Vendor;
    files: VendorFile[];
    labels: Record<string, string>;
}) {
    return (
        <div className="space-y-2 p-4">
            <h3 className="text-sm font-medium">{title}</h3>
            {files.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No copy uploaded.
                </p>
            ) : (
                <ul className="space-y-2">
                    {files.map((file) => (
                        <li key={file.id} className="flex items-center gap-3">
                            <FileText
                                className="size-5 shrink-0 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <div className="min-w-0 flex-1 space-y-0.5">
                                <a
                                    href={file.url}
                                    target={
                                        file.mime_type?.startsWith('image/')
                                            ? '_blank'
                                            : undefined
                                    }
                                    rel="noreferrer"
                                    className="block truncate text-sm font-medium hover:underline"
                                >
                                    {file.original_name ?? 'Untitled file'}
                                </a>
                                <p className="text-xs text-muted-foreground">
                                    {[
                                        file.document &&
                                        labels[file.document] !== title
                                            ? labels[file.document]
                                            : null,
                                        formatDate(file.created_at),
                                        formatFileSize(file.size),
                                        file.uploader,
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')}
                                </p>
                            </div>
                            <ConfirmDeleteDialog
                                trigger={
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`Delete ${file.original_name ?? 'file'}`}
                                    >
                                        <Trash2 />
                                    </Button>
                                }
                                title="Delete this file?"
                                description="It will be removed permanently."
                                action={VendorFileController.destroy.form([
                                    vendor.id,
                                    file.id,
                                ])}
                            />
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
