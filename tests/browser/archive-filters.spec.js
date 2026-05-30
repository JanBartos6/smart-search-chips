const { test, expect } = require("@playwright/test");
const { escapedChipRegexp, countArchiveResults, isLocalWordPressAvailable } = require("./helpers");

test.describe("Archive and search results chip links", () => {
  test.beforeEach(async ({ request, baseURL }) => {
    test.skip(
      !(await isLocalWordPressAvailable(request, baseURL)),
      "Local WordPress test site is not running. Run `npm run local:wp:start` before local-site browser tests."
    );
  });

  test("chip links update URL, support back/forward, and can open in a new tab", async ({ page, context, browserName }) => {
    await page.goto("/?s=tent");

    await expect(page.locator(".ssc-loop-filters")).toBeVisible();
    await expect(page.locator(".ssc-loop-filters__title")).toHaveText("Refine This Search");
    await expect(page.locator(".ssc-loop-filters .ssc-chip")).toHaveCount(3);
    await expect(page.locator(".ssc-loop-filters .ssc-chip--active")).toHaveCount(0);
    await expect.poll(() => countArchiveResults(page)).toBe(6);

    const waterproofLink = page.getByRole("link", { name: escapedChipRegexp("waterproof") });

    await waterproofLink.click();
    await expect(page).toHaveURL(/sschp_tags=waterproof/);
    await expect(page.locator(".ssc-loop-filters .ssc-chip--active")).toContainText(["waterproof"]);
    await expect.poll(() => countArchiveResults(page)).toBe(3);

    await page.getByRole("link", { name: escapedChipRegexp("tent") }).click();
    await expect(page).toHaveURL(/sschp_tags=waterproof,tent/);
    await expect.poll(() => countArchiveResults(page)).toBe(2);

    await page.goBack();
    await expect(page).toHaveURL(/sschp_tags=waterproof/);
    await expect.poll(() => countArchiveResults(page)).toBe(3);

    await page.goBack();
    await expect(page).toHaveURL(/\/\?s=tent$/);
    await expect.poll(() => countArchiveResults(page)).toBe(6);

    const newTabLink = page.getByRole("link", { name: escapedChipRegexp("waterproof") });
    const waterproofHref = await newTabLink.getAttribute("href");
    const modifier = process.platform === "darwin" || browserName === "webkit" ? "Meta" : "Control";
    const newPagePromise = context.waitForEvent("page");
    await newTabLink.click({ modifiers: [modifier] });
    const newPage = await newPagePromise;
    await newPage.waitForLoadState("domcontentloaded");
    await expect(newPage).toHaveURL(new RegExp((waterproofHref || "").replace(/[.*+?^${}()|[\]\\]/g, "\\$&")));
    await expect.poll(() => countArchiveResults(newPage)).toBe(3);
    await newPage.close();

    await page.goForward();
    await expect(page).toHaveURL(/sschp_tags=waterproof/);
    await expect.poll(() => countArchiveResults(page)).toBe(3);
  });

  test("clear filters removes chip params and restores the unfiltered result set", async ({ page }) => {
    await page.goto("/?s=tent&sschp_tags=waterproof");

    await expect(page.locator(".ssc-loop-filters .ssc-chip--active")).toContainText(["waterproof"]);
    await expect.poll(() => countArchiveResults(page)).toBe(3);

    await page.getByRole("link", { name: "Clear filters" }).click();
    await expect(page).toHaveURL(/\/\?s=tent$/);
    await expect(page.locator(".ssc-loop-filters .ssc-chip--active")).toHaveCount(0);
    await expect.poll(() => countArchiveResults(page)).toBe(6);
  });

  test("direct filtered URLs render the expected active state and result counts", async ({ page }) => {
    await page.goto("/?s=tent&sschp_tags=waterproof,tent");

    await expect(page.locator(".ssc-loop-filters .ssc-chip--active")).toContainText([
      "waterproof",
      "tent"
    ]);
    await expect.poll(() => countArchiveResults(page)).toBe(2);
  });
});
