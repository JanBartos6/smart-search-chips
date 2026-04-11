import { chromium } from '@playwright/test';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = resolve(__dirname, '..');

const browser = await chromium.launch();
const context = await browser.newContext({
    viewport: { width: 1280, height: 800 },
    ignoreHTTPSErrors: true,
});
const page = await context.newPage();

// Screenshot 1: Live search with chips appearing
await page.goto('https://smartsearchchips.app/search/');
await page.waitForTimeout(1500);
await page.evaluate(() => {
    document.querySelectorAll('[data-reveal]').forEach(el => el.classList.add('is-visible'));
    document.documentElement.style.scrollBehavior = 'auto';
});
const input = await page.$('.ssc-search__input');
await input.fill('tea');
await input.dispatchEvent('input');
await page.evaluate(() => {
    var input = document.querySelector('.ssc-search__input');
    var form = input.closest('form');
    if (form && typeof form.requestSubmit === 'function') form.requestSubmit();
});
await page.waitForTimeout(3000);
// Scroll so the search + chips + results are centered
await page.evaluate(() => {
    var el = document.querySelector('.ssc-chips') || document.querySelector('.ssc-search__input');
    if (el) el.scrollIntoView({ block: 'start' });
    window.scrollBy(0, -80);
});
await page.waitForTimeout(500);
await page.screenshot({ path: resolve(root, 'screenshot-1.png') });
console.log('Screenshot 1: Search with chips');

// Screenshot 2: Click a chip to show filtering
await page.evaluate(() => {
    var chips = document.querySelectorAll('.ssc-chip');
    if (chips.length > 0) chips[0].click();
});
await page.waitForTimeout(2500);
await page.evaluate(() => {
    var el = document.querySelector('.ssc-chips') || document.querySelector('.ssc-search__input');
    if (el) el.scrollIntoView({ block: 'start' });
    window.scrollBy(0, -80);
});
await page.waitForTimeout(500);
await page.screenshot({ path: resolve(root, 'screenshot-2.png') });
console.log('Screenshot 2: Filtered after chip click');

// Screenshot 3: Admin settings
await page.goto('https://smartsearchchips.app/wp-login.php');
await page.waitForTimeout(1000);
await page.fill('#user_login', 'janbartos');
await page.fill('#user_pass', '5O5tpUFf1sopXDp9z6hZzl5V7kIa7cPq');
await page.click('#wp-submit');
await page.waitForTimeout(2500);
await page.goto('https://smartsearchchips.app/wp-admin/options-general.php?page=smart-search-chips');
await page.waitForTimeout(2000);
await page.screenshot({ path: resolve(root, 'screenshot-3.png') });
console.log('Screenshot 3: Admin settings');

await browser.close();
console.log('Done');
