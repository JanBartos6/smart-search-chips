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
  gotoAnalyticsPage,
  isLocalWordPressAvailable,
  countAjaxResults,
  countArchiveResults,
  ensureAnalyticsEnabled
};
