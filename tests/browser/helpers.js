function escapedChipRegexp(label) {
  const escaped = label.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  return new RegExp("^(?:(?:Filter by|Remove filter)\\s+)?" + escaped + "(?:(?:,\\s*\\d+\\s+results?)|(?:\\s*\\(\\d+\\)))?$", "i");
}

async function loginAsAdmin(page) {
  await page.goto("/wp-login.php");
  await page.fill("#user_login", "admin");
  await page.fill("#user_pass", process.env.SSCHP_TEST_ADMIN_PASS || "password");
  await Promise.all([
    page.waitForNavigation({ waitUntil: "networkidle" }),
    page.click("#wp-submit"),
  ]);
}

async function gotoPluginSettings(page) {
  await page.goto("/wp-admin/admin.php?page=smart-search-chips", {
    waitUntil: "domcontentloaded",
  });
}

async function gotoPluginAccount(page) {
  await page.goto("/wp-admin/admin.php?page=smart-search-chips-account", {
    waitUntil: "domcontentloaded",
  });
}

async function gotoAnalyticsPage(page) {
  await page.goto("/wp-admin/admin.php?page=smart-search-chips-analytics", {
    waitUntil: "domcontentloaded",
  });
}

async function readBodyText(page) {
  try {
    return await page.locator("body").innerText();
  } catch (error) {
    return "";
  }
}

async function isLocalWordPressAvailable(request, baseURL) {
  const rootUrl = baseURL || "http://localhost:8080";

  try {
    const response = await request.get(new URL("/wp-login.php", rootUrl).toString(), {
      timeout: 2000,
    });

    return response.status() < 500;
  } catch (error) {
    return false;
  }
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

  const settingsText = await readBodyText(page);
  if (settingsText.includes("Pro active")) {
    return;
  }

  const licenseField = page.locator("#fs_license_key");
  if (!await licenseField.count()) {
    await gotoPluginSettings(page);

    const postSettingsText = await readBodyText(page);
    if (postSettingsText.includes("Pro active")) {
      return;
    }
  }

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
  page.once("dialog", async (dialog) => {
    await dialog.accept();
  });

  await gotoPluginAccount(page);
  const deactivateButton = page.getByText("Deactivate License");

  if (!await deactivateButton.count()) {
    await gotoPluginSettings(page);
    return {
      outcome: "deactivated",
      settingsState: "free",
      accountText: "",
    };
  }

  await deactivateButton.click({ noWaitAfter: true });

  const timeoutPattern = /(CurlException|cURL error 28|Connection timed out|timed out)/i;
  let accountText = "";
  const deadline = Date.now() + 25000;

  while (Date.now() < deadline) {
    accountText = await readBodyText(page);

    if (timeoutPattern.test(accountText)) {
      break;
    }

    await page.waitForTimeout(1000);
  }

  await gotoPluginSettings(page);

  const settingsText = await readBodyText(page);
  let settingsState = "unknown";

  if (settingsText.includes("This site is running the free tier.")) {
    settingsState = "free";
  } else if (settingsText.includes("Pro active")) {
    settingsState = "pro";
  }

  let outcome = "unknown";

  if (settingsState === "free") {
    outcome = "deactivated";
  } else if (timeoutPattern.test(accountText)) {
    outcome = "timeout";
  }

  return {
    outcome,
    settingsState,
    accountText: accountText.replace(/\s+/g, " ").trim(),
  };
}

async function countAjaxResults(page) {
  return page.locator(".ssc-results .ssc-result").count();
}

async function countArchiveResults(page) {
  const blockResults = await page.locator("main .wp-block-query.alignfull .wp-block-post-template > li.wp-block-post").count();
  if (blockResults > 0) {
    return blockResults;
  }

  const wooResults = await page.locator("main ul.products > li.product").count();
  if (wooResults > 0) {
    return wooResults;
  }

  return page.locator("main .tf-grid .tf-card.tf-product").count();
}

async function ensureAnalyticsEnabled(page) {
  await gotoPluginSettings(page);

  const checkbox = page.getByRole("checkbox", { name: /Track anonymous search/i });
  if (!await checkbox.count()) {
    return false;
  }

  if (!await checkbox.isChecked()) {
    await checkbox.check();
    await Promise.all([
      page.waitForLoadState("networkidle"),
      page.getByRole("button", { name: "Save Changes" }).click(),
    ]);
  }

  return true;
}

module.exports = {
  escapedChipRegexp,
  loginAsAdmin,
  gotoPluginSettings,
  gotoPluginAccount,
  gotoAnalyticsPage,
  isLocalWordPressAvailable,
  activateProLicense,
  deactivateProLicense,
  countAjaxResults,
  countArchiveResults,
  ensureAnalyticsEnabled
};
