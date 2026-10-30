import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    ClipboardList,
    ImagePlus,
    MapPin,
    MessageSquareText,
} from 'lucide-react';
import Seo from '@/components/seo';
import { Button } from '@/components/ui/button';
import { create } from '@/routes/job-requests';
import type { Faq, Service } from '@/types';

type Props = {
    services: Service[];
    serviceAreas: string[];
    faqs: Faq[];
    structuredData: Record<string, unknown>[];
};

const steps = [
    {
        icon: ClipboardList,
        title: 'Tell us about the job',
        description:
            'Fill in a short form with what needs doing, where and how soon. No account needed.',
    },
    {
        icon: ImagePlus,
        title: 'Add photos',
        description:
            'Log in with the same email to add photos, so we can see the job before quoting. Only you and our team can see them.',
    },
    {
        icon: MessageSquareText,
        title: 'Get your quote',
        description:
            'We match the job with the right tradesperson and send you a quote. Follow every update from your account.',
    },
];

export default function Home({
    services,
    serviceAreas,
    faqs,
    structuredData,
}: Props) {
    return (
        <>
            <Seo
                title="Home maintenance & renovations in Melbourne"
                description="Repairs, maintenance and renovations across Melbourne: plumbing, electrical, carpentry, painting, tiling, kitchens, bathrooms and more. Request a quote online."
                jsonLd={structuredData}
            />

            <section className="border-b bg-gradient-to-b from-orange-50 to-background dark:from-orange-950/20">
                <div className="mx-auto max-w-6xl px-4 py-16 sm:px-6 md:py-24">
                    <p className="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-orange-700 dark:text-orange-400">
                        <MapPin className="size-4" aria-hidden="true" />
                        Melbourne, Victoria
                    </p>
                    <h1 className="max-w-3xl text-4xl font-semibold tracking-tight text-balance md:text-5xl">
                        Home maintenance and renovations in Melbourne
                    </h1>
                    <p className="mt-6 max-w-2xl text-lg text-muted-foreground">
                        From a leaking tap to a new bathroom, tell us what needs
                        doing and we'll match the job with a trusted local
                        tradesperson and send you a quote.
                    </p>
                    <div className="mt-8 flex flex-wrap gap-3">
                        <Button size="lg" asChild>
                            <Link href={create()}>
                                Request a quote
                                <ArrowRight aria-hidden="true" />
                            </Link>
                        </Button>
                        <Button size="lg" variant="outline" asChild>
                            <a href="#services">See our services</a>
                        </Button>
                    </div>
                </div>
            </section>

            <section
                id="services"
                aria-labelledby="services-heading"
                className="mx-auto max-w-6xl scroll-mt-4 px-4 py-16 sm:px-6"
            >
                <h2
                    id="services-heading"
                    className="text-2xl font-semibold tracking-tight md:text-3xl"
                >
                    Services
                </h2>
                <p className="mt-2 max-w-2xl text-muted-foreground">
                    Repairs, maintenance and renovations for houses, apartments
                    and townhouses across Melbourne.
                </p>
                <ul className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {services.map((service) => (
                        <li key={service.value}>
                            <Link
                                href={create({
                                    query: { category: service.value },
                                })}
                                className="group flex h-full flex-col rounded-xl border p-5 transition-colors hover:border-orange-300 hover:bg-orange-50/50 dark:hover:border-orange-800 dark:hover:bg-orange-950/20"
                            >
                                <h3 className="font-medium">{service.label}</h3>
                                <p className="mt-1 flex-1 text-sm text-muted-foreground">
                                    {service.description}
                                </p>
                                <span className="mt-4 inline-flex items-center gap-1 text-sm font-medium text-orange-700 dark:text-orange-400">
                                    Get a {service.label.toLowerCase()} quote
                                    <ArrowRight
                                        className="size-4 transition-transform group-hover:translate-x-0.5"
                                        aria-hidden="true"
                                    />
                                </span>
                            </Link>
                        </li>
                    ))}
                </ul>
            </section>

            <section
                id="how-it-works"
                aria-labelledby="how-it-works-heading"
                className="scroll-mt-4 border-y bg-muted/40"
            >
                <div className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                    <h2
                        id="how-it-works-heading"
                        className="text-2xl font-semibold tracking-tight md:text-3xl"
                    >
                        How it works
                    </h2>
                    <ol className="mt-8 grid gap-8 md:grid-cols-3">
                        {steps.map((step, index) => (
                            <li key={step.title} className="flex gap-4">
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-orange-600 text-white">
                                    <step.icon
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                </span>
                                <div>
                                    <h3 className="font-medium">
                                        <span className="sr-only">
                                            Step {index + 1}:{' '}
                                        </span>
                                        {step.title}
                                    </h3>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {step.description}
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ol>
                </div>
            </section>

            <section
                aria-labelledby="areas-heading"
                className="mx-auto max-w-6xl px-4 py-16 sm:px-6"
            >
                <h2
                    id="areas-heading"
                    className="text-2xl font-semibold tracking-tight md:text-3xl"
                >
                    Areas we cover
                </h2>
                <p className="mt-2 max-w-2xl text-muted-foreground">
                    We take jobs across metropolitan Melbourne. If you're not
                    sure whether we cover your suburb, send a request and we'll
                    let you know.
                </p>
                <ul className="mt-6 flex flex-wrap gap-2">
                    {serviceAreas.map((area) => (
                        <li
                            key={area}
                            className="rounded-full border px-3 py-1 text-sm"
                        >
                            {area}
                        </li>
                    ))}
                </ul>
            </section>

            <section
                id="faq"
                aria-labelledby="faq-heading"
                className="scroll-mt-4 border-t bg-muted/40"
            >
                <div className="mx-auto max-w-3xl px-4 py-16 sm:px-6">
                    <h2
                        id="faq-heading"
                        className="text-2xl font-semibold tracking-tight md:text-3xl"
                    >
                        Frequently asked questions
                    </h2>
                    <div className="mt-8 divide-y rounded-xl border bg-background">
                        {faqs.map((faq) => (
                            <details key={faq.question} className="group p-5">
                                <summary className="flex cursor-pointer list-none items-center justify-between gap-4 font-medium [&::-webkit-details-marker]:hidden">
                                    <h3>{faq.question}</h3>
                                    <span
                                        className="text-xl leading-none text-muted-foreground transition-transform group-open:rotate-45"
                                        aria-hidden="true"
                                    >
                                        +
                                    </span>
                                </summary>
                                <p className="mt-3 text-muted-foreground">
                                    {faq.answer}
                                </p>
                            </details>
                        ))}
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-6xl px-4 py-16 text-center sm:px-6">
                <h2 className="text-2xl font-semibold tracking-tight md:text-3xl">
                    Got a job that needs doing?
                </h2>
                <p className="mx-auto mt-2 max-w-xl text-muted-foreground">
                    It only takes a couple of minutes to tell us about it.
                </p>
                <Button size="lg" className="mt-6" asChild>
                    <Link href={create()}>
                        Request a quote
                        <ArrowRight aria-hidden="true" />
                    </Link>
                </Button>
            </section>
        </>
    );
}
