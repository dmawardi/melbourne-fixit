import { router } from '@inertiajs/react';

declare global {
    interface Window {
        gtag?: (...args: unknown[]) => void;
    }
}

// Pages anyone can visit, shown with the marketing site's header and footer.
export const PUBLIC_PAGES = [
    'home',
    'job-requests/create',
    'job-requests/submitted',
];

const isPublicPage = (component: string) => PUBLIC_PAGES.includes(component);

// URLs can carry personal data (e.g. /register?email=...), which must never
// reach Google. Only campaign attribution params are kept.
function safeLocation(url: string): string {
    const { origin, pathname, searchParams } = new URL(
        url,
        window.location.origin,
    );

    const kept = new URLSearchParams();

    searchParams.forEach((value, key) => {
        if (key.startsWith('utm_') || key === 'gclid') {
            kept.set(key, value);
        }
    });

    const query = kept.toString();

    return `${origin}${pathname}${query ? `?${query}` : ''}`;
}

export function initializeAnalytics(): void {
    if (typeof window === 'undefined' || !window.gtag) {
        return;
    }

    const { gtag } = window;

    let onPublicPage = false;

    router.on('navigate', ({ detail: { page, type } }) => {
        onPublicPage = isPublicPage(page.component);

        gtag('set', { page_location: safeLocation(page.url) });

        if (type === 'initial' && document.referrer) {
            gtag('set', { page_referrer: new URL(document.referrer).origin });
        }

        if (!onPublicPage) {
            return;
        }

        gtag('event', 'page_view');

        if (page.component === 'job-requests/submitted' && type !== 'history') {
            gtag('event', 'generate_lead', {
                emergency: Boolean(page.props.emergency),
            });
        }
    });

    document.addEventListener('click', (event) => {
        if (!onPublicPage || !(event.target instanceof Element)) {
            return;
        }

        const link = event.target.closest<HTMLAnchorElement>(
            'a[href^="tel:"], a[href^="mailto:"]',
        );

        if (link) {
            gtag(
                'event',
                link.protocol === 'tel:' ? 'phone_click' : 'email_click',
            );
        }
    });
}
