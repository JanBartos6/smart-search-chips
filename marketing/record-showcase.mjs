import { chromium } from '@playwright/test';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';

const __dirname = dirname(fileURLToPath(import.meta.url));
const showcasePath = resolve(__dirname, 'showcase.html');

const browser = await chromium.launch();
const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
    recordVideo: {
        dir: resolve(__dirname, 'video'),
        size: { width: 1920, height: 1080 },
    },
});

const page = await context.newPage();
await page.goto(`file://${showcasePath.replace(/\\/g, '/')}`);

// Wait for all 7 slides to finish (~19s total, add buffer)
await page.waitForTimeout(24000);

await context.close();
await browser.close();

console.log('Video saved to marketing/video/');
