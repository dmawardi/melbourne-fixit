// Builds the social sharing image from the home page hero. Run with: npm run og-image
// The wordmark needs Baloo 2 installed locally; without it the text silently falls back to the next font.
import sharp from 'sharp';

const WIDTH = 1200;
const HEIGHT = 630;
const FONT = "'Helvetica Neue', Helvetica, Arial, sans-serif";
const BRAND_FONT = "'Baloo 2', 'Helvetica Neue', Helvetica, Arial, sans-serif";
const LOGO_SIZE = 96;

const logo = await sharp('public/images/logo.webp')
    .resize(LOGO_SIZE, LOGO_SIZE)
    .composite([
        {
            input: Buffer.from(
                `<svg width="${LOGO_SIZE}" height="${LOGO_SIZE}"><rect width="${LOGO_SIZE}" height="${LOGO_SIZE}" rx="20"/></svg>`,
            ),
            blend: 'dest-in',
        },
    ])
    .png()
    .toBuffer();

const overlay = `
<svg xmlns="http://www.w3.org/2000/svg" width="${WIDTH}" height="${HEIGHT}">
    <defs>
        <linearGradient id="fade" x1="0" x2="1" y1="0" y2="0">
            <stop offset="0" stop-color="#17324d" stop-opacity="0.96"/>
            <stop offset="0.5" stop-color="#17324d" stop-opacity="0.88"/>
            <stop offset="0.72" stop-color="#17324d" stop-opacity="0"/>
        </linearGradient>
    </defs>
    <rect width="${WIDTH}" height="${HEIGHT}" fill="url(#fade)"/>
    <text x="80" y="330" font-family="${BRAND_FONT}" font-size="80" font-weight="800" fill="#fff">My <tspan fill="#fb923c">Reno</tspan> Mate</text>
    <g font-family="${FONT}" fill="#fff">
        <text x="80" y="392" font-size="34">Vetted, insured local tradespeople</text>
        <text x="80" y="448" font-size="26" fill="#f1ece4">Melbourne, Victoria</text>
    </g>
</svg>`;

await sharp('public/images/hero.webp')
    .resize(WIDTH, HEIGHT, { fit: 'cover', position: 'right' })
    .composite([
        { input: Buffer.from(overlay) },
        { input: logo, left: 80, top: 150 },
    ])
    .png()
    .toFile('public/og-image.png');
