import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import type { JobRequestOptions, Option } from '@/types';

export type JobRequestDefaults = {
    contact_name?: string | null;
    contact_email?: string | null;
    contact_phone?: string | null;
    suburb?: string | null;
    postcode?: string | null;
    address_line?: string | null;
    category?: string | null;
    title?: string | null;
    description?: string | null;
    urgency?: string | null;
    budget_range?: string | null;
    property_type?: string | null;
};

type Props = {
    options: JobRequestOptions;
    errors: Partial<Record<string, string>>;
    defaults?: JobRequestDefaults;
    // The public form asks for an email but not a street address; editing is the reverse.
    mode: 'create' | 'edit';
};

export default function JobRequestFields({
    options,
    errors,
    defaults = {},
    mode,
}: Props) {
    return (
        <div className="space-y-10">
            <Fieldset legend="About the job">
                <Field
                    id="category"
                    label="Type of job"
                    error={errors.category}
                >
                    <NativeSelect
                        id="category"
                        name="category"
                        required
                        defaultValue={defaults.category ?? ''}
                        aria-invalid={!!errors.category}
                    >
                        <option value="" disabled>
                            Choose a type of job
                        </option>
                        <Options options={options.categories} />
                    </NativeSelect>
                </Field>

                <Field
                    id="title"
                    label="Job title"
                    hint="A short summary, like “Replace leaking kitchen tap”."
                    error={errors.title}
                >
                    <Input
                        id="title"
                        name="title"
                        required
                        maxLength={120}
                        defaultValue={defaults.title ?? ''}
                        aria-invalid={!!errors.title}
                        aria-describedby="title-hint"
                    />
                </Field>

                <Field
                    id="description"
                    label="Describe the job"
                    hint="What needs doing, what's gone wrong and anything we should know about access. At least 20 characters."
                    error={errors.description}
                >
                    <Textarea
                        id="description"
                        name="description"
                        required
                        minLength={20}
                        maxLength={5000}
                        rows={6}
                        defaultValue={defaults.description ?? ''}
                        aria-invalid={!!errors.description}
                        aria-describedby="description-hint"
                    />
                </Field>

                <div className="grid gap-6 sm:grid-cols-3">
                    <Field
                        id="urgency"
                        label="How soon?"
                        error={errors.urgency}
                    >
                        <NativeSelect
                            id="urgency"
                            name="urgency"
                            required
                            defaultValue={defaults.urgency ?? 'flexible'}
                            aria-invalid={!!errors.urgency}
                        >
                            <Options options={options.urgencies} />
                        </NativeSelect>
                    </Field>

                    <Field
                        id="budget_range"
                        label="Budget"
                        optional
                        error={errors.budget_range}
                    >
                        <NativeSelect
                            id="budget_range"
                            name="budget_range"
                            defaultValue={defaults.budget_range ?? ''}
                            aria-invalid={!!errors.budget_range}
                        >
                            <option value="">Not sure</option>
                            <Options options={options.budgetRanges} />
                        </NativeSelect>
                    </Field>

                    <Field
                        id="property_type"
                        label="Property type"
                        optional
                        error={errors.property_type}
                    >
                        <NativeSelect
                            id="property_type"
                            name="property_type"
                            defaultValue={defaults.property_type ?? ''}
                            aria-invalid={!!errors.property_type}
                        >
                            <option value="">Prefer not to say</option>
                            <Options options={options.propertyTypes} />
                        </NativeSelect>
                    </Field>
                </div>
            </Fieldset>

            <Fieldset legend="Location">
                {mode === 'edit' && (
                    <Field
                        id="address_line"
                        label="Street address"
                        optional
                        error={errors.address_line}
                    >
                        <Input
                            id="address_line"
                            name="address_line"
                            autoComplete="street-address"
                            maxLength={255}
                            defaultValue={defaults.address_line ?? ''}
                            aria-invalid={!!errors.address_line}
                        />
                    </Field>
                )}

                <div className="grid gap-6 sm:grid-cols-3">
                    <div className="sm:col-span-2">
                        <Field id="suburb" label="Suburb" error={errors.suburb}>
                            <Input
                                id="suburb"
                                name="suburb"
                                required
                                maxLength={100}
                                autoComplete="address-level2"
                                defaultValue={defaults.suburb ?? ''}
                                aria-invalid={!!errors.suburb}
                            />
                        </Field>
                    </div>

                    <Field
                        id="postcode"
                        label="Postcode"
                        error={errors.postcode}
                    >
                        <Input
                            id="postcode"
                            name="postcode"
                            required
                            inputMode="numeric"
                            pattern="3[0-9]{3}"
                            maxLength={4}
                            autoComplete="postal-code"
                            title="A Victorian postcode, starting with 3"
                            defaultValue={defaults.postcode ?? ''}
                            aria-invalid={!!errors.postcode}
                        />
                    </Field>
                </div>
            </Fieldset>

            <Fieldset legend="Your contact details">
                <Field
                    id="contact_name"
                    label="Name"
                    error={errors.contact_name}
                >
                    <Input
                        id="contact_name"
                        name="contact_name"
                        required
                        maxLength={255}
                        autoComplete="name"
                        defaultValue={defaults.contact_name ?? ''}
                        aria-invalid={!!errors.contact_name}
                    />
                </Field>

                <div className="grid gap-6 sm:grid-cols-2">
                    {mode === 'create' && (
                        <Field
                            id="contact_email"
                            label="Email"
                            hint="Use this email to log in and see your request."
                            error={errors.contact_email}
                        >
                            <Input
                                id="contact_email"
                                name="contact_email"
                                type="email"
                                required
                                maxLength={255}
                                autoComplete="email"
                                defaultValue={defaults.contact_email ?? ''}
                                aria-invalid={!!errors.contact_email}
                                aria-describedby="contact_email-hint"
                            />
                        </Field>
                    )}

                    <Field
                        id="contact_phone"
                        label="Phone"
                        optional
                        error={errors.contact_phone}
                    >
                        <Input
                            id="contact_phone"
                            name="contact_phone"
                            type="tel"
                            maxLength={32}
                            autoComplete="tel"
                            defaultValue={defaults.contact_phone ?? ''}
                            aria-invalid={!!errors.contact_phone}
                        />
                    </Field>
                </div>
            </Fieldset>
        </div>
    );
}

function Fieldset({
    legend,
    children,
}: {
    legend: string;
    children: ReactNode;
}) {
    return (
        <fieldset className="space-y-6">
            <legend className="mb-4 text-lg font-medium">{legend}</legend>
            {children}
        </fieldset>
    );
}

function Field({
    id,
    label,
    hint,
    optional = false,
    error,
    children,
}: {
    id: string;
    label: string;
    hint?: string;
    optional?: boolean;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>
                {label}
                {optional && (
                    <span className="font-normal text-muted-foreground">
                        (optional)
                    </span>
                )}
            </Label>
            {children}
            {hint && (
                <p id={`${id}-hint`} className="text-sm text-muted-foreground">
                    {hint}
                </p>
            )}
            <InputError message={error} />
        </div>
    );
}

function Options({ options }: { options: Option[] }) {
    return options.map((option) => (
        <option key={option.value} value={option.value}>
            {option.label}
        </option>
    ));
}
