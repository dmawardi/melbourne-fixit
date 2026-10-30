import { Form, Link, usePage } from '@inertiajs/react';
import JobRequestController from '@/actions/App/Http/Controllers/JobRequests/JobRequestController';
import JobRequestFields from '@/components/job-requests/job-request-fields';
import type { JobRequestDefaults } from '@/components/job-requests/job-request-fields';
import Seo from '@/components/seo';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { home, login } from '@/routes';
import type { JobRequestOptions } from '@/types';

type Props = {
    options: JobRequestOptions;
    defaults: JobRequestDefaults;
    structuredData: Record<string, unknown>[];
};

export default function Create({ options, defaults, structuredData }: Props) {
    const { auth } = usePage().props;

    return (
        <>
            <Seo
                title="Request a quote"
                description="Tell us about your home maintenance, repair or renovation job in Melbourne and we'll send you a quote. No account needed."
                jsonLd={structuredData}
            />

            <div className="mx-auto max-w-3xl px-4 py-12 sm:px-6">
                <nav aria-label="Breadcrumb" className="mb-6 text-sm">
                    <ol className="flex items-center gap-2 text-muted-foreground">
                        <li>
                            <Link
                                href={home()}
                                className="hover:text-foreground"
                            >
                                Home
                            </Link>
                        </li>
                        <li aria-hidden="true">/</li>
                        <li aria-current="page" className="text-foreground">
                            Request a quote
                        </li>
                    </ol>
                </nav>

                <h1 className="text-3xl font-semibold tracking-tight">
                    Request a quote
                </h1>
                <p className="mt-3 text-muted-foreground">
                    Tell us about the job and where it is. We'll review it and
                    get back to you with a quote. You can add photos once you've
                    logged in.
                </p>
                {!auth.user && (
                    <p className="mt-2 text-sm text-muted-foreground">
                        Already have an account?{' '}
                        <Link
                            href={login()}
                            className="text-foreground underline underline-offset-4"
                        >
                            Log in
                        </Link>{' '}
                        to add photos straight away.
                    </p>
                )}

                <Form
                    {...JobRequestController.store.form()}
                    disableWhileProcessing
                    className="mt-10 space-y-10"
                >
                    {({ processing, errors }) => (
                        <>
                            <JobRequestFields
                                mode="create"
                                options={options}
                                errors={errors}
                                defaults={defaults}
                            />

                            {/* Honeypot: hidden from people, so only bots fill it in. */}
                            <div
                                aria-hidden="true"
                                className="absolute -left-[9999px] h-px w-px overflow-hidden"
                            >
                                <label htmlFor="website">
                                    Leave this field empty
                                </label>
                                <input
                                    id="website"
                                    name="website"
                                    type="text"
                                    tabIndex={-1}
                                    autoComplete="off"
                                />
                            </div>

                            <div className="border-t pt-6">
                                <Button
                                    type="submit"
                                    size="lg"
                                    disabled={processing}
                                    data-test="submit-job-request-button"
                                >
                                    {processing && <Spinner />}
                                    Send request
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
