import { Head, Link, usePage } from '@inertiajs/react';
import { CircleCheck } from 'lucide-react';
import EmergencyNotice from '@/components/job-requests/emergency-notice';
import { Button } from '@/components/ui/button';
import { home, login, register } from '@/routes';
import { index } from '@/routes/job-requests';

export default function Submitted({
    email,
    emergency,
}: {
    email: string;
    emergency: boolean;
}) {
    const { auth, site } = usePage().props;

    return (
        <>
            <Head title="Request received" />

            <div className="mx-auto max-w-xl px-4 py-16 text-center sm:px-6">
                <CircleCheck
                    className="mx-auto size-12 text-green-600"
                    aria-hidden="true"
                />
                <h1 className="mt-6 text-3xl font-semibold tracking-tight">
                    Thanks, we've got your request
                </h1>
                <p className="mt-4 text-muted-foreground">
                    We'll review the details and get back to you with a quote.
                    We've emailed a confirmation to{' '}
                    <span className="break-words">{email}</span>.
                </p>
                {!emergency && site.phone && (
                    <p className="mt-2 text-muted-foreground">
                        Need us in a hurry? Call{' '}
                        <a
                            href={`tel:${site.phone.replace(/[^\d+]/g, '')}`}
                            className="font-medium text-foreground underline underline-offset-4"
                        >
                            {site.phone}
                        </a>
                        .
                    </p>
                )}

                {emergency && (
                    <div className="mt-8 text-left">
                        <EmergencyNotice />
                    </div>
                )}

                <div className="mt-8 rounded-xl border bg-muted/40 p-6 text-left">
                    <h2 className="font-medium">Next: add photos</h2>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Photos help us quote accurately. To add them and follow
                        updates, log in or create an account with{' '}
                        <strong className="font-medium break-all text-foreground">
                            {email}
                        </strong>
                        . Your request is linked automatically once your email
                        is verified.
                    </p>
                    <div className="mt-4 flex flex-wrap gap-3">
                        {auth.user ? (
                            <Button asChild>
                                <Link href={index()}>Go to my requests</Link>
                            </Button>
                        ) : (
                            <>
                                <Button asChild>
                                    <Link href={register({ query: { email } })}>
                                        Create an account
                                    </Link>
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={login()}>Log in</Link>
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                <Link
                    href={home()}
                    className="mt-8 inline-block text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
                >
                    Back to home
                </Link>
            </div>
        </>
    );
}
