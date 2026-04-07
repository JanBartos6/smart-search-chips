function escapedChipRegexp(label) {
  const escaped = label.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  return new RegExp("^" + escaped + "(?:\\s*\\(\\d+\\))?$", "i");
}

async function loginAsAdmin(page) {
  await page.goto("/wp-login.php");
  await page.fill("#user_login", "admin");
  await page.fill("#user_pass", "admin");
  await Promise.all([
    page.waitForNavigation({ waitUntil: "networkidle" }),
    page.click("#wp-submit"),
  ]);
}

async function gotoPluginSettings(page) {
  await page.goto("/wp-admin/options-general.php?page=smart-search-chips", {
    waitUntil: "domcontentloaded",
  });
}

async function gotoPluginAccount(page) {
  await page.goto("/wp-admin/options-general.php?page=smart-search-chips-account", {
    waitUntil: "domcontentloaded",
  });
}

async function reactivatePlugin(page) {
  await page.goto("/wp-admin/plugins.php", { waitUntil: "networkidle" });

  const deactivateLink = page.locator("#deactivate-smart-search-chips");
  if (await deactivateLink.count()) {
    const deactivateHref = await deactivateLink.getAttribute("href");
    await page.goto("/wp-admin/" + deactivateHref, { waitUntil: "networkidle" });
  }

  const activateHref = await page.locator("#activate-smart-search-chips").getAttribute("href");
  await page.goto("/wp-admin/" + activateHref, { waitUntil: "networkidle" });
}

async function activateProLicense(page, licenseKey) {
  await reactivatePlugin(page);
  await page.click("#fs_license_key");
  await page.type("#fs_license_key", licenseKey, { delay: 20 });
  await page.getByText("No - do NOT send me security").click();
  await Promise.all([
    page.waitForLoadState("networkidle"),
    page.getByRole("button", { name: "Activate License" }).click(),
  ]);

  const consentButton = page.getByRole("button", { name: /Agree & Activate License/i });
  if (await consentButton.count()) {
    await Promise.all([
      page.waitForLoadState("networkidle"),
      consentButton.click(),
    ]);
  }

  await page.waitForTimeout(6000);
  await gotoPluginSettings(page);
}

async function deactivateProLicense(page) {
  page.on("dialog", async (dialog) => {
    await dialog.accept();
  });

  await gotoPluginAccount(page);
  await page.getByText("Deactivate License").click({ noWaitAfter: true });
  await page.waitForTimeout(12000);
  await gotoPluginSettings(page);
}

async function countAjaxResults(page) {
  return page.locator(".ssc-results .ssc-result").count();
}

async function countArchiveResults(page) {
  return page.locator("main .wp-block-query.alignfull .wp-block-post-template > li.wp-block-post").count();
}

module.exports = {
  escapedChipRegexp,
  loginAsAdmin,
  gotoPluginSettings,
  gotoPluginAccount,
  activateProLicense,
  deactivateProLicense,
  countAjaxResults,
  countArchiveResults
};
