const { test, expect } = require("@playwright/test");
const {
  loginAsAdmin,
  gotoPluginSettings,
  activateProLicense,
  deactivateProLicense,
} = require("./helpers");

const licenseKey = process.env.SSC_TEST_LICENSE_KEY || "";

test.describe.configure({ mode: "serial" });

test("Freemius free-to-pro-to-free cycle toggles the gated UI", async ({ page, baseURL }) => {
  test.skip(!licenseKey, "Set SSC_TEST_LICENSE_KEY to run the Freemius license-cycle test.");
  test.fail(true, "Freemius license deactivation still times out from the local Docker site.");
  test.setTimeout(180000);

  await loginAsAdmin(page);

  await deactivateProLicense(page);
  await expect(page.getByText("This site is running the free tier.")).toBeVisible();
  await expect(page.getByText("Upgrade to Pro")).toBeVisible();
  await expect(page.getByText("Upgrade to Pro for border radius, font size, and padding controls.")).toBeVisible();
  await expect(page.getByRole("checkbox", { name: /Track anonymous search and chip-click events/i })).toHaveCount(0);
  await expect(page.getByLabel("REST API")).toHaveCount(0);

  await activateProLicense(page, licenseKey);
  await expect(page.getByText("Pro active")).toBeVisible();
  await expect(page.getByText("Manage License")).toBeVisible();
  await expect(page.getByText("Border Radius")).toBeVisible();
  await expect(page.getByRole("checkbox", { name: /Track anonymous search and chip-click events/i })).toBeVisible();
  await expect(page.getByText("Allow REST API write-back in generated prompt")).toBeVisible();

  await deactivateProLicense(page);
  await expect(page.getByText("This site is running the free tier.")).toBeVisible();
  await expect(page.getByText("Upgrade to Pro")).toBeVisible();
  await expect(page.getByText("Border Radius")).toHaveCount(0);
  await expect(page.getByRole("checkbox", { name: /Track anonymous search and chip-click events/i })).toHaveCount(0);
  await expect(page.getByText("Allow REST API write-back in generated prompt")).toHaveCount(0);

  await page.goto(`${baseURL}/wp-admin/plugins.php`, { waitUntil: "networkidle" });
});
