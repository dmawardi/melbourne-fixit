import { Form, Link, usePage } from '@inertiajs/react';
import { Phone } from 'lucide-react';
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

const nextSteps = [
    'We review your request.',
    'We match the job with a vetted, insured tradesperson, licensed where the work requires it.',
    'We send you a quote.',
];

export default function Create({ options, defaults, structuredData }: Props) {
    const { auth, site } = usePage().props;

    return (
        <>
            <Seo
                title="Request a quote"
                description="Tell us about your home maintenance, repair or renovation job in Melbourne and we'll send you a quote. No account needed."
                jsonLd={structuredData}
            />

            <div className="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <div>
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
                        Tell us about the job and where it is. We'll review it
                        and get back to you with a quote. You can add photos
                        once you've logged in.
                    </p>
                    <p className="mt-2 text-sm font-medium lg:hidden">
                        Every tradesperson is vetted and insured, and licensed
                        where the work requires it.
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
                        className="mt-8 space-y-6"
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

                                <div>
                                    <Button
                                        type="submit"
                                        variant="cta"
                                        size="lg"
                                        disabled={processing}
                                        data-test="submit-job-request-button"
                                    >
                                        {processing && <Spinner />}
                                        Send request
                                    </Button>
                                    <p className="mt-3 text-sm text-muted-foreground">
                                        No account or payment needed to send a
                                        request.
                                    </p>
                                </div>
                            </>
                        )}
                    </Form>
                </div>

                <aside className="hidden lg:block">
                    <div className="sticky top-6 overflow-hidden rounded-xl border bg-card">
                        <img
                            src="/images/request-aside.webp"
                            alt=""
                            width={700}
                            height={382}
                            loading="lazy"
                            className="aspect-[16/9] w-full object-cover"
                        />
                        <div className="p-5">
                            <h2 className="font-medium">What happens next</h2>
                            <ol className="mt-3 space-y-3 text-sm text-muted-foreground">
                                {nextSteps.map((step, index) => (
                                    <li key={step} className="flex gap-3">
                                        <span
                                            className="flex size-5 shrink-0 items-center justify-center rounded-full bg-cta text-xs font-medium text-white"
                                            aria-hidden="true"
                                        >
                                            {index + 1}
                                        </span>
                                        {step}
                                    </li>
                                ))}
                            </ol>
                            {site.phone && (
                                <a
                                    href={`tel:${site.phone.replace(/[^\d+]/g, '')}`}
                                    className="mt-4 flex items-center gap-2 border-t pt-4 text-sm font-medium"
                                >
                                    <Phone
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    {site.phone}
                                </a>
                            )}
                        </div>
                    </div>
                </aside>
            </div>
        </>
    );
}
