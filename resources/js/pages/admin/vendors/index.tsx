import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2, Truck } from 'lucide-react';
import VendorController from '@/actions/App/Http/Controllers/Admin/VendorController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, edit, index } from '@/routes/admin/vendors';
import type { Paginated, Vendor } from '@/types';

export default function Index({ vendors }: { vendors: Paginated<Vendor> }) {
    const { data, links, meta } = vendors;

    return (
        <>
            <Head title="Vendors" />

            <div className="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            Vendors
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            The businesses you can assign to job requests.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            Add vendor
                        </Link>
                    </Button>
                </div>

                {data.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center">
                        <Truck
                            className="mx-auto size-10 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <Heading
                            variant="small"
                            title="No vendors yet"
                            description="Add a vendor to start assigning it to job requests."
                        />
                    </div>
                ) : (
                    <ul className="divide-y rounded-xl border">
                        {data.map((vendor) => (
                            <li
                                key={vendor.id}
                                className="flex flex-wrap items-center gap-4 p-4"
                            >
                                <div className="min-w-0 flex-1 space-y-1">
                                    <h2 className="font-medium break-words">
                                        {vendor.name}
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        {[
                                            vendor.contact_name,
                                            vendor.phone,
                                            vendor.email,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                    <div className="flex flex-wrap gap-1">
                                        {vendor.categories.length === 0 ? (
                                            <Badge variant="secondary">
                                                All job types
                                            </Badge>
                                        ) : (
                                            vendor.categories.map(
                                                (category) => (
                                                    <Badge
                                                        key={category.value}
                                                        variant="outline"
                                                    >
                                                        {category.label}
                                                    </Badge>
                                                ),
                                            )
                                        )}
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        {vendor.job_requests_count ?? 0}{' '}
                                        {vendor.job_requests_count === 1
                                            ? 'request'
                                            : 'requests'}{' '}
                                        assigned
                                    </p>
                                </div>
                                <div className="flex gap-2">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={edit(vendor.id)}>
                                            <Pencil />
                                            Edit
                                        </Link>
                                    </Button>
                                    <ConfirmDeleteDialog
                                        trigger={
                                            <Button variant="outline" size="sm">
                                                <Trash2 />
                                                Archive
                                            </Button>
                                        }
                                        title={`Archive ${vendor.name}?`}
                                        description="It will stay on requests it's already assigned to, but can't be assigned to any more."
                                        action={VendorController.destroy.form(
                                            vendor.id,
                                        )}
                                        confirmLabel="Archive vendor"
                                    />
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination links={links} meta={meta} />
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [{ title: 'Vendors', href: index() }],
};
