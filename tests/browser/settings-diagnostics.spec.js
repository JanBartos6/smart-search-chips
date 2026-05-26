const { test, expect } = require("@playwright/test");
const path = require("path");

const settingsScriptPath = path.join(__dirname, "..", "..", "assets", "js", "settings.js");
const settingsStylePath = path.join(__dirname, "..", "..", "assets", "css", "settings.css");

async function installSettingsFixture(page, values = {}, body = "") {
  await page.setContent(`
    <main id="ssc-settings-layout">
      <div id="ssc-settings-left">
      <input type="text" name="sschp_search_input_selector" value="${values.inputSelector || ".theme-search input"}" />
      <input type="text" name="sschp_result_container_selector" value="${values.resultSelector || ".theme-search-results"}" />
      <input type="text" name="sschp_override_ajax" value="${values.ajaxAction || ""}" />
      <input type="text" name="sschp_ajax_keyword_field" value="${values.keywordField || "keyword"}" />
      <section class="ssc-settings-card ssc-settings-card--setup">
        <h2>First-run Setup Wizard</h2>
        <p>For the most reliable setup, add the Smart Search Chips search block or shortcode to your search page.</p>
        <p>Existing theme search enhancement is automatic when compatible and configurable when needed.</p>
        <div class="ssc-setup-grid">
          <div class="ssc-setup-step">
            <strong>1. Catalog Source</strong>
            <p>Products selected.</p>
            <span class="ssc-badge ssc-badge--neutral">Products</span>
          </div>
          <div class="ssc-setup-step">
            <strong>2. Guaranteed Search Mode</strong>
            <p>Guaranteed Search Mode uses the Smart Search Chips endpoint and does not depend on theme AJAX.</p>
            <div class="ssc-copy-row">
              <input id="sschp-guaranteed-shortcode" type="text" class="regular-text code" value="[sschp_search]" readonly />
              <button type="button" class="button button-secondary" data-sschp-copy="[sschp_search]">Copy shortcode</button>
            </div>
            <div class="ssc-setup-paths">
              <p><strong>Block</strong><span>Find and add the Smart Search Chips Search block from the block inserter.</span></p>
              <p><strong>Shortcode fallback</strong><span>Search for WordPress's built-in Shortcode block and paste [sschp_search]. If your editor or page builder hides that block, paste the shortcode into its shortcode or widget module instead.</span></p>
            </div>
          </div>
          <div class="ssc-setup-step">
            <strong>3. Theme Search Enhancement</strong>
            <span class="ssc-badge ssc-badge--neutral">Passive by default</span>
            <p>No existing theme AJAX action is configured.</p>
          </div>
        </div>
      </section>
      <button type="button" id="sschp-run-compat-test">Run frontend compatibility test</button>
      <div id="sschp-compat-results" aria-live="polite"></div>
      ${body}
      </div>
      <aside id="ssc-settings-right"><div id="ssc-preview-panel">Preview</div></aside>
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
        resultMissingRecommendation: "No result container was found on this admin page. If your theme search cannot be detected on the frontend, use the [sschp_search] shortcode or configure the result container selector.",
        copied: "Copied.",
        copyFailed: "Copy failed. Copy the snippet manually."
      }
    };
  }, values);

  await page.addStyleTag({ path: settingsStylePath });
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

  test("setup wizard provides guaranteed shortcode and block recommendation", async ({ page }) => {
    await installSettingsFixture(page);

    await expect(page.getByRole("heading", { name: /first-run setup wizard/i })).toBeVisible();
    await expect(page.locator(".ssc-settings-card--setup")).toContainText("For the most reliable setup");
    await expect(page.locator(".ssc-settings-card--setup")).toContainText("Existing theme search enhancement is automatic");
    await expect(page.locator("#sschp-guaranteed-shortcode")).toHaveValue("[sschp_search]");
    await expect(page.locator(".ssc-settings-card--setup")).toContainText("block inserter");
    await expect(page.locator(".ssc-settings-card--setup")).toContainText("Shortcode fallback");
    await expect(page.locator(".ssc-settings-card--setup")).toContainText("Shortcode block");
    await expect(page.locator(".ssc-settings-card--setup")).toContainText("does not depend on theme AJAX");
  });

  test("setup wizard does not overflow at medium admin width", async ({ page }) => {
    await page.setViewportSize({ width: 1120, height: 800 });
    await installSettingsFixture(page);

    const overflow = await page.locator(".ssc-settings-card--setup").evaluate((card) => {
      const cardRect = card.getBoundingClientRect();

      return Array.from(card.querySelectorAll("*"))
        .filter((element) => {
          const rect = element.getBoundingClientRect();
          const style = window.getComputedStyle(element);

          return style.display !== "none" && (rect.left < cardRect.left - 1 || rect.right > cardRect.right + 1);
        })
        .map((element) => element.className || element.tagName.toLowerCase());
    });

    await expect(page.locator("#sschp-guaranteed-shortcode")).toBeVisible();
    expect(overflow).toEqual([]);
  });
});
