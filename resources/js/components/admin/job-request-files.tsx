import { Form } from '@inertiajs/react';
import { FileText, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import JobRequestFileController from '@/actions/App/Http/Controllers/Admin/JobRequestFileController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { formatFileSize } from '@/lib/format';
import type {
    AdminJobRequest,
    AdminJobRequestFile,
    AdminJobRequestOptions,
    Option,
} from '@/types';

type Props = {
    jobRequest: AdminJobRequest;
    options: AdminJobRequestOptions;
};

export default function JobRequestFiles({ jobRequest, options }: Props) {
    const files = jobRequest.files ?? [];

    return (
        <div className="space-y-4">
            <Form
                {...JobRequestFileController.store.form(jobRequest.id)}
                options={{ preserveScroll: true }}
                resetOnSuccess
                className="space-y-3 rounded-lg border p-4"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="files">Upload files</Label>
                            <Input
                                id="files"
                                name="files[]"
                                type="file"
                                multiple
                                required
                                aria-invalid={!!errors.files}
                            />
                            <InputError
                                message={errors.files ?? errors['files.0']}
                            />
                        </div>
                        <div className="flex flex-wrap items-end justify-between gap-3">
                            <FileSelects
                                idPrefix="new-file"
                                options={options}
                                errors={errors}
                            />
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Upload
                            </Button>
                        </div>
                    </>
                )}
            </Form>

            {files.length === 0 ? (
                <p className="text-sm text-muted-foreground">No files yet.</p>
            ) : (
                <ul className="divide-y rounded-lg border">
                    {files.map((file) => (
                        <FileRow
                            key={file.id}
                            jobRequest={jobRequest}
                            file={file}
                            options={options}
                        />
                    ))}
                </ul>
            )}
        </div>
    );
}

function FileRow({
    jobRequest,
    file,
    options,
}: Props & { file: AdminJobRequestFile }) {
    const [editing, setEditing] = useState(false);

    if (editing) {
        return (
            <li className="p-3">
                <Form
                    {...JobRequestFileController.update.form([
                        jobRequest.id,
                        file.id,
                    ])}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setEditing(false)}
                    className="space-y-3"
                >
                    {({ processing, errors }) => (
                        <>
                            <p className="truncate text-sm font-medium">
                                {file.original_name ?? file.kind.label}
                            </p>
                            <FileSelects
                                idPrefix={`file-${file.id}`}
                                options={options}
                                errors={errors}
                                file={file}
                            />
                            <div className="grid gap-2">
                                <Label htmlFor={`file-${file.id}-caption`}>
                                    Caption
                                </Label>
                                <Input
                                    id={`file-${file.id}-caption`}
                                    name="caption"
                                    maxLength={255}
                                    defaultValue={file.caption ?? ''}
                                    aria-invalid={!!errors.caption}
                                />
                                <InputError message={errors.caption} />
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    Save
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => setEditing(false)}
                                >
                                    Cancel
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </li>
        );
    }

    return (
        <li className="flex items-center gap-3 p-3">
            <FileText
                className="size-5 shrink-0 text-muted-foreground"
                aria-hidden="true"
            />
            <div className="min-w-0 flex-1 space-y-1">
                <a
                    href={file.url}
                    className="block truncate text-sm font-medium hover:underline"
                >
                    {file.original_name ?? file.kind.label}
                </a>
                <p className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                    <Badge variant="outline">{file.visibility.label}</Badge>
                    {file.kind.label}
                    {file.size !== null && ` · ${formatFileSize(file.size)}`}
                    {file.uploader && ` · ${file.uploader.name}`}
                    {file.caption && ` · ${file.caption}`}
                </p>
            </div>
            <Button
                variant="ghost"
                size="icon"
                aria-label="Edit file"
                onClick={() => setEditing(true)}
            >
                <Pencil />
            </Button>
            <ConfirmDeleteDialog
                trigger={
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Delete file"
                    >
                        <Trash2 />
                    </Button>
                }
                title="Delete this file?"
                description="It will be removed permanently."
                action={JobRequestFileController.destroy.form([
                    jobRequest.id,
                    file.id,
                ])}
            />
        </li>
    );
}

function FileSelects({
    idPrefix,
    options,
    errors,
    file,
}: {
    idPrefix: string;
    options: AdminJobRequestOptions;
    errors: Partial<Record<string, string>>;
    file?: AdminJobRequestFile;
}) {
    return (
        <div className="flex flex-wrap gap-3">
            <SelectField
                id={`${idPrefix}-kind`}
                name="kind"
                label="Type"
                options={options.fileKinds}
                defaultValue={file?.kind.value}
                error={errors.kind}
            />
            <SelectField
                id={`${idPrefix}-visibility`}
                name="visibility"
                label="Visible to"
                options={options.visibilities}
                defaultValue={file?.visibility.value ?? 'internal'}
                error={errors.visibility}
            />
        </div>
    );
}

function SelectField({
    id,
    name,
    label,
    options,
    defaultValue,
    error,
}: {
    id: string;
    name: string;
    label: string;
    options: Option[];
    defaultValue?: string;
    error?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            <NativeSelect
                id={id}
                name={name}
                required
                defaultValue={defaultValue}
                aria-invalid={!!error}
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </NativeSelect>
            <InputError message={error} />
        </div>
    );
}
