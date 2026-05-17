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
    await page.goto("/?s=Trapped");

    await expect(page.locator(".ssc-loop-filters")).toBeVisible();
    await expect(page.locator(".ssc-loop-filters__title")).toHaveText("Refine This Search");
    await expect(page.locator(".ssc-loop-filters .ssc-chip")).toHaveCount(5);
    await expect(page.locator(".ssc-loop-filters .ssc-chip--active")).toHaveCount(0);
    await expect.poll(() => countArchiveResults(page)).toBe(8);

    const springHandleLink = page.getByRole("link", { name: escapedChipRegexp("spring handle") });

    await springHandleLink.click();
    await expect(page).toHaveURL(/sschp_tags=spring%20handle/);
    await expect(page.locator(".ssc-loop-filters .ssc-chip--active")).toContainText(["spring handle"]);
    await expect.poll(() => countArchiveResults(page)).toBe(4);

    await page.getByRole("link", { name: escapedChipRegexp("trapped key interlock") }).click();
    await expect(page).toHaveURL(/sschp_tags=spring%20handle,trapped%20key%20interlock/);
    await expect.poll(() => countArchiveResults(page)).toBe(2);

    await page.goBack();
    await expect(page).toHaveURL(/sschp_tags=spring%20handle/);
    await expect.poll(() => countArchiveResults(page)).toBe(4);

    await page.goBack();
    await expect(page).toHaveURL(/\/\?s=Trapped$/);
    await expect.poll(() => countArchiveResults(page)).toBe(8);

    const newTabLink = page.getByRole("link", { name: escapedChipRegexp("spring handle") });
    const springHandleHref = await newTabLink.getAttribute("href");
    const modifier = browserName === "webkit" ? "Meta" : "Control";
    const newPagePromise = context.waitForEvent("page");
    await newTabLink.click({ modifiers: [modifier] });
    const newPage = await newPagePromise;
    await newPage.waitForLoadState("domcontentloaded");
    await expect(newPage).toHaveURL(new RegExp((springHandleHref || "").replace(/[.*+?^${}()|[\]\\]/g, "\\$&")));
    await expect.poll(() => countArchiveResults(newPage)).toBe(4);
    await newPage.close();

    await page.goForward();
    await expect(page).toHaveURL(/sschp_tags=spring%20handle/);
    await expect.poll(() => countArchiveResults(page)).toBe(4);
  });

  test("clear filters removes chip params and restores the unfiltered result set", async ({ page }) => {
    await page.goto("/?s=Trapped&sschp_tags=spring%20handle");

    await expect(page.locator(".ssc-loop-filters .ssc-chip--active")).toContainText(["spring handle"]);
    await expect.poll(() => countArchiveResults(page)).toBe(4);

    await page.getByRole("link", { name: "Clear filters" }).click();
    await expect(page).toHaveURL(/\/\?s=Trapped$/);
    await expect(page.locator(".ssc-loop-filters .ssc-chip--active")).toHaveCount(0);
    await expect.poll(() => countArchiveResults(page)).toBe(8);
  });

  test("direct filtered URLs render the expected active state and result counts", async ({ page }) => {
    await page.goto("/?s=Trapped&sschp_tags=spring%20handle,trapped%20key%20interlock");

    await expect(page.locator(".ssc-loop-filters .ssc-chip--active")).toContainText([
      "spring handle",
      "trapped key interlock"
    ]);
    await expect.poll(() => countArchiveResults(page)).toBe(2);
  });
});
