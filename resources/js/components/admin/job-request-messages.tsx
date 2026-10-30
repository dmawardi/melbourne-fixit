import { Form } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import JobRequestMessageController from '@/actions/App/Http/Controllers/Admin/JobRequestMessageController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime } from '@/lib/format';
import type { AdminJobRequest, Option } from '@/types';

export default function JobRequestMessages({
    jobRequest,
    visibilities,
}: {
    jobRequest: AdminJobRequest;
    visibilities: Option[];
}) {
    const messages = jobRequest.messages ?? [];

    return (
        <div className="space-y-4">
            <Form
                {...JobRequestMessageController.store.form(jobRequest.id)}
                options={{ preserveScroll: true }}
                resetOnSuccess
                className="space-y-3 rounded-lg border p-4"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="body">Post an update</Label>
                            <Textarea
                                id="body"
                                name="body"
                                required
                                rows={3}
                                aria-invalid={!!errors.body}
                            />
                            <InputError message={errors.body} />
                        </div>
                        <div className="flex flex-wrap items-end justify-between gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="message-visibility">
                                    Visible to
                                </Label>
                                <NativeSelect
                                    id="message-visibility"
                                    name="visibility"
                                    required
                                    defaultValue="internal"
                                    aria-invalid={!!errors.visibility}
                                >
                                    {visibilities.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                                <InputError message={errors.visibility} />
                            </div>
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Post update
                            </Button>
                        </div>
                    </>
                )}
            </Form>

            {messages.length === 0 ? (
                <p className="text-sm text-muted-foreground">No updates yet.</p>
            ) : (
                <ul className="divide-y rounded-lg border">
                    {messages.map((message) => (
                        <li
                            key={message.id}
                            className="flex items-start gap-3 p-3"
                        >
                            <div className="min-w-0 flex-1 space-y-1">
                                <p className="text-sm break-words whitespace-pre-line">
                                    {message.body}
                                </p>
                                <p className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                    <Badge variant="outline">
                                        {message.visibility.label}
                                    </Badge>
                                    {formatDateTime(message.created_at)}
                                    {message.author &&
                                        ` · ${message.author.name}`}
                                </p>
                            </div>
                            <ConfirmDeleteDialog
                                trigger={
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Delete update"
                                    >
                                        <Trash2 />
                                    </Button>
                                }
                                title="Delete this update?"
                                description="Anyone who could see it won't be able to any more."
                                action={JobRequestMessageController.destroy.form(
                                    [jobRequest.id, message.id],
                                )}
                            />
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
