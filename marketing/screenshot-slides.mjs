import { chromium } from '@playwright/test';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';

const __dirname = dirname(fileURLToPath(import.meta.url));
const showcasePath = resolve(__dirname, 'showcase.html');

const browser = await chromium.launch();
const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
});
const page = await context.newPage();
await page.goto(`file://${showcasePath.replace(/\\/g, '/')}`);
await page.waitForTimeout(1000);

// Stop autoplay
await page.evaluate(() => {
    var id = window.setTimeout(function(){}, 0);
    while (id--) window.clearTimeout(id);
});

// Force all ambient chips visible
await page.evaluate(() => {
    document.querySelectorAll('.ambient-chip').forEach(c => c.classList.add('visible'));
});

const slides = await page.$$('.slide');
console.log(`Found ${slides.length} slides`);

for (let i = 0; i < slides.length; i++) {
    await page.evaluate((idx) => {
        var slides = document.querySelectorAll('.slide');
        slides.forEach(function(s, j) {
            s.classList.remove('active', 'exit');
            if (j < idx) s.classList.add('exit');
        });
        slides[idx].classList.add('active');
    }, i);

    await page.waitForTimeout(800); // let transitions finish
    await page.screenshot({ path: resolve(__dirname, `video/slide-${i + 1}.png`) });
    console.log(`Captured slide ${i + 1}`);
}

await browser.close();
console.log('All slides captured in marketing/video/');
