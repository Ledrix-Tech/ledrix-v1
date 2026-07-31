/**
 * Add AI voiceover + burned-in subtitles to a launch screen-recording.
 *
 * 1. Place your video at: public/front-assets/media/launch-source.mp4
 * 2. Run: node scripts/add-launch-voiceover.mjs
 * 3. Output: public/front-assets/media/ledrix-launch-final.mp4
 *
 * Requires: npm install --no-save edge-tts-universal ffmpeg-static ffprobe-static
 */
import fs from 'fs';
import path from 'path';
import { execFileSync } from 'child_process';
import { fileURLToPath } from 'url';
import { MsEdgeTTS, OUTPUT_FORMAT } from 'edge-tts-universal';
import ffmpegPath from 'ffmpeg-static';
import ffprobePath from 'ffprobe-static';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, '..');

const INPUT = process.env.LAUNCH_VIDEO_IN
    || path.join(root, 'public/front-assets/media/launch-source.mp4');
const OUTPUT = process.env.LAUNCH_VIDEO_OUT
    || path.join(root, 'public/front-assets/media/ledrix-launch-final.mp4');
const NARRATION = process.env.LAUNCH_NARRATION
    || path.join(__dirname, 'launch-narration-admin.json');
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

function writeSrt(entries, file) {
    const body = entries.map((e, i) => {
        return `${i + 1}\n${formatSrtTime(e.start)} --> ${formatSrtTime(e.end)}\n${e.text}\n`;
    }).join('\n');
    fs.writeFileSync(file, body, 'utf8');
}

async function synthesizeLine(text, voice, outMp3) {
    const tts = new MsEdgeTTS();
    await tts.setMetadata(voice, OUTPUT_FORMAT.AUDIO_24KHZ_48KBITRATE_MONO_MP3);
    const readable = tts.toStream(text);
    await new Promise((resolve, reject) => {
        const chunks = [];
        readable.on('data', (c) => chunks.push(c));
        readable.on('end', () => {
            fs.writeFileSync(outMp3, Buffer.concat(chunks));
            resolve();
        });
        readable.on('error', reject);
    });
}

function concatAudio(parts, outFile) {
    const list = path.join(WORK, 'audio-list.txt');
    fs.writeFileSync(list, parts.map((p) => `file '${p.replace(/\\/g, '/')}'`).join('\n'));
    execFileSync(ffmpegPath, [
        '-y', '-f', 'concat', '-safe', '0', '-i', list,
        '-c', 'copy', outFile,
    ], { stdio: 'inherit' });
}

function mergeVideoAudioSubs(video, audio, srt, out) {
    fs.mkdirSync(path.dirname(out), { recursive: true });
    const escapedSrt = srt.replace(/\\/g, '/').replace(/:/g, '\\:');
    execFileSync(ffmpegPath, [
        '-y',
        '-i', video,
        '-i', audio,
        '-vf', `subtitles='${escapedSrt}':force_style='FontName=Segoe UI,FontSize=22,PrimaryColour=&HFFFFFF,OutlineColour=&H000000,Outline=2,Shadow=1,MarginV=40'`,
        '-c:v', 'libx264', '-preset', 'fast', '-crf', '22',
        '-c:a', 'aac', '-b:a', '128k',
        '-shortest',
        '-movflags', '+faststart',
        out,
    ], { stdio: 'inherit' });
}

async function main() {
    if (!fs.existsSync(INPUT)) {
        console.error(`Missing input video: ${INPUT}`);
        console.error('Place your file at public/front-assets/media/launch-source.mp4');
        process.exit(1);
    }

    const config = JSON.parse(fs.readFileSync(NARRATION, 'utf8'));
    const { voice, lines } = config;

    fs.mkdirSync(WORK, { recursive: true });

    console.log('Input video:', INPUT);
    const videoDuration = probeDuration(INPUT);
    console.log('Video duration:', videoDuration.toFixed(1), 's');

    const gap = 0.35;
    const mp3Parts = [];
    const srtEntries = [];
    let cursor = 0.5;

    for (let i = 0; i < lines.length; i++) {
        const mp3 = path.join(WORK, `line-${String(i + 1).padStart(2, '0')}.mp3`);
        console.log(`TTS line ${i + 1}/${lines.length}...`);
        await synthesizeLine(lines[i], voice, mp3);
        const dur = probeDuration(mp3);
        mp3Parts.push(mp3);
        srtEntries.push({ start: cursor, end: cursor + dur, text: lines[i] });
        cursor += dur + gap;
    }

    const narrationMp3 = path.join(WORK, 'narration-full.mp3');
    concatAudio(mp3Parts, narrationMp3);
    const audioDuration = probeDuration(narrationMp3);
    console.log('Narration duration:', audioDuration.toFixed(1), 's');

    if (audioDuration > videoDuration) {
        console.warn('Note: narration is longer than video — output will trim to video length (-shortest).');
    } else if (audioDuration < videoDuration - 2) {
        console.warn('Note: narration is shorter than video — last frames will be silent.');
    }

    const srtFile = path.join(WORK, 'subtitles.srt');
    writeSrt(srtEntries, srtFile);

    console.log('Merging video + voiceover + subtitles...');
    mergeVideoAudioSubs(INPUT, narrationMp3, srtFile, OUTPUT);

    console.log('Done:', OUTPUT);
}

main().catch((err) => {
    console.error(err);
    process.exit(1);
});
