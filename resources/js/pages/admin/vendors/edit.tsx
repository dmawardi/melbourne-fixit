import { Head } from '@inertiajs/react';
import VendorController from '@/actions/App/Http/Controllers/Admin/VendorController';
import VendorFiles from '@/components/admin/vendor-files';
import VendorForm from '@/components/admin/vendor-form';
import { edit, index } from '@/routes/admin/vendors';
import type { Option, Vendor, VendorFile, VendorOptions } from '@/types';

type Props = {
    vendor: Vendor;
    options: VendorOptions;
    files: VendorFile[];
    fileDocuments: Option[];
};

export default function Edit({ vendor, options, files, fileDocuments }: Props) {
    return (
        <>
            <Head title={`Edit ${vendor.name}`} />

            <div className="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
                <h1 className="text-xl font-semibold tracking-tight break-words">
                    Edit {vendor.name}
                </h1>
                <VendorForm
                    action={VendorController.update.form(vendor.id)}
                    options={options}
                    vendor={vendor}
                    submitLabel="Save changes"
                />
                <VendorFiles
                    vendor={vendor}
                    files={files}
                    documents={fileDocuments}
                    options={options}
                />
            </div>
        </>
    );
}

Edit.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Vendors', href: index() },
        { title: props.vendor.name, href: edit(props.vendor.id) },
    ],
});
