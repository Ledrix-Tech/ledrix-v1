# Launch video — AI voiceover + subtitles

## Step 1 — Add your video

Copy your screen recording to:

```
public/front-assets/media/launch-source.mp4
```

(Or rename your file to `launch-source.mp4`.)

## Step 2 — Install tools (one time)

```powershell
cd "F:\Zentra CRM\ledrix"
npm install --no-save edge-tts-universal ffmpeg-static ffprobe-static
```

## Step 3 — Generate final MP4

```powershell
node scripts/add-launch-voiceover.mjs
```

**Output:** `public/front-assets/media/ledrix-launch-final.mp4`

- AI voice: US English male (`en-US-GuyNeural`)
- Subtitles: burned into the video (white text, black outline)
- Script: `scripts/launch-narration-admin.json` (edit lines if needed)

## Custom paths

```powershell
$env:LAUNCH_VIDEO_IN="path\to\your.mp4"
$env:LAUNCH_VIDEO_OUT="path\to\output.mp4"
node scripts/add-launch-voiceover.mjs
```

## Female voice

Edit `scripts/launch-narration-admin.json` and set:

```json
"voice": "en-US-JennyNeural"
```
