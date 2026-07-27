/**
 * Remove solid backgrounds from Ledrix logo & favicon PNGs (transparent PNG output).
 * Run: node scripts/process-brand-assets.mjs
 */
import { Jimp } from 'jimp';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, '..');

function colorDistance(r1, g1, b1, r2, g2, b2) {
    return Math.sqrt((r1 - r2) ** 2 + (g1 - g2) ** 2 + (b1 - b2) ** 2);
}

function removeMatchingPixels(image, matcher) {
    image.scan(0, 0, image.bitmap.width, image.bitmap.height, function (x, y, idx) {
        const r = this.bitmap.data[idx];
        const g = this.bitmap.data[idx + 1];
        const b = this.bitmap.data[idx + 2];

        if (matcher(r, g, b)) {
            this.bitmap.data[idx + 3] = 0;
        }
    });
}

async function removeWhiteBackground(file) {
    const image = await Jimp.read(file);

    removeMatchingPixels(image, (r, g, b) => r >= 232 && g >= 232 && b >= 232);
    removeMatchingPixels(image, (r, g, b) => colorDistance(r, g, b, 255, 255, 255) <= 28);

    await image.write(file);
    console.log('Transparent wordmark:', path.relative(root, file));
}

async function removeSolidBackground(file) {
    const image = await Jimp.read(file);
    const corners = [
        [0, 0],
        [image.bitmap.width - 1, 0],
        [0, image.bitmap.height - 1],
        [image.bitmap.width - 1, image.bitmap.height - 1],
    ];

    const samples = corners.map(([x, y]) => {
        const idx = image.getPixelIndex(x, y);
        return [
            image.bitmap.data[idx],
            image.bitmap.data[idx + 1],
            image.bitmap.data[idx + 2],
        ];
    });

    removeMatchingPixels(image, (r, g, b) => {
        // Keep white/light logo strokes intact.
        if (r > 210 && g > 210 && b > 210) {
            return false;
        }

        return samples.some(([tr, tg, tb]) => colorDistance(r, g, b, tr, tg, tb) <= 42);
    });

    await image.write(file);
    console.log('Transparent icon:', path.relative(root, file));
}

async function writeFaviconSizes(source) {
    const base = await Jimp.read(source);

    const sizes = [
        ['public/front-assets/imgs/favicon-32.png', 32],
        ['public/front-assets/imgs/apple-touch-icon.png', 180],
        ['public/admin-assets/dpm-logos/fv-icon.png', 180],
    ];

    for (const [rel, size] of sizes) {
        const out = path.join(root, rel);
        const clone = base.clone().resize({ w: size, h: size });
        await clone.write(out);
        console.log('Favicon size:', rel);
    }
}

const wordmarks = [
    'public/front-assets/imgs/logo-ic.png',
    'public/admin-assets/dpm-logos/logo-ic.png',
];

const icons = [
    'public/front-assets/imgs/fv-icon.png',
];

for (const rel of wordmarks) {
    await removeWhiteBackground(path.join(root, rel));
}

for (const rel of icons) {
    await removeSolidBackground(path.join(root, rel));
}

await writeFaviconSizes(path.join(root, 'public/front-assets/imgs/fv-icon.png'));

console.log('Done.');
