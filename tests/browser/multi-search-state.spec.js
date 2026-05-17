const { test, expect } = require("@playwright/test");
const path = require("path");

const chipsScriptPath = path.join(__dirname, "..", "..", "assets", "js", "chips.js");

async function installSearchFixture(page, options = {}) {
  await page.setContent(`
    <main>
      <div class="ssc-search" data-testid="first-search">
        <form class="ssc-search__form js-ssc-search" role="search">
          <input type="search" class="ssc-search__input" name="keyword" />
          <button type="button" class="ssc-search__clear">Clear</button>
        </form>
        <div class="ssc-search__results js-ssc-results" aria-live="polite" aria-busy="false"></div>
      </div>

      <div class="ssc-search" data-testid="second-search">
        <form class="ssc-search__form js-ssc-search" role="search">
          <input type="search" class="ssc-search__input" name="keyword" />
          <button type="button" class="ssc-search__clear">Clear</button>
        </form>
        <div class="ssc-search__results js-ssc-results" aria-live="polite" aria-busy="false"></div>
      </div>
    </main>
  `);

  await installFakeAjax(page, options);
  await page.addScriptTag({ path: chipsScriptPath });
}

async function installThemeSearchFixture(page) {
  await page.setContent(`
    <main>
      <div class="js-search" data-testid="header-search">
        <input type="search" name="keyword" value="Trapped" />
        <div class="js-search__results">
          <button type="button" class="ssc-chip" data-tag="spring handle">spring handle (4)</button>
        </div>
      </div>

      <div class="js-search" data-testid="mobile-search">
        <input type="search" name="keyword" value="Trapped" />
        <div class="js-search__results"></div>
      </div>
    </main>
  `);

  await installFakeAjax(page, { action: "search_processing" });
  await page.addScriptTag({ path: chipsScriptPath });
}

async function installCustomSelectorFixture(page) {
  await page.setContent(`
    <main>
      <section class="custom-search-root" data-testid="custom-search">
        <form>
          <input type="text" class="custom-search-input" name="keyword" />
        </form>
        <div class="custom-search-results"></div>
      </section>
    </main>
  `);

  await installFakeAjax(page, {
    inputSelector: ".custom-search-input",
    resultSelector: ".custom-search-results"
  });
  await page.addScriptTag({ path: chipsScriptPath });
}

