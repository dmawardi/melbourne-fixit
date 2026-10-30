import { Head } from '@inertiajs/react';
import VendorController from '@/actions/App/Http/Controllers/Admin/VendorController';
import VendorForm from '@/components/admin/vendor-form';
import { create, index } from '@/routes/admin/vendors';
import type { VendorOptions } from '@/types';

export default function Create({ options }: { options: VendorOptions }) {
    return (
        <>
            <Head title="Add vendor" />

            <div className="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
                <h1 className="text-xl font-semibold tracking-tight">
                    Add vendor
                </h1>
                <VendorForm
                    action={VendorController.store.form()}
                    options={options}
                    submitLabel="Add vendor"
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Vendors', href: index() },
        { title: 'Add vendor', href: create() },
    ],
};
