import { chromium } from '@playwright/test';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';
import { mkdir, readdir, rename, stat, unlink } from 'fs/promises';

const __dirname = dirname(fileURLToPath(import.meta.url));
const showcasePath = resolve(__dirname, 'video-motion.html');
const videoDir = resolve(__dirname, 'video');
const finalVideoPath = resolve(videoDir, 'smart-search-chips-motion-showcase.webm');
const frameTimes = [
    { name: 'motion-frame-01-type.png', frame: 1400 },
    { name: 'motion-frame-02-chips.png', frame: 3200 },
    { name: 'motion-frame-03-range.png', frame: 4400 },
    { name: 'motion-frame-04-filter.png', frame: 6100 },
    { name: 'motion-frame-05-refine.png', frame: 8700 },
    { name: 'motion-frame-06-final.png', frame: 10300 },
];

await mkdir(videoDir, { recursive: true });

for (const path of [
    finalVideoPath,
    resolve(videoDir, 'motion-frame-01-search.png'),
    resolve(videoDir, 'motion-frame-02-range.png'),
    resolve(videoDir, 'motion-frame-03-diagnostics.png'),
    resolve(videoDir, 'motion-frame-04-analytics.png'),
    resolve(videoDir, 'motion-frame-03-filter.png'),
    resolve(videoDir, 'motion-frame-04-refine.png'),
    resolve(videoDir, 'motion-frame-05-workflows.png'),
    resolve(videoDir, 'motion-frame-05-final.png'),
    resolve(videoDir, 'motion-frame-06-final.png'),
    ...frameTimes.map((item) => resolve(videoDir, item.name)),
]) {
    try {
        await unlink(path);
    } catch (error) {
        if (error.code !== 'ENOENT') {
            throw error;
        }
    }
}

const browser = await chromium.launch();

const stillContext = await browser.newContext({ viewport: { width: 1920, height: 1080 } });
const stillPage = await stillContext.newPage();

for (const item of frameTimes) {
    await stillPage.goto(`file://${showcasePath.replace(/\\/g, '/')}?frame=${item.frame}`);
    await stillPage.waitForFunction(() => window.sschpMotionDone === true, null, { timeout: 10000 });
    await stillPage.waitForTimeout(1100);
    await stillPage.screenshot({ path: resolve(videoDir, item.name) });
    console.log(`Captured ${item.name}`);
}

await stillContext.close();

const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
    recordVideo: {
        dir: videoDir,
        size: { width: 1920, height: 1080 },
    },
});

const page = await context.newPage();
await page.goto(`file://${showcasePath.replace(/\\/g, '/')}`);
await page.waitForFunction(() => window.sschpMotionDone === true, null, { timeout: 45000 });
await page.waitForTimeout(800);
await context.close();
await browser.close();

const videos = await readdir(videoDir);
const webmVideos = [];

for (const file of videos) {
    if (!file.endsWith('.webm') || file === 'smart-search-chips-motion-showcase.webm') {
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
