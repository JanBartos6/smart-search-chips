const { test, expect } = require("@playwright/test");
const path = require("path");

const chipsScriptPath = path.join(__dirname, "..", "..", "assets", "js", "chips.js");
const legacyEscapedSearchShortcode = "[ssc" + "_search";

const inputSelector = [
  ".custom-search-input",
  ".ssc-search__input",
  "input[type='search']",
  "input[name='keyword']",
  "input[name='s']",
  "input[name='query']",
  "input[name='search']",
].join(", ");

const resultSelector = [
  ".custom-search-results",
  ".js-ssc-results",
  ".js-search__results",
  ".ssc-search__results",
].join(", ");

async function installCompatFixture(page, options = {}) {
  await page.setContent(`
    <main>
      <section class="ssc-search" data-testid="shortcode-search">
        <form class="ssc-search__form js-ssc-search" role="search">
          <input type="search" class="ssc-search__input" name="keyword" />
          <button type="submit">Search</button>
        </form>
        <div class="ssc-search__results js-ssc-results" aria-live="polite" aria-busy="false"></div>
      </section>

      <section data-testid="escaped-shortcode-search">
        ${legacyEscapedSearchShortcode} placeholder="Try searching: tea, stoneware, serving..." class="demo-search"]
      </section>

      <section class="js-search" data-testid="header-xhr-formdata" data-transport="xhr-formdata">
        <form><input type="search" name="keyword" /></form>
        <button type="button">Run header search</button>
        <div class="js-search__results"></div>
      </section>

      <section class="js-search" data-testid="fetch-formdata" data-transport="fetch-formdata">
        <form><input type="search" name="keyword" /></form>
        <button type="button">Run fetch FormData</button>
        <div class="js-search__results"></div>
      </section>

      <section class="js-search" data-testid="fetch-params" data-transport="fetch-params">
        <form><input type="search" name="keyword" /></form>
        <button type="button">Run fetch params</button>
        <div class="js-search__results"></div>
      </section>

      <section class="js-search" data-testid="fetch-serialized" data-transport="fetch-serialized">
        <form><input type="search" name="keyword" /></form>
        <button type="button">Run serialized search</button>
        <div class="js-search__results"></div>
      </section>

      <section data-testid="normal-get-search">
        <form method="get" action="/">
          <input type="search" name="s" />
          <button type="submit">Normal GET search</button>
        </form>
      </section>

      <section class="js-search" data-testid="query-field" data-transport="fetch-query">
        <form><input type="search" name="query" /></form>
        <button type="button">Run query field search</button>
        <div class="js-search__results"></div>
      </section>

      <section class="js-search" data-testid="search-field" data-transport="fetch-search">
        <form><input type="search" name="search" /></form>
        <button type="button">Run search field search</button>
        <div class="js-search__results"></div>
      </section>

      <section class="custom-search-root" data-testid="custom-selector-search" data-transport="fetch-custom">
        <form><input type="text" class="custom-search-input" name="keyword" /></form>
        <button type="button">Run custom selector search</button>
        <div class="custom-search-results"></div>
      </section>

      <section class="js-search" data-testid="desktop-search" data-transport="fetch-formdata">
        <form><input type="search" name="keyword" /></form>
        <button type="button">Run desktop search</button>
        <div class="js-search__results"></div>
      </section>

      <section class="js-search" data-testid="mobile-search" data-transport="fetch-formdata">
        <form><input type="search" name="keyword" /></form>
        <button type="button">Run mobile search</button>
        <div class="js-search__results"></div>
      </section>

      <section class="js-search" data-testid="raw-theme-search" data-transport="raw-xhr-formdata">
        <form><input type="search" name="keyword" /></form>
        <button type="button">Run raw theme search</button>
        <div class="js-search__results"></div>
      </section>
    </main>
  `);

  await page.evaluate((config) => {
    window.__compatRequests = [];
    window.__compatThemeRuns = [];
    window.sschpConfig = {
      action: config.searchAction || "sschp_search",
      themeAction: Object.prototype.hasOwnProperty.call(config, "themeAction") ? config.themeAction : (config.action || "search_processing"),
      ajaxUrl: "/wp-admin/admin-ajax.php",
      keywordField: config.keywordField || "keyword",
      inputSelector: config.inputSelector || config.defaultInputSelector,
      resultSelector: config.resultSelector || config.defaultResultSelector,
      minChars: 2,
      debounceMs: Object.prototype.hasOwnProperty.call(config, "debounceMs") ? config.debounceMs : 300,
      postType: "product",
      nonce: "compat-nonce",
      lang: "",
      isAdmin: !!config.isAdmin,
      debug: !!config.debug,
      analytics: { enabled: false, nonce: "" },
    };
    window.__compatResponseDelays = config.responseDelays || {};

    function entriesFromParams(params) {
      const entries = [];
      params.forEach((value, key) => entries.push([key, String(value)]));
      return entries;
    }

    function entriesFromBody(body) {
      if (!body) {
        return [];
      }

      if (body instanceof FormData || body instanceof URLSearchParams) {
        return Array.from(body.entries()).map(([key, value]) => [key, String(value)]);
      }

      if (typeof body === "string") {
        return entriesFromParams(new URLSearchParams(body));
      }

      return [];
    }

    function entriesFromUrl(url) {
      try {
        return entriesFromParams(new URL(String(url || "/"), window.location.href).searchParams);
      } catch (error) {
        return [];
      }
    }

    function firstEntry(entries, keys) {
      for (const key of keys) {
        const match = entries.find((entry) => entry[0] === key);
        if (match) {
          return match[1];
        }
      }

      return "";
    }

    function recordRequest(transport, method, url, body) {
      const entries = entriesFromBody(body).concat(entriesFromUrl(url));
      const request = {
        transport,
        method: String(method || "GET").toUpperCase(),
        url: String(url || ""),
        action: firstEntry(entries, ["action"]),
        keyword: firstEntry(entries, ["keyword", "s", "query", "search"]),
        managed: firstEntry(entries, ["sschp_managed"]),
        nonce: firstEntry(entries, ["sschp_nonce"]),
        activeTags: entries
          .filter((entry) => entry[0] === "active_tags[]" || entry[0] === "active_tags")
          .map((entry) => entry[1]),
      };

      window.__compatRequests.push(request);
      return request;
    }

    function responseFor(request) {
      if (request.action === "raw_theme_search" && !request.managed && !request.nonce) {
        return '<div class="theme-original-result">Original theme search response</div>';
      }

      if (request.action === "search_processing" && !request.managed && !request.nonce) {
        return '<div class="theme-original-result">Original theme search response</div>';
      }

      const steelActive = request.activeTags.some((tag) => tag.toLowerCase() === "steel");
      const activeClass = steelActive ? " ssc-chip--active" : "";
      const ariaPressed = steelActive ? "true" : "false";
      const ariaLabel = steelActive ? "Remove filter Steel, 3 results" : "Filter by Steel, 3 results";

      return `
        <div class="ssc-chips">
          <button type="button" class="ssc-chip${activeClass}" data-tag="Steel" aria-pressed="${ariaPressed}" aria-label="${ariaLabel}">Steel (3)</button>
          <button type="button" class="ssc-chip" data-tag="Outdoor" aria-pressed="false" aria-label="Filter by Outdoor, 2 results">Outdoor (2)</button>
        </div>
        <ul class="ssc-results">
          <li class="ssc-result"><a href="#"><span class="ssc-result__title">${request.keyword || "Theme"} result</span></a></li>
        </ul>
      `;
    }

    class FakeXMLHttpRequest {
      constructor() {
        this.listeners = {};
        this.readyState = 0;
        this.status = 0;
        this.responseText = "";
        this.responseHeaders = { "X-SSC-Results-Count": "1" };
      }

      open(method, url) {
        this.method = method;
        this.url = url;
      }

      setRequestHeader() {}

      getResponseHeader(name) {
        return this.responseHeaders[name] || null;
      }

      addEventListener(type, callback, listenerOptions) {
        this.listeners[type] = this.listeners[type] || [];
        this.listeners[type].push({ callback, once: !!(listenerOptions && listenerOptions.once) });
      }

      abort() {
        this.aborted = true;
        this.status = 0;
        this.readyState = 4;

        if (typeof this.onabort === "function") {
          this.onabort();
        }

        this.dispatch("abort");
        this.dispatch("loadend");
      }

      dispatch(type) {
        const listeners = this.listeners[type] || [];
        this.listeners[type] = listeners.filter((listener) => {
          listener.callback.call(this);
          return !listener.once;
        });
      }

      send(body) {
        const request = recordRequest("xhr", this.method || "GET", this.url || "", body);
        const delay = Number(window.__compatResponseDelays[request.keyword] || 0);
        const finish = () => {
          if (this.aborted) {
            return;
          }

          this.status = 200;
          this.readyState = 4;
          this.responseText = responseFor(request);

          if (typeof this.onreadystatechange === "function") {
            this.onreadystatechange();
          }

          if (typeof this.onload === "function") {
            this.onload();
          }

          this.dispatch("loadend");
        };

        if (delay > 0) {
          window.setTimeout(finish, delay);
          return;
        }

        finish();
      }
    }

    window.XMLHttpRequest = FakeXMLHttpRequest;
    window.fetch = (input, init = {}) => {
      const url = typeof input === "string" || input instanceof URL ? String(input) : input.url;
      const method = init.method || (input && input.method) || "GET";
      const body = Object.prototype.hasOwnProperty.call(init, "body") ? init.body : null;
      const request = recordRequest("fetch", method, url, body);

      return Promise.resolve(new Response(responseFor(request), {
        status: 200,
        headers: { "X-SSC-Results-Count": "1" },
      }));
    };
  }, {
    ...options,
    defaultInputSelector: inputSelector,
    defaultResultSelector: resultSelector,
  });

  await page.addScriptTag({ path: chipsScriptPath });

  await page.evaluate(() => {
    function formValue(root, name) {
      const input = root.querySelector(`[name="${name}"]`) || root.querySelector("input");
      return input ? input.value : "";
    }

    function render(root, html) {
      const container = root.querySelector(".js-search__results, .custom-search-results");
      if (container) {
        container.innerHTML = html;
        container.classList.add("theme-search-open");
      }
    }

    function runXhr(root, body) {
      return new Promise((resolve) => {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "/wp-admin/admin-ajax.php", true);
        xhr.onload = () => {
          render(root, xhr.responseText);
          resolve();
        };
        xhr.send(body);
      });
    }

    async function runFetch(root, body, method = "POST", url = "/wp-admin/admin-ajax.php") {
      const response = await fetch(url, { method, body });
      render(root, await response.text());
    }

    window.runCompatSearch = async (testId, keyword = "Steel") => {
      const root = document.querySelector(`[data-testid="${testId}"]`);
      const input = root.querySelector("input");
      input.value = keyword;
      input.focus();

      const transport = root.getAttribute("data-transport");
      window.__compatThemeRuns.push({ testId, transport });

      if (transport === "xhr-formdata") {
        const body = new FormData();
        body.append("action", "search_processing");
        body.append("keyword", formValue(root, "keyword"));
        await runXhr(root, body);
        return;
      }

      if (transport === "fetch-formdata") {
        const body = new FormData();
        body.append("action", "search_processing");
        body.append("keyword", formValue(root, "keyword"));
        await runFetch(root, body);
        return;
      }

      if (transport === "fetch-params") {
        const body = new URLSearchParams();
        body.append("action", "search_processing");
        body.append("keyword", formValue(root, "keyword"));
        await runFetch(root, body);
        return;
      }

      if (transport === "fetch-serialized") {
        await runFetch(root, `action=search_processing&keyword=${encodeURIComponent(formValue(root, "keyword"))}`);
        return;
      }

      if (transport === "fetch-query") {
        const body = new URLSearchParams();
        body.append("action", "search_processing");
        body.append("query", formValue(root, "query"));
        await runFetch(root, body);
        return;
      }

      if (transport === "fetch-search") {
        const body = new FormData();
        body.append("action", "search_processing");
        body.append("search", formValue(root, "search"));
        await runFetch(root, body);
        return;
      }

      if (transport === "fetch-custom") {
        const body = new FormData();
        body.append("action", "search_processing");
        body.append("keyword", formValue(root, "keyword"));
        await runFetch(root, body);
        return;
      }

      if (transport === "raw-xhr-formdata") {
        const body = new FormData();
        body.append("action", "raw_theme_search");
        body.append("keyword", formValue(root, "keyword"));
        await runXhr(root, body);
      }
    };
  });
}

