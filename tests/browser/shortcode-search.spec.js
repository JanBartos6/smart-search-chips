const { test, expect } = require("@playwright/test");
const { escapedChipRegexp, countAjaxResults, isLocalWordPressAvailable } = require("./helpers");

test.describe("Shortcode AJAX search", () => {
  test.beforeEach(async ({ request, baseURL }) => {
    test.skip(
      !(await isLocalWordPressAvailable(request, baseURL)),
      "Local WordPress test site is not running. Run `npm run local:wp:start` before local-site browser tests."
    );
  });

  test("stays on the page, searches live, and resets when cleared", async ({ page }) => {
    await page.goto("/search-gear/");

    const input = page.locator(".ssc-search__input");

    await expect(input).toBeVisible();

    await input.click();
    await input.pressSequentially("T", { delay: 80 });
    await page.waitForTimeout(400);
    await expect(page.locator(".ssc-results .ssc-result")).toHaveCount(0);

    await input.fill("");
    await input.click();
    await input.pressSequentially("tent", { delay: 80 });
    await expect(page.locator(".ssc-results .ssc-result").first()).toBeVisible();
    await expect(page).toHaveURL(/\/search-gear\/$/);
    await expect(page.locator(".ssc-chips .ssc-chip")).toHaveCount(8);
    await expect(page.locator(".ssc-results .ssc-result")).toHaveCount(5);

    await input.fill("");
    await page.waitForTimeout(500);
    await expect(page.locator(".ssc-results .ssc-result")).toHaveCount(0);
    await expect(page.locator(".ssc-chips .ssc-chip")).toHaveCount(0);
  });

  test("supports multi-chip AND filtering via AJAX", async ({ page }) => {
    await page.goto("/search-gear/");

    const input = page.locator(".ssc-search__input");
    await input.click();
    await input.pressSequentially("tent", { delay: 80 });
    await expect(page.locator(".ssc-results .ssc-result")).toHaveCount(5);

    await page.getByRole("button", { name: escapedChipRegexp("waterproof") }).click();
    await expect(page.locator(".ssc-results .ssc-result")).toHaveCount(3);
    await expect(page.locator(".ssc-chip--active")).toContainText(["waterproof"]);

    await page.getByRole("button", { name: escapedChipRegexp("tent") }).click();
    await expect(page.locator(".ssc-results .ssc-result")).toHaveCount(2);

    const titles = page.locator(".ssc-result__title");
    await expect(titles).toContainText([
      "Pinebreak Family Tent",
      "RidgeLite Two-Person Tent"
    ]);
  });

  test("submit with Enter keeps the shortcode flow in AJAX mode", async ({ page }) => {
    await page.goto("/search-gear/");

    const input = page.locator(".ssc-search__input");
    await input.fill("tent");
    await input.press("Enter");

    await expect(page).toHaveURL(/\/search-gear\/$/);
    await expect(page.locator(".ssc-results .ssc-result")).toHaveCount(5);
  });
});