async function installFakeAjax(page, options = {}) {
  await page.evaluate((config) => {
    window.__sschpRequests = [];
    window.__sschpFetchCalls = [];
    window.__sschpBeaconCalls = [];
    window.sschpConfig = {
      action: config.action || "sschp_search",
      ajaxUrl: "/wp-admin/admin-ajax.php",
      minChars: 2,
      nonce: "test-nonce",
      inputSelector: config.inputSelector || "",
      resultSelector: config.resultSelector || "",
      isAdmin: !!config.isAdmin,
      primaryChipTag: config.primaryChipTag || "spring handle",
      responseDelay: config.responseDelay || 0,
      analytics: config.analytics || { enabled: false, nonce: "" }
    };

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
        return Array.from(body.entries()).map((entry) => [entry[0], String(entry[1])]);
      }

      if (typeof body === "string") {
        const trimmed = body.trim();

        if (trimmed.startsWith("{")) {
          try {
            const parsed = JSON.parse(trimmed);
            return Object.keys(parsed).flatMap((key) => {
              const value = parsed[key];
              return Array.isArray(value)
                ? value.map((item) => [key, String(item)])
                : [[key, String(value)]];
            });
          } catch (error) {
            return [];
          }
        }

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

    function recordRequest(transport, method, url, body) {
      const entries = entriesFromBody(body).concat(entriesFromUrl(url));
      const activeTags = entries
        .filter((entry) => entry[0] === "active_tags[]" || entry[0] === "active_tags")
        .map((entry) => entry[1]);

      const request = {
        transport,
        method,
        url: String(url || ""),
        action: (entries.find((entry) => entry[0] === "action") || [null, ""])[1],
        eventType: (entries.find((entry) => entry[0] === "event_type") || [null, ""])[1],
        keyword: (entries.find((entry) => entry[0] === "keyword") || entries.find((entry) => entry[0] === "query") || [null, ""])[1],
        searchQuery: (entries.find((entry) => entry[0] === "search_query") || [null, ""])[1],
        managed: (entries.find((entry) => entry[0] === "sschp_managed") || [null, ""])[1],
        nonce: (entries.find((entry) => entry[0] === "sschp_nonce") || [null, ""])[1],
        analyticsNonce: (entries.find((entry) => entry[0] === "nonce") || [null, ""])[1],
        activeTagCount: (entries.find((entry) => entry[0] === "active_tag_count") || [null, ""])[1],
        chipLabels: entries
          .filter((entry) => entry[0] === "chip_labels[]" || entry[0] === "chip_labels")
          .map((entry) => entry[1]),
        activeTags
      };

      window.__sschpRequests.push(request);

      if (transport === "fetch") {
        window.__sschpFetchCalls.push(request);
      }

      return request;
    }

    if (config.sendBeaconMode) {
      Object.defineProperty(window.navigator, "sendBeacon", {
        configurable: true,
        value: (url, body) => {
          const request = recordRequest("beacon", "POST", url, body);
          window.__sschpBeaconCalls.push(request);

          if (config.sendBeaconMode === "throw") {
            throw new Error("sendBeacon failed");
          }

          return config.sendBeaconMode === "true";
        }
      });
    } else {
      try {
        delete window.navigator.sendBeacon;
      } catch (error) {
        Object.defineProperty(window.navigator, "sendBeacon", {
          configurable: true,
          value: undefined
        });
      }
    }

    class FakeXMLHttpRequest {
      constructor() {
        this.listeners = {};
        this.readyState = 0;
        this.status = 0;
        this.responseText = "";
        this.responseHeaders = {
          "X-SSC-Results-Count": "1"
        };
      }

      open(method, url) {
        this.method = method;
        this.url = url;
      }

      setRequestHeader(name, value) {
        this.headers = this.headers || {};
        this.headers[name] = value;
      }

      addEventListener(type, callback, listenerOptions) {
        this.listeners[type] = this.listeners[type] || [];
        this.listeners[type].push({ callback, once: !!(listenerOptions && listenerOptions.once) });
      }

      getResponseHeader(name) {
        return this.responseHeaders[name] || null;
      }

      send(data) {
        const request = recordRequest("xhr", this.method || "GET", this.url || "", data);
        const activeTags = request.activeTags;
        const primaryChipTag = window.sschpConfig.primaryChipTag;
        const isPrimaryActive = activeTags.some((tag) => tag.toLowerCase() === primaryChipTag.toLowerCase());
        const activeClass = isPrimaryActive ? " ssc-chip--active" : "";
        const pressed = isPrimaryActive ? "true" : "false";
        const primaryAction = isPrimaryActive ? "Remove filter" : "Filter by";

        const complete = () => {
          this.status = 200;
          this.readyState = 4;
          this.responseText = `
            <div class="ssc-chips">
              <button type="button" class="ssc-chip${activeClass}" data-tag="${primaryChipTag}" aria-pressed="${pressed}" aria-label="${primaryAction} ${primaryChipTag}, 4 results">${primaryChipTag} (4)</button>
              <button type="button" class="ssc-chip" data-tag="trapped key interlock" aria-pressed="false" aria-label="Filter by trapped key interlock, 2 results">trapped key interlock (2)</button>
            </div>
            <ul class="ssc-results">
              <li class="ssc-result"><a href="#"><span class="ssc-result__title">Trapped Key Guard Door Interlock</span></a></li>
            </ul>
          `;

          if (typeof this.onreadystatechange === "function") {
            this.onreadystatechange();
          }

          this.dispatch("loadend");
        };

        if (window.sschpConfig.responseDelay > 0) {
          window.setTimeout(complete, window.sschpConfig.responseDelay);
          return;
        }

        complete();
      }

      dispatch(type) {
        const listeners = this.listeners[type] || [];
        this.listeners[type] = listeners.filter((listener) => {
          listener.callback.call(this);
          return !listener.once;
        });
      }
    }

    window.XMLHttpRequest = FakeXMLHttpRequest;
    window.fetch = (input, init = {}) => {
      const url = typeof input === "string" || input instanceof URL ? String(input) : input.url;
      const method = init.method || (input && input.method) || "GET";
      const body = Object.prototype.hasOwnProperty.call(init, "body") ? init.body : null;

      const request = recordRequest("fetch", method, url, body);

      if (config.analyticsFetchMode === "reject" && request.action === "sschp_track_event") {
        return Promise.reject(new Error("analytics endpoint failed"));
      }

      return Promise.resolve(new Response("", {
        status: 200,
        headers: {
          "X-SSC-Results-Count": "1"
        }
      }));
    };
  }, options);
}