async function runSearch(page, testId, keyword = "Steel") {
  await page.evaluate(([id, value]) => window.runCompatSearch(id, value), [testId, keyword]);
}

async function requests(page) {
  return page.evaluate(() => window.__compatRequests);
}

function last(items) {
  return items[items.length - 1];
}

test.describe("Theme compatibility matrix", () => {
  test("moves custom-theme archive chips directly above the product list", async ({ page }) => {
    await page.setContent(`
      <main id="content" class="f-main f-main--products f-main--has-products">
        <div class="a-container">
          <div class="a-stack">
            <div class="f-listings--products"><article class="f-listing--product">Product</article></div>
          </div>
        </div>
      </main>
      <footer>
        <div class="ssc-loop-filters-fallback" data-sschp-archive-fallback hidden>
          <div class="ssc-loop-filters"><div class="ssc-chips"><a class="ssc-chip" data-tag="IP67">IP67</a></div></div>
        </div>
      </footer>
    `);

    await page.evaluate(() => {
      window.sschpConfig = {
        action: "sschp_search",
        themeAction: "",
        inputSelector: "input[type='search']",
        resultSelector: ".js-ssc-results",
        analytics: { enabled: false, nonce: "" },
      };
    });
    await page.addScriptTag({ path: chipsScriptPath });

    const stackChildren = page.locator("main .a-stack > *");
    await expect(stackChildren).toHaveCount(2);
    await expect(stackChildren.nth(0)).toHaveClass(/ssc-loop-filters/);
    await expect(stackChildren.nth(1)).toHaveClass(/f-listings--products/);
    await expect(page.locator("[data-sschp-archive-fallback]")).toHaveCount(0);
  });

  test("fresh site with no theme integration still has working shortcode search", async ({ page }) => {
    await installCompatFixture(page, {
      themeAction: "",
    });

    const shortcode = page.getByTestId("shortcode-search");
    await shortcode.locator(".ssc-search__input").fill("Steel");
    await shortcode.locator(".ssc-search__input").dispatchEvent("input");

    await expect(shortcode.getByRole("button", { name: /Filter by Steel/i })).toBeVisible();

    const request = last(await requests(page));
    expect(request.action).toBe("sschp_search");
    expect(request.managed).toBe("1");
    expect(request.nonce).toBe("compat-nonce");
  });

  test("admin frontend debug mode renders visible diagnostics", async ({ page }) => {
    await installCompatFixture(page, {
      themeAction: "",
      isAdmin: true,
      debug: true,
      debounceMs: 50,
    });

    const panel = page.locator(".ssc-debug-panel");
    await expect(panel).toBeVisible();
    await expect(panel).toContainText("Smart Search Chips Debug");
    await expect(panel).toContainText("Frontend selector scan");

    const shortcode = page.getByTestId("shortcode-search");
    await shortcode.locator(".ssc-search__input").fill("Steel");
    await shortcode.locator(".ssc-search__input").dispatchEvent("input");

    await expect(shortcode.getByRole("button", { name: /Filter by Steel/i })).toBeVisible();
    await expect(panel).toContainText("Managed AJAX request prepared");
  });

  test("escaped guaranteed search shortcode text is hydrated into the live search UI", async ({ page }) => {
    await installCompatFixture(page, {
      themeAction: "",
    });

    const shortcode = page.getByTestId("escaped-shortcode-search");
    const input = shortcode.locator(".ssc-search__input");

    await expect(shortcode).not.toContainText(legacyEscapedSearchShortcode);
    await expect(shortcode.locator(".ssc-search.demo-search")).toBeVisible();
    await expect(input).toHaveAttribute("placeholder", "Try searching: tea, stoneware, serving...");

    await input.fill("Steel");
    await input.dispatchEvent("input");

    await expect(shortcode.getByRole("button", { name: /Filter by Steel/i })).toBeVisible();

    const request = last(await requests(page));
    expect(request.action).toBe("sschp_search");
    expect(request.managed).toBe("1");
    expect(request.nonce).toBe("compat-nonce");
  });

  test("rapid shortcode typing only renders the latest response", async ({ page }) => {
    await installCompatFixture(page, {
      themeAction: "",
      debounceMs: 50,
      responseDelays: {
        Slow: 120,
        Fast: 0,
      },
    });

    const shortcode = page.getByTestId("shortcode-search");
    const input = shortcode.locator(".ssc-search__input");

    await input.fill("Slow");
    await input.dispatchEvent("input");
    await page.waitForTimeout(70);

    await input.fill("Fast");
    await input.dispatchEvent("input");

    await expect(shortcode.locator(".ssc-result__title")).toHaveText("Fast result");
    await page.waitForTimeout(160);
    await expect(shortcode.locator(".ssc-result__title")).toHaveText("Fast result");

    const allRequests = await requests(page);
    expect(allRequests.slice(-2).map((request) => request.keyword)).toEqual(["Slow", "Fast"]);
  });

  test("renders chips across shortcode and ugly theme request shapes", async ({ page }) => {
    await installCompatFixture(page);

    const shortcode = page.getByTestId("shortcode-search");
    await shortcode.locator(".ssc-search__input").fill("Steel");
    await shortcode.locator(".ssc-search__input").dispatchEvent("input");
    await expect(shortcode.getByRole("button", { name: /Filter by Steel/i })).toBeVisible();

    const shortcodeRequest = last(await requests(page));
    expect(shortcodeRequest.action).toBe("sschp_search");
    expect(shortcodeRequest.managed).toBe("1");

    const cases = [
      ["header-xhr-formdata", "xhr"],
      ["fetch-formdata", "fetch"],
      ["fetch-params", "fetch"],
      ["fetch-serialized", "fetch"],
      ["query-field", "fetch"],
      ["search-field", "fetch"],
      ["custom-selector-search", "fetch"],
    ];

    for (const [testId, transport] of cases) {
      const root = page.getByTestId(testId);
      await runSearch(page, testId, `${testId} steel`);
      await expect(root.getByRole("button", { name: /Filter by Steel/i })).toBeVisible();

      const request = last(await requests(page));
      expect(request.transport).toBe(transport);
      expect(request.action).toBe("search_processing");
      expect(request.managed).toBe("1");
      expect(request.nonce).toBe("compat-nonce");
      expect(request.keyword).toContain("steel");
    }
  });

  test("chip click keeps the relevant theme search open and does not affect another form", async ({ page }) => {
    await installCompatFixture(page);

    await runSearch(page, "desktop-search", "Steel");
    await runSearch(page, "mobile-search", "Steel");

    const desktop = page.getByTestId("desktop-search");
    const mobile = page.getByTestId("mobile-search");

    await desktop.getByRole("button", { name: /Filter by Steel/i }).click();

    await expect(desktop.locator(".js-search__results")).not.toBeHidden();
    await expect(desktop.getByRole("button", { name: /Remove filter Steel/i })).toHaveAttribute("aria-pressed", "true");
    await expect(mobile.getByRole("button", { name: /Filter by Steel/i })).toHaveAttribute("aria-pressed", "false");

    const request = last(await requests(page));
    expect(request.action).toBe("search_processing");
    expect(request.managed).toBe("1");
    expect(request.activeTags).toEqual(["Steel"]);

    await runSearch(page, "custom-selector-search", "Custom steel");
    const custom = page.getByTestId("custom-selector-search");
    await custom.getByRole("button", { name: /Filter by Steel/i }).click();
    await expect(custom.getByRole("button", { name: /Remove filter Steel/i })).toHaveAttribute("aria-pressed", "true");

    const customRequest = last(await requests(page));
    expect(customRequest.managed).toBe("1");
    expect(customRequest.activeTags).toEqual(["Steel"]);
  });

  test("invalid selectors fall back without crashing shortcode search", async ({ page }) => {
    const pageErrors = [];
    page.on("pageerror", (error) => pageErrors.push(error.message));

    await installCompatFixture(page, {
      inputSelector: ".broken-input[",
      resultSelector: ".broken-results[",
      isAdmin: true,
    });

    const shortcode = page.getByTestId("shortcode-search");
    await shortcode.locator(".ssc-search__input").fill("Steel");
    await shortcode.locator(".ssc-search__input").dispatchEvent("input");

    await expect(shortcode.getByRole("button", { name: /Filter by Steel/i })).toBeVisible();
    expect(pageErrors).toEqual([]);
  });

  test("normal GET search form is not hijacked by shortcode AJAX handling", async ({ page }) => {
    await installCompatFixture(page);

    const allowed = await page.evaluate(() => {
      const form = document.querySelector('[data-testid="normal-get-search"] form');
      const event = new Event("submit", { bubbles: true, cancelable: true });
      return form.dispatchEvent(event);
    });

    expect(allowed).toBe(true);
  });

  test("original theme search is not blocked when SSC marker and nonce are missing", async ({ page }) => {
    await installCompatFixture(page, {
      themeAction: "different_action",
    });

    await runSearch(page, "raw-theme-search", "Steel");

    const root = page.getByTestId("raw-theme-search");
    await expect(root.locator(".theme-original-result")).toHaveText("Original theme search response");

    const request = last(await requests(page));
    expect(request.action).toBe("raw_theme_search");
    expect(request.managed).toBe("");
    expect(request.nonce).toBe("");
    expect(request.activeTags).toEqual([]);
  });

  test("theme detection failure keeps existing theme search passive", async ({ page }) => {
    await installCompatFixture(page, {
      themeAction: "",
    });

    await runSearch(page, "header-xhr-formdata", "Steel");

    const root = page.getByTestId("header-xhr-formdata");
    await expect(root.locator(".theme-original-result")).toHaveText("Original theme search response");

    const request = last(await requests(page));
    expect(request.action).toBe("search_processing");
    expect(request.managed).toBe("");
    expect(request.nonce).toBe("");
    expect(request.activeTags).toEqual([]);
  });

  test("unrelated AJAX and fetch requests are untouched", async ({ page }) => {
    await installCompatFixture(page);

    await page.evaluate(async () => {
      const unrelatedAjax = new FormData();
      unrelatedAjax.append("action", "unrelated_action");
      unrelatedAjax.append("keyword", "Steel");
      await fetch("/wp-admin/admin-ajax.php", { method: "POST", body: unrelatedAjax });

      const nonAjaxSearch = new FormData();
      nonAjaxSearch.append("action", "search_processing");
      nonAjaxSearch.append("keyword", "Steel");
      await fetch("/wp-json/example", { method: "POST", body: nonAjaxSearch });
    });

    const allRequests = await requests(page);
    const unrelated = allRequests.slice(-2);

    expect(unrelated[0].action).toBe("unrelated_action");
    expect(unrelated[0].managed).toBe("");
    expect(unrelated[0].nonce).toBe("");
    expect(unrelated[0].activeTags).toEqual([]);

    expect(unrelated[1].url).toContain("/wp-json/example");
    expect(unrelated[1].action).toBe("search_processing");
    expect(unrelated[1].managed).toBe("");
    expect(unrelated[1].nonce).toBe("");
    expect(unrelated[1].activeTags).toEqual([]);
  });
});
