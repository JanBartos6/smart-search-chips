const { test, expect } = require("@playwright/test");
const path = require("path");

const settingsScriptPath = path.join(__dirname, "..", "..", "assets", "js", "settings.js");

async function installSettingsFixture(page, values = {}, body = "") {
  await page.setContent(`
    <main>
      <input type="text" name="sschp_search_input_selector" value="${values.inputSelector || ".theme-search input"}" />
      <input type="text" name="sschp_result_container_selector" value="${values.resultSelector || ".theme-search-results"}" />
      <input type="text" name="sschp_override_ajax" value="${values.ajaxAction || ""}" />
      <input type="text" name="sschp_ajax_keyword_field" value="${values.keywordField || "keyword"}" />
      <button type="button" id="sschp-run-compat-test">Run frontend compatibility test</button>
      <div id="sschp-compat-results" aria-live="polite"></div>
      ${body}
    </main>
  `);

  await page.evaluate((config) => {
    window.sschpSettingsDiagnostics = {
      diagnostics: {
        inputSelector: config.inputSelector || ".theme-search input",
        resultSelector: config.resultSelector || ".theme-search-results",
        ajaxAction: config.ajaxAction || "",
        keywordField: config.keywordField || "keyword",
        defaultInputValid: true,
        defaultResultValid: true,
        woocommerceActive: false,
        relevanssiActive: false,
        shortcodeAvailable: true
      },
      strings: {
        pass: "Pass",
        warning: "Warning",
        selectorInvalid: "This selector is invalid and should be corrected before testing theme integration.",
        selectorValid: "Selector syntax is valid.",
        defaultSelectorsValid: "Default selectors are valid.",
        actionConfigured: "Custom AJAX action is configured.",
        actionDefault: "No theme AJAX action is configured. The shortcode will use the built-in sschp_search action.",
        keywordSane: "Keyword field looks valid.",
        keywordInvalid: "Keyword field should be a simple request key such as keyword, s, query, or search.",
        inputMatches: "Matching search inputs found on this page:",
        resultMatches: "Matching result containers found on this page:",
        resultMissingRecommendation: "No result container was found on this admin page. If your theme search cannot be detected on the frontend, use the [sschp_search] shortcode or configure the result container selector."
      }
    };
  }, values);

  await page.addScriptTag({ path: settingsScriptPath });
}

test.describe("Settings compatibility diagnostics", () => {
  test("invalid selector shows warning", async ({ page }) => {
    await installSettingsFixture(page, {
      inputSelector: ".broken[",
      resultSelector: ".theme-search-results"
    });

    await page.getByRole("button", { name: /run frontend compatibility test/i }).click();

    await expect(page.locator("#sschp-compat-results")).toContainText("Search input selector");
    await expect(page.locator("#sschp-compat-results")).toContainText("Warning");
    await expect(page.locator("#sschp-compat-results")).toContainText("This selector is invalid");
  });

  test("valid selector shows pass", async ({ page }) => {
    await installSettingsFixture(
      page,
      {
        inputSelector: ".theme-search input",
        resultSelector: ".theme-search-results"
      },
      '<div class="theme-search"><input type="search" /></div><div class="theme-search-results"></div>'
    );

    await page.getByRole("button", { name: /run frontend compatibility test/i }).click();

    await expect(page.locator("#sschp-compat-results")).toContainText("Search input selector");
    await expect(page.locator("#sschp-compat-results")).toContainText("Selector syntax is valid.");
    await expect(page.locator("#sschp-compat-results")).toContainText("Matching search inputs found on this page: 1");
    await expect(page.locator("#sschp-compat-results")).toContainText("Matching result containers found on this page: 1");
  });

  test("no result container found recommends shortcode", async ({ page }) => {
    await installSettingsFixture(
      page,
      {
        inputSelector: ".theme-search input",
        resultSelector: ".theme-search-results"
      },
      '<div class="theme-search"><input type="search" /></div>'
    );

    await page.getByRole("button", { name: /run frontend compatibility test/i }).click();

    await expect(page.locator("#sschp-compat-results")).toContainText("Matching result containers found on this page: 0");
    await expect(page.locator("#sschp-compat-results")).toContainText("use the [sschp_search] shortcode");
  });
});
