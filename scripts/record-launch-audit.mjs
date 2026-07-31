/**
 * Record Ledrix launch audit video (admin 2m + seller 2m + client 1m) → MP4.
 *
 * Usage:
 *   set RECORD_ADMIN_EMAIL=...
 *   set RECORD_ADMIN_PASSWORD=...
 *   set RECORD_SELLER_EMAIL=...
 *   set RECORD_SELLER_PASSWORD=...
 *   set RECORD_CLIENT_EMAIL=...
 *   set RECORD_CLIENT_PASSWORD=...
 *   node scripts/record-launch-audit.mjs
 */
import { chromium } from 'playwright';
import ffmpegPath from 'ffmpeg-static';
import { execFileSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, '..');
const BASE = process.env.RECORD_BASE_URL || 'https://ledrix.co';
const OUT_DIR = path.join(root, 'storage', 'launch-recordings');
const FINAL_MP4 = path.join(root, 'public', 'front-assets', 'media', 'ledrix-launch-audit.mp4');

const CREDS = {
    admin: {
        email: process.env.RECORD_ADMIN_EMAIL || '',
        password: process.env.RECORD_ADMIN_PASSWORD || '',
    },
    seller: {
        email: process.env.RECORD_SELLER_EMAIL || '',
        password: process.env.RECORD_SELLER_PASSWORD || '',
    },
    client: {
        email: process.env.RECORD_CLIENT_EMAIL || '',
        password: process.env.RECORD_CLIENT_PASSWORD || '',
    },
};

function assertCreds() {
    for (const [role, c] of Object.entries(CREDS)) {
        if (!c.email || !c.password) {
            throw new Error(`Missing RECORD_${role.toUpperCase()}_EMAIL / RECORD_${role.toUpperCase()}_PASSWORD`);
        }
    }
}

function sleep(page, ms) {
    return page.waitForTimeout(ms);
}

async function login(page, loginPath, email, password) {
    await page.goto(`${BASE}${loginPath}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForSelector('#email', { timeout: 30000 });
    await page.fill('#email', email);
    await page.fill('#password', password);
    await page.click('button.btn-submit');
    await page.waitForLoadState('networkidle', { timeout: 60000 }).catch(() => {});
    await sleep(page, 2000);
}

async function visit(page, pathSuffix, ms) {
    await page.goto(`${BASE}${pathSuffix}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await sleep(page, ms);
}

async function recordSegment(name, run) {
    fs.mkdirSync(OUT_DIR, { recursive: true });

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        recordVideo: { dir: OUT_DIR, size: { width: 1280, height: 720 } },
        viewport: { width: 1280, height: 720 },
        ignoreHTTPSErrors: true,
    });
    const page = await context.newPage();
    page.setDefaultTimeout(60000);

    await run(page);

    const video = page.video();
    await context.close();
    await browser.close();

    const webmPath = await video.path();
    const target = path.join(OUT_DIR, `${name}.webm`);
    if (fs.existsSync(target)) {
        fs.unlinkSync(target);
    }
    fs.renameSync(webmPath, target);
    console.log('Recorded segment:', name, '→', target);
    return target;
}

async function recordAdmin(page) {
    const { email, password } = CREDS.admin;
    await login(page, '/admin/login', email, password);
    await visit(page, '/admin/dashboard', 18000);
    await visit(page, '/admin/leads', 22000);
    await visit(page, '/admin/orders', 22000);
    await visit(page, '/admin/clients', 20000);
    await visit(page, '/admin/sellers', 20000);
    await visit(page, '/admin/payments', 16000);
}

async function recordSeller(page) {
    const { email, password } = CREDS.seller;
    await login(page, '/seller/login', email, password);
    await visit(page, '/seller/dashboard', 18000);
    await visit(page, '/seller/leads', 22000);
    await visit(page, '/seller/orders', 22000);
    await visit(page, '/seller/clients', 20000);
    await visit(page, '/seller/dashboard', 16000);
}

async function recordClient(page) {
    const { email, password } = CREDS.client;
    await login(page, '/client/login', email, password);
    await visit(page, '/client/dashboard', 15000);
    await visit(page, '/client/invoices', 20000);
    await page.goto(`${BASE}/client/invoices`, { waitUntil: 'domcontentloaded' });
    const detail = page.locator('a[href*="/client/invoice/"]').first();
    if (await detail.count()) {
        await detail.click();
        await sleep(page, 20000);
    } else {
        await sleep(page, 20000);
    }
    await visit(page, '/client/dashboard', 5000);
}

function mergeToMp4(webmFiles, output) {
    if (!ffmpegPath) {
        throw new Error('ffmpeg-static not available');
    }

    fs.mkdirSync(path.dirname(output), { recursive: true });

    const listFile = path.join(OUT_DIR, 'concat.txt');
    const content = webmFiles.map((f) => `file '${f.replace(/\\/g, '/')}'`).join('\n');
    fs.writeFileSync(listFile, content);

    execFileSync(ffmpegPath, [
        '-y',
        '-f', 'concat',
        '-safe', '0',
        '-i', listFile,
        '-c:v', 'libx264',
        '-preset', 'fast',
        '-crf', '23',
        '-pix_fmt', 'yuv420p',
        '-movflags', '+faststart',
        output,
    ], { stdio: 'inherit' });

    console.log('MP4 saved:', output);
}

async function main() {
    assertCreds();
    fs.mkdirSync(OUT_DIR, { recursive: true });

    console.log('Recording admin segment (~2 min)...');
    const admin = await recordSegment('01-admin', recordAdmin);

    console.log('Recording seller segment (~2 min)...');
    const seller = await recordSegment('02-seller', recordSeller);

    console.log('Recording client segment (~1 min)...');
    const client = await recordSegment('03-client', recordClient);

    console.log('Merging to MP4...');
    mergeToMp4([admin, seller, client], FINAL_MP4);

    const stats = fs.statSync(FINAL_MP4);
    console.log(`Done. Size: ${(stats.size / 1024 / 1024).toFixed(2)} MB`);
}

main().catch((err) => {
    console.error(err);
    process.exit(1);
});
