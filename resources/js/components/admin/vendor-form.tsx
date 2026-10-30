import { Form } from '@inertiajs/react';
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
import type { Vendor, VendorOptions } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

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
                                            defaultChecked={vendor?.categories.some(
                                                (selected) =>
                                                    selected.value ===
                                                    category.value,
                                            )}
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

                    <Button type="submit" disabled={processing}>
                        {processing && <Spinner />}
                        {submitLabel}
                    </Button>
                </>
            )}
        </Form>
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
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}
