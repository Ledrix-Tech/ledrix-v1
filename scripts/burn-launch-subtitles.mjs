/**
 * Burn subtitles into launch video — keeps original audio (your voiceover).
 *
 *   node scripts/burn-launch-subtitles.mjs
 */
import fs from 'fs';
import path from 'path';
import { execFileSync } from 'child_process';
import { fileURLToPath } from 'url';
import ffmpegPath from 'ffmpeg-static';
import ffprobePath from 'ffprobe-static';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, '..');

const INPUT = process.env.LAUNCH_VIDEO_IN
    || path.join(root, 'public/front-assets/media/ledrix-crm-audit-v1.mp4');
const OUTPUT = process.env.LAUNCH_VIDEO_OUT
    || path.join(root, 'public/front-assets/media/ledrix-crm-audit-v1-subtitled.mp4');
const NARRATION = process.env.LAUNCH_NARRATION
    || path.join(__dirname, 'launch-narration-audit-v1.json');
const WORK = path.join(root, 'storage/launch-voiceover');

function probeDuration(file) {
    const out = execFileSync(ffprobePath.path, [
        '-v', 'error',
        '-show_entries', 'format=duration',
        '-of', 'default=noprint_wrappers=1:nokey=1',
        file,
    ], { encoding: 'utf8' });
    return parseFloat(out.trim());
}

function formatSrtTime(seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    const ms = Math.round((seconds % 1) * 1000);
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')},${String(ms).padStart(3, '0')}`;
}

function buildSrt(lines, totalDuration) {
    const padStart = 2;
    const padEnd = 3;
    const usable = Math.max(totalDuration - padStart - padEnd, lines.length * 3);
    const slot = usable / lines.length;
    const entries = lines.map((text, i) => ({
        start: padStart + i * slot,
        end: padStart + (i + 1) * slot - 0.05,
        text,
    }));
    return entries.map((e, i) =>
        `${i + 1}\n${formatSrtTime(e.start)} --> ${formatSrtTime(e.end)}\n${e.text}\n`,
    ).join('\n');
}

function burnSubs(video, srt, out) {
    fs.mkdirSync(path.dirname(out), { recursive: true });
    const srtUnix = srt.replace(/\\/g, '/').replace(/:/g, '\\:');
    execFileSync(ffmpegPath, [
        '-y',
        '-i', video,
        '-c:v', 'libx264', '-preset', 'fast', '-crf', '22',
        '-c:a', 'copy',
        '-vf', `subtitles='${srtUnix}':force_style='FontName=Segoe UI,FontSize=20,PrimaryColour=&HFFFFFF,OutlineColour=&H000000,Outline=2,Shadow=1,MarginV=36'`,
        '-movflags', '+faststart',
        out,
    ], { stdio: 'inherit' });
}

function main() {
    if (!fs.existsSync(INPUT)) {
        console.error('Missing:', INPUT);
        process.exit(1);
    }

    const config = JSON.parse(fs.readFileSync(NARRATION, 'utf8'));
    fs.mkdirSync(WORK, { recursive: true });

    const duration = probeDuration(INPUT);
    console.log('Duration:', duration.toFixed(1), 's');

    const srtFile = path.join(WORK, 'audit-v1.srt');
    fs.writeFileSync(srtFile, buildSrt(config.lines, duration), 'utf8');
    console.log('Subtitles written:', srtFile);

    console.log('Burning subtitles (keeping your audio)...');
    burnSubs(INPUT, srtFile, OUTPUT);
    console.log('Done:', OUTPUT);
}

main();