async function readRequests(page) {
  return page.evaluate(() => window.__sschpRequests);
}

async function readBeaconCalls(page) {
  return page.evaluate(() => window.__sschpBeaconCalls);
}

async function readFetchCalls(page) {
  return page.evaluate(() => window.__sschpFetchCalls);
}

function lastRequest(requests) {
  return requests[requests.length - 1];
}

function analyticsEvents(requests, eventType) {
  return requests.filter((request) => request.action === "sschp_track_event" && request.eventType === eventType);
}

async function activateHeaderChipAndResetRequests(page) {
  const header = page.getByTestId("header-search");

  await header.locator("input[type='search']").focus();
  await header.getByRole("button", { name: /spring handle/i }).click();
  await expect(header.locator(".ssc-chip--active")).toHaveCount(1);
  await page.evaluate(() => {
    window.__sschpRequests = [];
    window.__sschpFetchCalls = [];
  });
}

test.describe("Multiple search box state", () => {
  test("exposes chip controls with accessible names and pressed state", async ({ page }) => {
    await installSearchFixture(page, {
      responseDelay: 50
    });

    const first = page.getByTestId("first-search");
    const input = first.locator(".ssc-search__input");
    const results = first.locator(".ssc-search__results");

    await expect(results).toHaveAttribute("aria-live", "polite");
    await expect(results).toHaveAttribute("aria-busy", "false");

    await input.fill("Trapped");
    await input.dispatchEvent("input");

    const chip = first.getByRole("button", { name: /Filter by spring handle, 4 results/i });
    await expect(chip).toBeVisible();
    await expect(chip).toHaveAttribute("aria-pressed", "false");

    await chip.focus();
    await chip.press("Enter");

    const activeChip = first.getByRole("button", { name: /Remove filter spring handle, 4 results/i });
    await expect(activeChip).toHaveAttribute("aria-pressed", "true");
    await expect(activeChip).toBeFocused();
  });

  test("toggles aria-busy while AJAX results update", async ({ page }) => {
    await installSearchFixture(page, {
      responseDelay: 600
    });

    const first = page.getByTestId("first-search");
    const input = first.locator(".ssc-search__input");
    const results = first.locator(".ssc-search__results");

    await input.fill("Trapped");
    await input.dispatchEvent("input");

    await expect(results).toHaveAttribute("aria-busy", "true");
    await expect(first.locator(".ssc-results .ssc-result")).toHaveCount(1);
    await expect(results).toHaveAttribute("aria-busy", "false");
  });

  test("falls back to fetch when sendBeacon returns false", async ({ page }) => {
    await installSearchFixture(page, {
      analytics: { enabled: true, nonce: "analytics-nonce" },
      sendBeaconMode: "false"
    });

    const first = page.getByTestId("first-search");
    const input = first.locator(".ssc-search__input");

    await input.fill("Trapped");
    await input.dispatchEvent("input");
    await expect(first.locator(".ssc-results .ssc-result")).toHaveCount(1);
    await page.waitForFunction(() => window.__sschpFetchCalls.some((request) => request.action === "sschp_track_event"));

    const beaconCalls = await readBeaconCalls(page);
    const fetchCalls = await readFetchCalls(page);

    expect(analyticsEvents(beaconCalls, "search")).toHaveLength(1);
    expect(analyticsEvents(fetchCalls, "search")).toHaveLength(1);
    expect(analyticsEvents(fetchCalls, "search")[0].analyticsNonce).toBe("analytics-nonce");
  });

  test("does not send analytics requests when analytics is disabled", async ({ page }) => {
    await installSearchFixture(page, {
      sendBeaconMode: "false"
    });

    const first = page.getByTestId("first-search");
    const input = first.locator(".ssc-search__input");

    await input.fill("Trapped");
    await input.dispatchEvent("input");
    await expect(first.locator(".ssc-results .ssc-result")).toHaveCount(1);

    const requests = await readRequests(page);
    const beaconCalls = await readBeaconCalls(page);
    const fetchCalls = await readFetchCalls(page);

    expect(analyticsEvents(requests, "search")).toHaveLength(0);
    expect(beaconCalls).toHaveLength(0);
    expect(analyticsEvents(fetchCalls, "search")).toHaveLength(0);
  });

  test("suppresses duplicate chip impression batches per search root and query", async ({ page }) => {
    await installSearchFixture(page, {
      analytics: { enabled: true, nonce: "analytics-nonce" },
      sendBeaconMode: "false"
    });

    const first = page.getByTestId("first-search");
    const input = first.locator(".ssc-search__input");

    await input.fill("Trapped");
    await input.dispatchEvent("input");
    await expect(first.locator(".ssc-results .ssc-result")).toHaveCount(1);

    await input.dispatchEvent("input");
    await page.waitForFunction(() => window.__sschpRequests.filter((request) => request.action === "sschp_search").length >= 2);
    await page.waitForTimeout(100);

    const fetchCalls = await readFetchCalls(page);
    const impressionEvents = analyticsEvents(fetchCalls, "chip_impression");

    expect(impressionEvents).toHaveLength(1);
    expect(impressionEvents[0].searchQuery).toBe("Trapped");
    expect(impressionEvents[0].chipLabels).toEqual(["spring handle", "trapped key interlock"]);
  });

  test("keeps search working when analytics fetch rejects", async ({ page }) => {
    const pageErrors = [];
    page.on("pageerror", (error) => pageErrors.push(error.message));

    await installSearchFixture(page, {
      analytics: { enabled: true, nonce: "analytics-nonce" },
      sendBeaconMode: "false",
      analyticsFetchMode: "reject"
    });

    const first = page.getByTestId("first-search");
    const input = first.locator(".ssc-search__input");

    await input.fill("Trapped");
    await input.dispatchEvent("input");

    await expect(first.locator(".ssc-results .ssc-result")).toHaveCount(1);
    await page.waitForTimeout(100);
    expect(pageErrors).toEqual([]);
  });

  test("runs live search for paste-style input events", async ({ page }) => {
    await installSearchFixture(page);

    const first = page.getByTestId("first-search");
    const input = first.locator(".ssc-search__input");

    await input.evaluate((el) => {
      el.focus();
      el.value = "Trapped";
      el.dispatchEvent(new InputEvent("input", {
        bubbles: true,
        data: "Trapped",
        inputType: "insertFromPaste"
      }));
    });

    await expect(first.locator(".ssc-results .ssc-result")).toHaveCount(1);

    const requests = await readRequests(page);
    expect(lastRequest(requests).keyword).toBe("Trapped");
  });

  test("runs live search after Playwright fill and explicit input dispatch", async ({ page }) => {
    await installSearchFixture(page);

    const first = page.getByTestId("first-search");
    const input = first.locator(".ssc-search__input");

    await input.fill("Trapped");
    await input.dispatchEvent("input");

    await expect(first.locator(".ssc-results .ssc-result")).toHaveCount(1);

    const requests = await readRequests(page);
    expect(lastRequest(requests).keyword).toBe("Trapped");
  });

  test("clearing input resets only that search root", async ({ page }) => {
    await installSearchFixture(page);

    const first = page.getByTestId("first-search");
    const second = page.getByTestId("second-search");
    const firstInput = first.locator(".ssc-search__input");
    const secondInput = second.locator(".ssc-search__input");

    await firstInput.fill("Trapped");
    await firstInput.dispatchEvent("input");
    await expect(first.locator(".ssc-results .ssc-result")).toHaveCount(1);

    await first.getByRole("button", { name: /spring handle/i }).click();
    await expect(first.locator(".ssc-chip--active")).toHaveCount(1);

    await secondInput.fill("Trapped");
    await secondInput.dispatchEvent("input");
    await expect(second.locator(".ssc-results .ssc-result")).toHaveCount(1);

    await firstInput.fill("");
    await firstInput.dispatchEvent("input");

    await expect(first.locator(".ssc-results .ssc-result")).toHaveCount(0);
    await expect(first.locator(".ssc-chips .ssc-chip")).toHaveCount(0);
    await expect(second.locator(".ssc-results .ssc-result")).toHaveCount(1);

    await firstInput.fill("Trapped");
    await firstInput.dispatchEvent("input");
    await expect(first.locator(".ssc-results .ssc-result")).toHaveCount(1);

    const requests = await readRequests(page);
    expect(lastRequest(requests).activeTags).toEqual([]);
  });

  test("Escape closes only the current search panel", async ({ page }) => {
    await installSearchFixture(page);

    const first = page.getByTestId("first-search");
    const second = page.getByTestId("second-search");
    const firstInput = first.locator(".ssc-search__input");
    const secondInput = second.locator(".ssc-search__input");

    await firstInput.fill("Trapped");
    await firstInput.dispatchEvent("input");
    await expect(first.locator(".ssc-results .ssc-result")).toHaveCount(1);

    await secondInput.fill("Trapped");
    await secondInput.dispatchEvent("input");
    await expect(second.locator(".ssc-results .ssc-result")).toHaveCount(1);

    await firstInput.focus();
    await firstInput.press("Escape");

    await expect(first.locator(".ssc-results .ssc-result")).toHaveCount(0);
    await expect(second.locator(".ssc-results .ssc-result")).toHaveCount(1);
  });

  test("falls back to defaults when configured selectors are invalid", async ({ page }) => {
    const pageErrors = [];
    page.on("pageerror", (error) => pageErrors.push(error.message));

    await installSearchFixture(page, {
      inputSelector: ".broken-input[",
      resultSelector: ".broken-results[",
      isAdmin: true
    });

    const first = page.getByTestId("first-search");
    const firstInput = first.locator(".ssc-search__input");

    await firstInput.click();
    await firstInput.pressSequentially("Trapped", { delay: 10 });

    await expect(first.locator(".ssc-results .ssc-result")).toHaveCount(1);
    expect(pageErrors).toEqual([]);
  });

  test("supports custom valid input and results selectors", async ({ page }) => {
    await installCustomSelectorFixture(page);

    const custom = page.getByTestId("custom-search");
    const input = custom.locator(".custom-search-input");

    await input.click();
    await input.pressSequentially("Trapped", { delay: 10 });

    await expect(custom.locator(".custom-search-results .ssc-results .ssc-result")).toHaveCount(1);

    const requests = await readRequests(page);
    expect(lastRequest(requests).keyword).toBe("Trapped");
    expect(lastRequest(requests).managed).toBe("1");
  });

  test("keeps shortcode active chips isolated per search root", async ({ page }) => {
    await installSearchFixture(page);

    const first = page.getByTestId("first-search");
    const second = page.getByTestId("second-search");
    const firstInput = first.locator(".ssc-search__input");
    const secondInput = second.locator(".ssc-search__input");

    await firstInput.click();
    await firstInput.pressSequentially("Trapped", { delay: 10 });
    await expect(first.locator(".ssc-results .ssc-result")).toHaveCount(1);

    await first.getByRole("button", { name: /spring handle/i }).click();
    await expect(first.locator(".ssc-chip--active")).toHaveCount(1);

    let requests = await readRequests(page);
    expect(lastRequest(requests).activeTags).toEqual(["spring handle"]);

    await secondInput.click();
    await secondInput.pressSequentially("Trapped", { delay: 10 });
    await expect(second.locator(".ssc-results .ssc-result")).toHaveCount(1);
    await expect(second.locator(".ssc-chip--active")).toHaveCount(0);

    requests = await readRequests(page);
    expect(lastRequest(requests).activeTags).toEqual([]);

    await first.getByRole("button", { name: "Clear" }).click();
    await expect(first.locator(".ssc-results .ssc-result")).toHaveCount(0);
    await expect(second.locator(".ssc-results .ssc-result")).toHaveCount(1);
    await expect(second.locator(".ssc-chip--active")).toHaveCount(0);
  });

  test("toggles active shortcode chips case-insensitively", async ({ page }) => {
    await installSearchFixture(page, {
      primaryChipTag: "Spring Handle"
    });

    const search = page.getByTestId("first-search");
    const input = search.locator("input[type='search']");

    await input.fill("Trapped");
    await input.dispatchEvent("input");
    await expect(search.getByRole("button", { name: /spring handle/i })).toBeVisible();

    await search.getByRole("button", { name: /spring handle/i }).click();
    let requests = await readRequests(page);
    expect(lastRequest(requests).activeTags).toEqual(["Spring Handle"]);

    await search.getByRole("button", { name: /spring handle/i }).evaluate((chip) => {
      chip.setAttribute("data-tag", "spring handle");
    });
    await search.getByRole("button", { name: /spring handle/i }).click();

    requests = await readRequests(page);
    expect(lastRequest(requests).activeTags).toEqual([]);
  });

  test("does not leak header search chips into a separate mobile theme AJAX request", async ({ page }) => {
    await installThemeSearchFixture(page);

    const header = page.getByTestId("header-search");
    const mobile = page.getByTestId("mobile-search");

    await header.locator("input[type='search']").focus();
    await header.getByRole("button", { name: /spring handle/i }).click();
    await expect(header.locator(".ssc-chip--active")).toHaveCount(1);

    let requests = await readRequests(page);
    expect(lastRequest(requests).activeTags).toEqual(["spring handle"]);

    await mobile.locator("input[type='search']").focus();
    await page.evaluate(() => {
      const data = new FormData();
      data.append("action", "search_processing");
      data.append("keyword", "Trapped");

      const xhr = new XMLHttpRequest();
      xhr.open("POST", "/wp-admin/admin-ajax.php", true);
      xhr.send(data);
    });

    requests = await readRequests(page);
    expect(lastRequest(requests).managed).toBe("1");
    expect(lastRequest(requests).nonce).toBe("test-nonce");
    expect(lastRequest(requests).activeTags).toEqual([]);
  });

  test("adds active tags to XMLHttpRequest FormData theme searches", async ({ page }) => {
    await installThemeSearchFixture(page);
    await activateHeaderChipAndResetRequests(page);

    await page.evaluate(() => {
      const data = new FormData();
      data.append("action", "search_processing");
      data.append("keyword", "Trapped");

      const xhr = new XMLHttpRequest();
      xhr.open("POST", "/wp-admin/admin-ajax.php", true);
      xhr.send(data);
    });

    const request = lastRequest(await readRequests(page));
    const diagnostics = await page.evaluate(() => window.sschpDiagnostics);

    expect(request.transport).toBe("xhr");
    expect(request.managed).toBe("1");
    expect(request.nonce).toBe("test-nonce");
    expect(request.activeTags).toEqual(["spring handle"]);
    expect(diagnostics.lastDetectedRequestMethod).toBe("POST");
    expect(diagnostics.actionDetected).toBe(true);
    expect(diagnostics.keywordFound).toBe(true);
    expect(diagnostics.nonceAttached).toBe(true);
    expect(diagnostics.resultContainerFound).toBe(true);
  });

  test("adds active tags to fetch FormData theme searches", async ({ page }) => {
    await installThemeSearchFixture(page);
    await activateHeaderChipAndResetRequests(page);

    await page.evaluate(async () => {
      const data = new FormData();
      data.append("action", "search_processing");
      data.append("keyword", "Trapped");

      await fetch("/wp-admin/admin-ajax.php", {
        method: "POST",
        body: data
      });
    });

    const request = lastRequest(await readRequests(page));

    expect(request.transport).toBe("fetch");
    expect(request.managed).toBe("1");
    expect(request.nonce).toBe("test-nonce");
    expect(request.activeTags).toEqual(["spring handle"]);
  });

  test("adds active tags to fetch URLSearchParams theme searches", async ({ page }) => {
    await installThemeSearchFixture(page);
    await activateHeaderChipAndResetRequests(page);

    await page.evaluate(async () => {
      const data = new URLSearchParams();
      data.append("action", "search_processing");
      data.append("keyword", "Trapped");

      await fetch("/wp-admin/admin-ajax.php", {
        method: "POST",
        body: data
      });
    });

    const request = lastRequest(await readRequests(page));

    expect(request.transport).toBe("fetch");
    expect(request.managed).toBe("1");
    expect(request.nonce).toBe("test-nonce");
    expect(request.activeTags).toEqual(["spring handle"]);
  });

  test("adds active tags to serialized string theme searches", async ({ page }) => {
    await installThemeSearchFixture(page);
    await activateHeaderChipAndResetRequests(page);

    await page.evaluate(() => {
      const xhr = new XMLHttpRequest();
      xhr.open("POST", "/wp-admin/admin-ajax.php", true);
      xhr.send("action=search_processing&keyword=Trapped");
    });

    const request = lastRequest(await readRequests(page));

    expect(request.transport).toBe("xhr");
    expect(request.managed).toBe("1");
    expect(request.nonce).toBe("test-nonce");
    expect(request.activeTags).toEqual(["spring handle"]);
  });

  test("adds active tags to GET admin-ajax query searches", async ({ page }) => {
    await installThemeSearchFixture(page);
    await activateHeaderChipAndResetRequests(page);

    await page.evaluate(async () => {
      await fetch("/wp-admin/admin-ajax.php?action=search_processing&keyword=Trapped");
    });

    const request = lastRequest(await readRequests(page));

    expect(request.transport).toBe("fetch");
    expect(request.method).toBe("GET");
    expect(request.managed).toBe("1");
    expect(request.nonce).toBe("test-nonce");
    expect(request.activeTags).toEqual(["spring handle"]);
  });

  test("adds request fields to fetch JSON theme searches via query params", async ({ page }) => {
    await installThemeSearchFixture(page);
    await activateHeaderChipAndResetRequests(page);

    await page.evaluate(async () => {
      await fetch("/wp-admin/admin-ajax.php", {
        method: "POST",
        body: JSON.stringify({
          action: "search_processing",
          keyword: "Trapped"
        })
      });
    });

    const request = lastRequest(await readRequests(page));

    expect(request.transport).toBe("fetch");
    expect(request.managed).toBe("1");
    expect(request.nonce).toBe("test-nonce");
    expect(request.activeTags).toEqual(["spring handle"]);
  });

  test("does not duplicate active tags on already managed theme requests", async ({ page }) => {
    await installThemeSearchFixture(page);
    await activateHeaderChipAndResetRequests(page);

    await page.evaluate(async () => {
      const data = new FormData();
      data.append("action", "search_processing");
      data.append("keyword", "Trapped");
      data.append("sschp_managed", "1");
      data.append("sschp_nonce", "test-nonce");
      data.append("active_tags[]", "spring handle");

      await fetch("/wp-admin/admin-ajax.php", {
        method: "POST",
        body: data
      });
    });

    const request = lastRequest(await readRequests(page));

    expect(request.transport).toBe("fetch");
    expect(request.managed).toBe("1");
    expect(request.nonce).toBe("test-nonce");
    expect(request.activeTags).toEqual(["spring handle"]);
  });

  test("does not modify unrelated fetch requests", async ({ page }) => {
    await installThemeSearchFixture(page);
    await activateHeaderChipAndResetRequests(page);

    await page.evaluate(async () => {
      const data = new FormData();
      data.append("action", "unrelated_action");
      data.append("keyword", "Trapped");

      await fetch("/wp-admin/admin-ajax.php", {
        method: "POST",
        body: data
      });

      const other = new FormData();
      other.append("action", "search_processing");
      other.append("keyword", "Trapped");

      await fetch("/wp-json/example", {
        method: "POST",
        body: other
      });
    });

    const requests = await readRequests(page);

    expect(requests[0].transport).toBe("fetch");
    expect(requests[0].action).toBe("unrelated_action");
    expect(requests[0].managed).toBe("");
    expect(requests[0].nonce).toBe("");
    expect(requests[0].activeTags).toEqual([]);

    expect(requests[1].transport).toBe("fetch");
    expect(requests[1].url).toContain("/wp-json/example");
    expect(requests[1].managed).toBe("");
    expect(requests[1].nonce).toBe("");
    expect(requests[1].activeTags).toEqual([]);
  });
});
