import { Form } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import { useState } from 'react';
import JobRequestVendorController from '@/actions/App/Http/Controllers/Admin/JobRequestVendorController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import type { AdminJobRequest, VendorChoice } from '@/types';

function CategoryWarning({
    name,
    category,
}: {
    name: string;
    category: string;
}) {
    return (
        <p
            role="alert"
            className="flex items-start gap-2 rounded-md bg-amber-100 p-3 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200"
        >
            <TriangleAlert
                className="mt-0.5 size-4 shrink-0"
                aria-hidden="true"
            />
            {name} doesn't usually take {category} jobs.
        </p>
    );
}

export default function JobRequestVendor({
    jobRequest,
    vendors,
}: {
    jobRequest: AdminJobRequest;
    vendors: VendorChoice[];
}) {
    const current = jobRequest.vendor;
    const [selectedId, setSelectedId] = useState('');
    const selected = vendors.find((vendor) => String(vendor.id) === selectedId);
    const suitable = vendors.filter((vendor) => vendor.handles_category);
    const others = vendors.filter((vendor) => !vendor.handles_category);

    return (
        <div className="space-y-4 rounded-lg border p-4">
            {current ? (
                <div className="space-y-3">
                    <div className="flex flex-wrap items-start justify-between gap-2">
                        <div className="min-w-0">
                            <p className="flex flex-wrap items-center gap-2 font-medium break-words">
                                {current.name}
                                {current.archived && (
                                    <Badge variant="outline">Archived</Badge>
                                )}
                            </p>
                            <p className="text-sm break-words text-muted-foreground">
                                {[
                                    current.contact_name,
                                    current.phone,
                                    current.email,
                                ]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </p>
                        </div>
                        <ConfirmDeleteDialog
                            trigger={
                                <Button variant="outline" size="sm">
                                    Remove
                                </Button>
                            }
                            title={`Remove ${current.name} from this request?`}
                            description="This will be recorded in the activity log."
                            action={JobRequestVendorController.destroy.form(
                                jobRequest.id,
                            )}
                            confirmLabel="Remove vendor"
                        />
                    </div>
                    {!current.handles_category && (
                        <CategoryWarning
                            name={current.name}
                            category={jobRequest.category.label}
                        />
                    )}
                </div>
            ) : (
                <p className="text-sm text-muted-foreground">
                    No vendor assigned.
                </p>
            )}

            <Form
                {...JobRequestVendorController.update.form(jobRequest.id)}
                options={{ preserveScroll: true }}
                onSuccess={() => setSelectedId('')}
                className="space-y-3 border-t pt-4"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="vendor_id">
                                {current ? 'Change vendor' : 'Assign a vendor'}
                            </Label>
                            <NativeSelect
                                id="vendor_id"
                                name="vendor_id"
                                required
                                value={selectedId}
                                onChange={(event) =>
                                    setSelectedId(event.target.value)
                                }
                                aria-invalid={!!errors.vendor_id}
                            >
                                <option value="" disabled>
                                    Choose a vendor
                                </option>
                                <optgroup
                                    label={`Takes ${jobRequest.category.label} jobs`}
                                >
                                    {suitable.map((vendor) => (
                                        <option
                                            key={vendor.id}
                                            value={vendor.id}
                                        >
                                            {vendor.name}
                                        </option>
                                    ))}
                                </optgroup>
                                <optgroup label="Other vendors">
                                    {others.map((vendor) => (
                                        <option
                                            key={vendor.id}
                                            value={vendor.id}
                                        >
                                            {vendor.name}
                                        </option>
                                    ))}
                                </optgroup>
                            </NativeSelect>
                            <InputError message={errors.vendor_id} />
                        </div>

                        {selected && !selected.handles_category && (
                            <CategoryWarning
                                name={selected.name}
                                category={jobRequest.category.label}
                            />
                        )}

                        <Button
                            type="submit"
                            size="sm"
                            disabled={processing || !selected}
                        >
                            {processing && <Spinner />}
                            {current ? 'Change vendor' : 'Assign vendor'}
                        </Button>
                    </>
                )}
            </Form>
        </div>
    );
}
