import { chromium } from '@playwright/test';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';
import { readdir, rename, stat, unlink } from 'fs/promises';

const __dirname = dirname(fileURLToPath(import.meta.url));
const showcasePath = resolve(__dirname, 'showcase.html');
const videoDir = resolve(__dirname, 'video');
const finalVideoPath = resolve(videoDir, 'smart-search-chips-showcase.webm');

try {
    await unlink(finalVideoPath);
} catch (error) {
    if (error.code !== 'ENOENT') {
        throw error;
    }
}

const browser = await chromium.launch();
const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
    recordVideo: {
        dir: videoDir,
        size: { width: 1920, height: 1080 },
    },
});

const page = await context.newPage();
await page.goto(`file://${showcasePath.replace(/\\/g, '/')}`);

// Wait for all slides to finish. Keep this explicit so the recording includes the final CTA.
await page.waitForTimeout(31000);

await context.close();
await browser.close();

const videos = await readdir(videoDir);
const webmVideos = [];

for (const file of videos) {
    if (!file.endsWith('.webm') || file === 'smart-search-chips-showcase.webm') {
        continue;
    }

    const fullPath = resolve(videoDir, file);
    const info = await stat(fullPath);
    webmVideos.push({ file, fullPath, mtimeMs: info.mtimeMs });
}

webmVideos.sort((a, b) => b.mtimeMs - a.mtimeMs);

if (!webmVideos.length) {
    throw new Error('No recorded WebM file was created.');
}

await rename(webmVideos[0].fullPath, finalVideoPath);
console.log(`Video saved to ${finalVideoPath}`);
