const { test, expect } = require("@playwright/test");
const {
  escapedChipRegexp,
  loginAsAdmin,
  gotoAnalyticsPage,
  ensureAnalyticsEnabled,
  isLocalWordPressAvailable,
} = require("./helpers");

test.describe("Analytics dashboard", () => {
  test.beforeEach(async ({ request, baseURL }) => {
    test.skip(
      !(await isLocalWordPressAvailable(request, baseURL)),
      "Local WordPress test site is not running. Run `npm run local:wp:start` before local-site browser tests."
    );
  });

  test("records search, click-through, and zero-result insights", async ({ page }) => {
    await loginAsAdmin(page);

    const analyticsAvailable = await ensureAnalyticsEnabled(page);
    test.skip(!analyticsAvailable, "Analytics could not be enabled on the test site.");

    await page.goto("/search/");

    const input = page.locator(".ssc-search__input");
    await input.click();
    await input.pressSequentially("Trapped", { delay: 60 });
    await expect(page.locator(".ssc-results .ssc-result")).toHaveCount(8);

    await page.getByRole("button", { name: escapedChipRegexp("spring handle") }).click();
    await expect(page.locator(".ssc-results .ssc-result")).toHaveCount(4);

    await input.fill("");
    await input.click();
    await input.pressSequentially("NoResultsQxz", { delay: 60 });
    await expect(page.locator(".ssc-empty")).toContainText("No results found.");

    await page.waitForTimeout(1500);
    await gotoAnalyticsPage(page);

    await expect(page.getByText("Search To Click Rate")).toBeVisible();
    await expect(page.getByRole("heading", { name: "Top Chips By Click-Through Rate" })).toBeVisible();
    await expect(page.locator(".ssc-analytics-panel").filter({ has: page.getByRole("heading", { name: "Top Chips By Click-Through Rate" }) })).toContainText("spring handle");
    await expect(page.locator(".ssc-analytics-panel").filter({ has: page.getByRole("heading", { name: "Top Zero-Result Queries Over Time" }) })).toContainText("NoResultsQxz");
    await expect(page.locator(".ssc-analytics-panel").filter({ has: page.getByRole("heading", { name: "Top Search Queries" }) })).toContainText("Click Rate");
  });
});
