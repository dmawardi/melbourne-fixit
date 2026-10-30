import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AppLogo from '@/components/app-logo';
import { Button } from '@/components/ui/button';
import { home, login, register } from '@/routes';
import { create, index } from '@/routes/job-requests';

const sectionLinks = [
    { label: 'Services', href: '/#services' },
    { label: 'How it works', href: '/#how-it-works' },
    { label: 'FAQ', href: '/#faq' },
];

export default function PublicLayout({ children }: { children: ReactNode }) {
    const { auth, name, site } = usePage().props;

    return (
        <div className="flex min-h-svh flex-col bg-background text-foreground">
            <a
                href="#main"
                className="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded-md focus:bg-background focus:px-3 focus:py-2 focus:shadow"
            >
                Skip to content
            </a>

            <header className="border-b">
                <div className="mx-auto flex h-16 max-w-6xl items-center gap-6 px-4 sm:px-6">
                    <Link
                        href={home()}
                        className="flex items-center"
                        aria-label={`${name} home`}
                    >
                        <AppLogo />
                    </Link>

                    <nav
                        aria-label="Main"
                        className="hidden items-center gap-6 text-sm md:flex"
                    >
                        {sectionLinks.map((link) => (
                            <a
                                key={link.href}
                                href={link.href}
                                className="text-muted-foreground transition-colors hover:text-foreground"
                            >
                                {link.label}
                            </a>
                        ))}
                    </nav>

                    <div className="ml-auto flex items-center gap-2">
                        {auth.user ? (
                            <Button variant="ghost" asChild>
                                <Link href={index()}>My requests</Link>
                            </Button>
                        ) : (
                            <Button variant="ghost" asChild>
                                <Link href={login()}>Log in</Link>
                            </Button>
                        )}
                        <Button asChild>
                            <Link href={create()}>
                                <span className="sm:hidden">Get a quote</span>
                                <span className="hidden sm:inline">
                                    Request a quote
                                </span>
                            </Link>
                        </Button>
                    </div>
                </div>
            </header>

            <main id="main" className="flex-1">
                {children}
            </main>

            <footer className="border-t bg-muted/40">
                <div className="mx-auto grid max-w-6xl gap-8 px-4 py-10 text-sm sm:px-6 md:grid-cols-3">
                    <div className="space-y-3">
                        <AppLogo />
                        <p className="text-muted-foreground">
                            {site.description}
                        </p>
                    </div>

                    <nav aria-label="Footer" className="space-y-2">
                        <h2 className="font-medium">Get started</h2>
                        <ul className="space-y-2 text-muted-foreground">
                            <li>
                                <Link
                                    href={create()}
                                    className="hover:text-foreground"
                                >
                                    Request a quote
                                </Link>
                            </li>
                            {auth.user ? (
                                <li>
                                    <Link
                                        href={index()}
                                        className="hover:text-foreground"
                                    >
                                        My requests
                                    </Link>
                                </li>
                            ) : (
                                <>
                                    <li>
                                        <Link
                                            href={login()}
                                            className="hover:text-foreground"
                                        >
                                            Log in
                                        </Link>
                                    </li>
                                    <li>
                                        <Link
                                            href={register()}
                                            className="hover:text-foreground"
                                        >
                                            Create an account
                                        </Link>
                                    </li>
                                </>
                            )}
                        </ul>
                    </nav>

                    <address className="space-y-2 not-italic">
                        <h2 className="font-medium">Contact</h2>
                        <ul className="space-y-2 text-muted-foreground">
                            <li>Serving metropolitan Melbourne, Victoria</li>
                            {site.phone && (
                                <li>
                                    <a
                                        href={`tel:${site.phone.replace(/[^\d+]/g, '')}`}
                                        className="hover:text-foreground"
                                    >
                                        {site.phone}
                                    </a>
                                </li>
                            )}
                            {site.email && (
                                <li>
                                    <a
                                        href={`mailto:${site.email}`}
                                        className="hover:text-foreground"
                                    >
                                        {site.email}
                                    </a>
                                </li>
                            )}
                            {site.abn && <li>ABN {site.abn}</li>}
                        </ul>
                    </address>
                </div>
                <p className="mx-auto max-w-6xl px-4 pb-8 text-xs text-muted-foreground sm:px-6">
                    © {new Date().getFullYear()} {name}
                </p>
            </footer>
        </div>
    );
}
