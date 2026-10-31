// Builds the social sharing image from the home page hero. Run with: npm run og-image
import sharp from 'sharp';

const WIDTH = 1200;
const HEIGHT = 630;
const FONT = "'Helvetica Neue', Helvetica, Arial, sans-serif";

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
    <g transform="translate(80 170)">
        <rect width="72" height="72" rx="16" fill="#c2410c"/>
        <path transform="translate(12 12) scale(2)" fill="#fff" d="M12 2.5 1.5 11.5h3V21.5h6v-6.5h3v6.5h6V11.5h3L12 2.5Z"/>
    </g>
    <g font-family="${FONT}" fill="#fff">
        <text x="80" y="325" font-size="66" font-weight="700">Melbourne Fixit</text>
        <text x="80" y="392" font-size="34">Vetted, insured local tradespeople</text>
        <text x="80" y="448" font-size="26" fill="#f1ece4">Melbourne, Victoria</text>
    </g>
</svg>`;

await sharp('public/images/hero.webp')
    .resize(WIDTH, HEIGHT, { fit: 'cover', position: 'right' })
    .composite([{ input: Buffer.from(overlay) }])
    .png()
    .toFile('public/og-image.png');
