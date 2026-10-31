import { Form } from '@inertiajs/react';
import { ExternalLink, FileCheck } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { Vendor, VendorDocumentRequirement, VendorOptions } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

type DocumentField = {
    name: Extract<
        keyof Vendor,
        | 'abn'
        | `public_liability_${string}`
        | `workcover_${string}`
        | `builder_${string}`
        | `plumbing_${string}`
        | `electrical_${string}`
    >;
    label: string;
    type?: 'date' | 'number';
};

// The details to record for each document, keyed by VendorDocument value.
const documentFields: Record<string, DocumentField[]> = {
    business: [{ name: 'abn', label: 'ABN' }],
    public_liability: [
        { name: 'public_liability_insurer', label: 'Insurer' },
        { name: 'public_liability_policy_number', label: 'Policy number' },
        {
            name: 'public_liability_cover',
            label: 'Cover amount ($)',
            type: 'number',
        },
        {
            name: 'public_liability_expires_on',
            label: 'Expiry date',
            type: 'date',
        },
    ],
    workcover: [
        { name: 'workcover_policy_number', label: 'Policy number' },
        { name: 'workcover_expires_on', label: 'Expiry date', type: 'date' },
    ],
    builder_registration: [
        { name: 'builder_registration_number', label: 'Registration number' },
        {
            name: 'builder_registration_expires_on',
            label: 'Expiry date',
            type: 'date',
        },
    ],
    plumbing_licence: [
        { name: 'plumbing_licence_number', label: 'Licence number' },
        {
            name: 'plumbing_licence_expires_on',
            label: 'Expiry date',
            type: 'date',
        },
    ],
    electrical_registration: [
        { name: 'electrical_registration_number', label: 'REC number' },
        {
            name: 'electrical_registration_expires_on',
            label: 'Expiry date',
            type: 'date',
        },
    ],
};

type Props = {
    action: RouteFormDefinition<'post'>;
    options: VendorOptions;
    vendor?: Vendor;
    submitLabel: string;
};

