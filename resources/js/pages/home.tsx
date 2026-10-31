import { Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BadgeCheck,
    ClipboardList,
    Droplets,
    Fence,
    Grid2x2,
    Hammer,
    House,
    ImagePlus,
    Layers,
    MapPin,
    MessageSquareText,
    Paintbrush,
    PaintRoller,
    Phone,
    Wrench,
    Zap,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
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

const trustPoints = [
    'Vetted tradespeople',
    'Fully insured',
    'Licensed where required',
];

// Featured services get a large photo card. The rest are listed with an icon.
const featuredImages: Record<
    string,
    { src: string; alt: string; width: number; height: number }
> = {
    kitchen_renovation: {
        src: '/images/service-kitchen.webp',
        alt: 'Renovated kitchen with navy cabinets and a pale stone benchtop',
        width: 900,
        height: 507,
    },
    bathroom_renovation: {
        src: '/images/service-bathroom.webp',
        alt: 'Renovated bathroom with a walk-in shower and timber vanity',
        width: 900,
        height: 507,
    },
    landscaping: {
        src: '/images/service-outdoor.webp',
        alt: 'Melbourne backyard with new paving, a timber fence and native plants',
        width: 900,
        height: 491,
    },
};

const serviceIcons: Record<string, LucideIcon> = {
    plumbing: Droplets,
    electrical: Zap,
    carpentry: Hammer,
    painting: Paintbrush,
    plastering: PaintRoller,
    tiling: Grid2x2,
    roofing: House,
    flooring: Layers,
    fencing: Fence,
    general_handyman: Wrench,
};

export default function Home({
    services,
    serviceAreas,
    faqs,
    structuredData,
}: Props) {
    const { site } = usePage().props;
    const featuredServices = Object.keys(featuredImages).flatMap(
        (value) => services.find((service) => service.value === value) ?? [],
    );
    const otherServices = services.filter(
        (service) => !(service.value in featuredImages),
    );

    return (
        <>
            <Seo
                title="Home maintenance & renovations in Melbourne"
                description="Repairs, maintenance and renovations across Melbourne: plumbing, electrical, carpentry, painting, tiling, kitchens, bathrooms and more. Request a quote online."
                jsonLd={structuredData}
            />

            <section className="border-b bg-muted/60">
                <div className="mx-auto grid max-w-6xl items-center gap-10 px-4 py-12 sm:px-6 md:py-20 lg:grid-cols-2">
                    <div>
                        <p className="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-cta-text">
                            <MapPin className="size-4" aria-hidden="true" />
                            Melbourne, Victoria
                        </p>
                        <h1 className="text-4xl font-semibold tracking-tight text-balance md:text-5xl">
                            Home maintenance and renovations in Melbourne
                        </h1>
                        <p className="mt-6 max-w-2xl text-lg text-muted-foreground">
                            From a leaking tap to a new bathroom, tell us what
                            needs doing and we'll match the job with a vetted,
                            insured local tradesperson, licensed where the work
                            requires it, and send you a quote.
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Button variant="cta" size="lg" asChild>
                                <Link href={create()}>
                                    Request a quote
                                    <ArrowRight aria-hidden="true" />
                                </Link>
                            </Button>
                            <Button size="lg" variant="outline" asChild>
                                <a href="#services">See our services</a>
                            </Button>
                        </div>
                        <ul className="mt-8 flex flex-wrap gap-x-5 gap-y-2 text-sm font-medium">
                            {trustPoints.map((point) => (
                                <li
                                    key={point}
                                    className="inline-flex items-center gap-1.5"
                                >
                                    <BadgeCheck
                                        className="size-4 text-cta-text"
                                        aria-hidden="true"
                                    />
                                    {point}
                                </li>
                            ))}
                            {site.phone && (
                                <li>
                                    <a
                                        href={`tel:${site.phone.replace(/[^\d+]/g, '')}`}
                                        className="inline-flex items-center gap-1.5"
                                    >
                                        <Phone
                                            className="size-4 text-cta-text"
                                            aria-hidden="true"
                                        />
                                        {site.phone}
                                    </a>
                                </li>
                            )}
                            {site.abn && <li>ABN {site.abn}</li>}
                        </ul>
                    </div>
                    <img
                        src="/images/hero.webp"
                        alt="Tradesperson fitting a new mixer tap in a bright Melbourne kitchen"
                        width={1400}
                        height={788}
                        fetchPriority="high"
                        className="aspect-[4/3] w-full rounded-xl object-cover object-right"
                    />
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
                    {featuredServices.map((service) => {
                        const image = featuredImages[service.value];

                        return (
                            <li key={service.value}>
                                <Link
                                    href={create({
                                        query: { category: service.value },
                                    })}
                                    className="group flex h-full flex-col overflow-hidden rounded-xl border bg-card transition-colors hover:border-cta-text"
                                >
                                    <img
                                        src={image.src}
                                        alt={image.alt}
                                        width={image.width}
                                        height={image.height}
                                        loading="lazy"
                                        className="aspect-[3/2] w-full object-cover"
                                    />
                                    <div className="flex flex-1 flex-col p-5">
                                        <h3 className="font-medium">
                                            {service.label}
                                        </h3>
                                        <p className="mt-1 flex-1 text-sm text-muted-foreground">
                                            {service.description}
                                        </p>
                                        <span className="mt-4 inline-flex items-center gap-1 text-sm font-medium text-cta-text">
                                            Get a {service.label.toLowerCase()}{' '}
                                            quote
                                            <ArrowRight
                                                className="size-4 transition-transform group-hover:translate-x-0.5"
                                                aria-hidden="true"
                                            />
                                        </span>
                                    </div>
                                </Link>
                            </li>
                        );
                    })}
                </ul>
                <ul className="mt-4 grid gap-4 sm:grid-cols-2">
                    {otherServices.map((service) => {
                        const Icon = serviceIcons[service.value] ?? Wrench;

                        return (
                            <li key={service.value}>
                                <Link
                                    href={create({
                                        query: { category: service.value },
                                    })}
                                    className="flex h-full gap-3 rounded-xl border bg-card p-4 transition-colors hover:border-cta-text"
                                >
                                    <Icon
                                        className="mt-0.5 size-5 shrink-0 text-cta-text"
                                        aria-hidden="true"
                                    />
                                    <div>
                                        <h3 className="font-medium">
                                            {service.label}
                                        </h3>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {service.description}
                                        </p>
                                    </div>
                                </Link>
                            </li>
                        );
                    })}
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
                    <div className="mt-8 grid items-center gap-10 lg:grid-cols-2">
                        <ol className="grid gap-8">
                            {steps.map((step, index) => (
                                <li key={step.title} className="flex gap-4">
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-cta text-white">
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
                        <img
                            src="/images/how-it-works.webp"
                            alt="Homeowner photographing a leaking pipe under a vanity with a phone"
                            width={1000}
                            height={546}
                            loading="lazy"
                            className="aspect-[16/9] w-full rounded-xl object-cover"
                        />
                    </div>
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
                            className="rounded-full border bg-card px-3 py-1 text-sm"
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

            <section className="bg-navy text-white">
                <div className="mx-auto max-w-6xl px-4 py-16 text-center sm:px-6">
                    <h2 className="text-2xl font-semibold tracking-tight md:text-3xl">
                        Got a job that needs doing?
                    </h2>
                    <p className="mx-auto mt-2 max-w-xl text-white/80">
                        It only takes a couple of minutes to tell us about it.
                    </p>
                    <Button variant="cta" size="lg" className="mt-6" asChild>
                        <Link href={create()}>
                            Request a quote
                            <ArrowRight aria-hidden="true" />
                        </Link>
                    </Button>
                </div>
            </section>
        </>
    );
}
