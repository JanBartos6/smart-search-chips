const { test, expect } = require("@playwright/test");
const { escapedChipRegexp, countAjaxResults } = require("./helpers");

test.describe("Shortcode AJAX search", () => {
  test("stays on the page, searches live, and resets when cleared", async ({ page }) => {
    await page.goto("/search/");

    const input = page.locator(".ssc-search__input");

    await expect(input).toBeVisible();

    await input.click();
    await input.pressSequentially("T", { delay: 80 });
    await page.waitForTimeout(400);
    await expect(page.locator(".ssc-results .ssc-result")).toHaveCount(0);

    await input.fill("");
    await input.click();
    await input.pressSequentially("Trapped", { delay: 80 });
    await expect(page.locator(".ssc-results .ssc-result").first()).toBeVisible();
    await expect(page).toHaveURL(/\/search\/$/);
    await expect(page.locator(".ssc-chips .ssc-chip")).toHaveCount(5);
    await expect(page.locator(".ssc-results .ssc-result")).toHaveCount(8);

    await input.fill("");
    await page.waitForTimeout(500);
    await expect(page.locator(".ssc-results .ssc-result")).toHaveCount(0);
    await expect(page.locator(".ssc-chips .ssc-chip")).toHaveCount(0);
  });

  test("supports multi-chip AND filtering via AJAX", async ({ page }) => {
    await page.goto("/search/");

    const input = page.locator(".ssc-search__input");
    await input.click();
    await input.pressSequentially("Trapped", { delay: 80 });
    await expect(page.locator(".ssc-results .ssc-result")).toHaveCount(8);

    await page.getByRole("button", { name: escapedChipRegexp("spring handle") }).click();
    await expect(page.locator(".ssc-results .ssc-result")).toHaveCount(4);
    await expect(page.locator(".ssc-chip--active")).toContainText(["spring handle"]);

    await page.getByRole("button", { name: escapedChipRegexp("trapped key interlock") }).click();
    await expect(page.locator(".ssc-results .ssc-result")).toHaveCount(2);

    const titles = page.locator(".ssc-result__title");
    await expect(titles).toContainText([
      "Trapped Key Guard Door Interlock",
      "Trapped Key Interlock with Lockout"
    ]);
  });

  test("submit with Enter keeps the shortcode flow in AJAX mode", async ({ page }) => {
    await page.goto("/search/");

    const input = page.locator(".ssc-search__input");
    await input.fill("Trapped");
    await input.press("Enter");

    await expect(page).toHaveURL(/\/search\/$/);
    await expect(page.locator(".ssc-results .ssc-result")).toHaveCount(8);
  });
});