export default function VendorForm({
    action,
    options,
    vendor,
    submitLabel,
}: Props) {
    const [type, setType] = useState(
        vendor?.type.value ?? options.types[0].value,
    );
    const [categories, setCategories] = useState(
        vendor?.categories.map((category) => category.value) ?? [],
    );
    // General vendors cover every job type, so they need every document.
    const documents = options.documents.filter(
        (document) =>
            type !== 'specialist' ||
            document.categories === null ||
            document.categories.some((category) =>
                categories.includes(category),
            ),
    );

    return (
        <Form {...action} className="max-w-2xl space-y-6">
            {({ processing, errors }) => (
                <>
                    <Field id="name" label="Name" error={errors.name}>
                        <Input
                            id="name"
                            name="name"
                            required
                            maxLength={255}
                            defaultValue={vendor?.name ?? ''}
                            aria-invalid={!!errors.name}
                        />
                    </Field>

                    <Field id="type" label="Type" error={errors.type}>
                        <NativeSelect
                            id="type"
                            name="type"
                            required
                            value={type}
                            onChange={(event) => setType(event.target.value)}
                            aria-invalid={!!errors.type}
                        >
                            {options.types.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </NativeSelect>
                    </Field>

                    {type === 'specialist' && (
                        <fieldset className="space-y-3">
                            <legend className="text-sm font-medium">
                                Job types covered
                            </legend>
                            <div className="grid gap-3 sm:grid-cols-2">
                                {options.categories.map((category) => (
                                    <div
                                        key={category.value}
                                        className="flex items-center gap-2"
                                    >
                                        <Checkbox
                                            id={`category-${category.value}`}
                                            name="categories[]"
                                            value={category.value}
                                            checked={categories.includes(
                                                category.value,
                                            )}
                                            onCheckedChange={(checked) =>
                                                setCategories((current) =>
                                                    checked
                                                        ? [
                                                              ...current,
                                                              category.value,
                                                          ]
                                                        : current.filter(
                                                              (value) =>
                                                                  value !==
                                                                  category.value,
                                                          ),
                                                )
                                            }
                                        />
                                        <Label
                                            htmlFor={`category-${category.value}`}
                                            className="font-normal"
                                        >
                                            {category.label}
                                        </Label>
                                    </div>
                                ))}
                            </div>
                            <InputError message={errors.categories} />
                        </fieldset>
                    )}

                    <div className="grid gap-6 sm:grid-cols-2">
                        <Field
                            id="contact_name"
                            label="Contact name"
                            error={errors.contact_name}
                        >
                            <Input
                                id="contact_name"
                                name="contact_name"
                                maxLength={255}
                                defaultValue={vendor?.contact_name ?? ''}
                                aria-invalid={!!errors.contact_name}
                            />
                        </Field>

                        <Field id="phone" label="Phone" error={errors.phone}>
                            <Input
                                id="phone"
                                name="phone"
                                type="tel"
                                maxLength={32}
                                defaultValue={vendor?.phone ?? ''}
                                aria-invalid={!!errors.phone}
                            />
                        </Field>
                    </div>

                    <Field id="email" label="Email" error={errors.email}>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            maxLength={255}
                            defaultValue={vendor?.email ?? ''}
                            aria-invalid={!!errors.email}
                        />
                    </Field>

                    <Field id="notes" label="Notes" error={errors.notes}>
                        <Textarea
                            id="notes"
                            name="notes"
                            rows={4}
                            maxLength={20000}
                            defaultValue={vendor?.notes ?? ''}
                            aria-invalid={!!errors.notes}
                        />
                    </Field>

                    <section
                        aria-labelledby="documents-heading"
                        className="space-y-4 border-t pt-6"
                    >
                        <div className="space-y-2">
                            <h2
                                id="documents-heading"
                                className="flex items-center gap-2 font-medium"
                            >
                                <FileCheck
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                Documents required from this vendor
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Collect these before assigning work, keep copies
                                and check each one against the official source.
                                The list changes with the job types above. This
                                is general guidance on Victorian rules, not
                                legal advice.
                            </p>
                        </div>

                        {documents.map((document) => (
                            <DocumentCard
                                key={document.value}
                                document={document}
                                vendor={vendor}
                                errors={errors}
                            />
                        ))}

                        <Field
                            id="documents_checked_on"
                            label="Documents last checked"
                            error={errors.documents_checked_on}
                        >
                            <Input
                                id="documents_checked_on"
                                name="documents_checked_on"
                                type="date"
                                className="sm:max-w-56"
                                defaultValue={
                                    vendor?.documents_checked_on ?? ''
                                }
                                aria-invalid={!!errors.documents_checked_on}
                            />
                        </Field>
                    </section>

                    <Button type="submit" disabled={processing}>
                        {processing && <Spinner />}
                        {submitLabel}
                    </Button>
                </>
            )}
        </Form>
    );
}

function DocumentCard({
    document,
    vendor,
    errors,
}: {
    document: VendorDocumentRequirement;
    vendor?: Vendor;
    errors: Partial<Record<string, string>>;
}) {
    return (
        <fieldset className="space-y-4 rounded-lg border p-4">
            <legend className="px-1 text-sm font-medium">
                {document.label}
            </legend>
            <p className="text-sm text-muted-foreground">
                {document.guidance}{' '}
                <a
                    href={document.url}
                    target="_blank"
                    rel="noreferrer"
                    className="inline-flex items-center gap-1 text-foreground underline underline-offset-4"
                >
                    Official source
                    <ExternalLink className="size-3" aria-hidden="true" />
                </a>
            </p>
            <div className="grid gap-4 sm:grid-cols-2">
                {documentFields[document.value].map((field) => (
                    <Field
                        key={field.name}
                        id={field.name}
                        label={field.label}
                        error={errors[field.name]}
                    >
                        <Input
                            id={field.name}
                            name={field.name}
                            type={field.type ?? 'text'}
                            min={field.type === 'number' ? 0 : undefined}
                            defaultValue={String(vendor?.[field.name] ?? '')}
                            aria-invalid={!!errors[field.name]}
                        />
                    </Field>
                ))}
            </div>
        </fieldset>
    );
}

function Field({
    id,
    label,
    error,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="grid content-start gap-2">
            <Label htmlFor={id}>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}
