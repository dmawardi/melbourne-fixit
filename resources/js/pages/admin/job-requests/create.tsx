import { Form, Head } from '@inertiajs/react';
import JobRequestController from '@/actions/App/Http/Controllers/Admin/JobRequestController';
import InputError from '@/components/input-error';
import JobRequestFields from '@/components/job-requests/job-request-fields';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { create, index } from '@/routes/admin/job-requests';
import type { JobRequestOptions, Option } from '@/types';

type Props = {
    options: JobRequestOptions & { statuses: Option[] };
};

export default function Create({ options }: Props) {
    return (
        <>
            <Head title="New request" />

            <div className="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight">
                        New request
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Log a request for a customer. If there's no account for
                        their email, we'll create one and email them a link to
                        set their password. We'll also email them to confirm the
                        request.
                    </p>
                </div>

                <Form
                    {...JobRequestController.store.form()}
                    disableWhileProcessing
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <JobRequestFields
                                mode="create"
                                showAddress
                                contactLegend="Customer details"
                                emailHint="The request is added to the account with this email."
                                options={options}
                                errors={errors}
                            />

                            <fieldset className="space-y-4 rounded-xl border bg-card p-5">
                                <legend className="px-1 font-medium">
                                    Admin
                                </legend>
                                <div className="grid gap-2">
                                    <Label htmlFor="status">Status</Label>
                                    <NativeSelect
                                        id="status"
                                        name="status"
                                        required
                                        defaultValue={
                                            options.statuses[0]?.value
                                        }
                                        aria-invalid={!!errors.status}
                                    >
                                        {options.statuses.map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError message={errors.status} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="admin_notes">
                                        Notes (admin only)
                                    </Label>
                                    <Textarea
                                        id="admin_notes"
                                        name="admin_notes"
                                        rows={4}
                                        maxLength={20000}
                                        aria-invalid={!!errors.admin_notes}
                                    />
                                    <InputError message={errors.admin_notes} />
                                </div>
                            </fieldset>

                            <Button
                                type="submit"
                                disabled={processing}
                                data-test="create-job-request-button"
                            >
                                {processing && <Spinner />}
                                Create request
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Requests', href: index() },
        { title: 'New request', href: create() },
    ],
};
