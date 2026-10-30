import { Head, usePage } from '@inertiajs/react';

type JsonLd = Record<string, unknown>;

type Props = {
    title: string;
    description: string;
    // Root-relative path to an image for social sharing. Defaults to the site-wide image.
    image?: string;
    type?: 'website' | 'article';
    jsonLd?: JsonLd[];
};

/**
 * Head tags for pages search engines and AI crawlers may index: description, canonical URL,
 * Open Graph and Twitter cards, and JSON-LD structured data. Rendered on the server by SSR.
 */
export default function Seo({
    title,
    description,
    image = '/og-image.png',
    type = 'website',
    jsonLd = [],
}: Props) {
    const page = usePage();
    const { appUrl, name } = page.props;
    const canonical = appUrl + new URL(page.url, appUrl).pathname;
    const fullTitle = `${title} - ${name}`;

    return (
        <Head title={title}>
            <meta
                head-key="description"
                name="description"
                content={description}
            />
            <link head-key="canonical" rel="canonical" href={canonical} />

            <meta head-key="og:type" property="og:type" content={type} />
            <meta head-key="og:title" property="og:title" content={fullTitle} />
            <meta
                head-key="og:description"
                property="og:description"
                content={description}
            />
            <meta head-key="og:url" property="og:url" content={canonical} />
            <meta
                head-key="og:image"
                property="og:image"
                content={appUrl + image}
            />

            <meta
                head-key="twitter:card"
                name="twitter:card"
                content="summary_large_image"
            />
            <meta
                head-key="twitter:title"
                name="twitter:title"
                content={fullTitle}
            />
            <meta
                head-key="twitter:description"
                name="twitter:description"
                content={description}
            />

            {jsonLd.map((data, index) => (
                <script
                    key={index}
                    head-key={`json-ld-${index}`}
                    type="application/ld+json"
                    // Escapes "<" so content can never close the script tag early.
                    dangerouslySetInnerHTML={{
                        __html: JSON.stringify(data).replace(/</g, '\\u003c'),
                    }}
                />
            ))}
        </Head>
    );
}
