import { Form } from '@inertiajs/react';
import { ImagePlus, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import JobRequestFileController from '@/actions/App/Http/Controllers/JobRequests/JobRequestFileController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { JobRequest, JobRequestFile } from '@/types';

type Props = {
    jobRequest: JobRequest;
    photos: JobRequestFile[];
    // Photo slots the customer has left, or null when they can't upload.
    remaining: number | null;
    maxPhotos: number;
};

export default function PhotoGallery({
    jobRequest,
    photos,
    remaining,
    maxPhotos,
}: Props) {
    return (
        <div className="space-y-6">
            {photos.length > 0 ? (
                <ul className="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    {photos.map((photo) => (
                        <PhotoCard
                            key={photo.id}
                            jobRequest={jobRequest}
                            photo={photo}
                        />
                    ))}
                </ul>
            ) : (
                <p className="text-sm text-muted-foreground">
                    No photos yet.
                    {remaining !== null &&
                        ' Photos of the problem and the area around it help us quote accurately.'}
                </p>
            )}

            {remaining !== null && (
                <PhotoUpload
                    jobRequest={jobRequest}
                    remaining={remaining}
                    maxPhotos={maxPhotos}
                />
            )}
        </div>
    );
}

function PhotoCard({
    jobRequest,
    photo,
}: {
    jobRequest: JobRequest;
    photo: JobRequestFile;
}) {
    const alt =
        photo.caption ?? `Photo ${photo.sort_order} for ${jobRequest.title}`;

    return (
        <li className="overflow-hidden rounded-lg border">
            <a href={photo.url} target="_blank" rel="noopener noreferrer">
                <img
                    src={photo.url}
                    alt={alt}
                    width={photo.width ?? undefined}
                    height={photo.height ?? undefined}
                    loading="lazy"
                    decoding="async"
                    className="aspect-[4/3] w-full bg-muted object-cover"
                />
            </a>
            <div className="flex items-start gap-1 p-2">
                <p className="min-w-0 flex-1 py-1 text-sm break-words text-muted-foreground">
                    {photo.caption ?? (
                        <span className="italic">No caption</span>
                    )}
                </p>
                {photo.can.update && (
                    <CaptionDialog jobRequest={jobRequest} photo={photo} />
                )}
                {photo.can.delete && (
                    <ConfirmDeleteDialog
                        trigger={
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-8 shrink-0"
                                aria-label="Delete photo"
                            >
                                <Trash2 />
                            </Button>
                        }
                        title="Delete this photo?"
                        description="The photo will be permanently removed from your request."
                        action={JobRequestFileController.destroy.form({
                            jobRequest: jobRequest.id,
                            file: photo.id,
                        })}
                    />
                )}
            </div>
        </li>
    );
}

function CaptionDialog({
    jobRequest,
    photo,
}: {
    jobRequest: JobRequest;
    photo: JobRequestFile;
}) {
    const [open, setOpen] = useState(false);
    const inputId = `caption-${photo.id}`;

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-8 shrink-0"
                    aria-label="Edit caption"
                >
                    <Pencil />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Edit caption</DialogTitle>
                <DialogDescription>
                    Describe what the photo shows, like “Water stain on the
                    ceiling below the bathroom”.
                </DialogDescription>

                <Form
                    {...JobRequestFileController.update.form({
                        jobRequest: jobRequest.id,
                        file: photo.id,
                    })}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor={inputId}>Caption</Label>
                                <Input
                                    id={inputId}
                                    name="caption"
                                    maxLength={255}
                                    defaultValue={photo.caption ?? ''}
                                    autoFocus
                                />
                                <InputError message={errors.caption} />
                            </div>
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    Save caption
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function PhotoUpload({
    jobRequest,
    remaining,
    maxPhotos,
}: {
    jobRequest: JobRequest;
    remaining: number;
    maxPhotos: number;
}) {
    if (remaining <= 0) {
        return (
            <p className="text-sm text-muted-foreground">
                You've added the maximum of {maxPhotos} photos. Delete one to
                add another.
            </p>
        );
    }

    return (
        <Form
            {...JobRequestFileController.store.form(jobRequest.id)}
            options={{ preserveScroll: true }}
            resetOnSuccess
            className="space-y-3 rounded-lg border border-dashed p-4"
        >
            {({ processing, errors }) => {
                const error = Object.entries(errors).find(([key]) =>
                    key.startsWith('photos'),
                )?.[1];

                return (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="photos">Add photos</Label>
                            <Input
                                id="photos"
                                name="photos[]"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                multiple
                                required
                                aria-describedby="photos-hint"
                                aria-invalid={!!error}
                            />
                            <p
                                id="photos-hint"
                                className="text-sm text-muted-foreground"
                            >
                                JPG, PNG or WebP, up to 10MB each. You can add{' '}
                                {remaining} more.
                            </p>
                            <InputError message={error} />
                        </div>
                        <Button type="submit" disabled={processing}>
                            {processing ? <Spinner /> : <ImagePlus />}
                            Upload
                        </Button>
                    </>
                );
            }}
        </Form>
    );
}
