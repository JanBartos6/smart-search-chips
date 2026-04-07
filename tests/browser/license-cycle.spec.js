const { test, expect } = require("@playwright/test");
const {
  loginAsAdmin,
  gotoPluginSettings,
  activateProLicense,
  deactivateProLicense,
} = require("./helpers");

const licenseKey = process.env.SSC_TEST_LICENSE_KEY || "";

test.describe.configure({ mode: "serial" });

test("Freemius deactivation yields a clear outcome", async ({ page }) => {
  test.skip(!licenseKey, "Set SSC_TEST_LICENSE_KEY to run the Freemius license-cycle test.");
  test.setTimeout(180000);

  await loginAsAdmin(page);
  await activateProLicense(page, licenseKey);

  const result = await deactivateProLicense(page);

  expect(["deactivated", "timeout"]).toContain(result.outcome);

  if (result.outcome === "timeout") {
    expect(result.settingsState).toBe("pro");
    expect(result.accountText).toMatch(/CurlException|cURL error 28|Connection timed out|timed out/i);
  } else {
    expect(result.settingsState).toBe("free");
    await expect(page.getByText("This site is running the free tier.")).toBeVisible();
    await expect(page.getByText("Upgrade to Pro")).toBeVisible();
  }
});

test("Freemius free-to-pro-to-free cycle toggles the gated UI", async ({ page, baseURL }) => {
  test.skip(!licenseKey, "Set SSC_TEST_LICENSE_KEY to run the Freemius license-cycle test.");
  test.setTimeout(180000);

  await loginAsAdmin(page);

  const initialResult = await deactivateProLicense(page);
  expect(initialResult.outcome, `Initial Freemius deactivation did not return the site to free tier. Outcome: ${initialResult.outcome}. Account text: ${initialResult.accountText}`).toBe("deactivated");
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

  const finalResult = await deactivateProLicense(page);
  expect(finalResult.outcome, `Final Freemius deactivation did not return the site to free tier. Outcome: ${finalResult.outcome}. Account text: ${finalResult.accountText}`).toBe("deactivated");
  await expect(page.getByText("This site is running the free tier.")).toBeVisible();
  await expect(page.getByText("Upgrade to Pro")).toBeVisible();
  await expect(page.getByText("Border Radius")).toHaveCount(0);
  await expect(page.getByRole("checkbox", { name: /Track anonymous search and chip-click events/i })).toHaveCount(0);
  await expect(page.getByText("Allow REST API write-back in generated prompt")).toHaveCount(0);

  await page.goto(`${baseURL}/wp-admin/plugins.php`, { waitUntil: "networkidle" });
});
