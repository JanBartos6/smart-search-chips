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
        <div class="ssc-search__results js-ssc-results"></div>
      </div>

      <div class="ssc-search" data-testid="second-search">
        <form class="ssc-search__form js-ssc-search" role="search">
          <input type="search" class="ssc-search__input" name="keyword" />
          <button type="button" class="ssc-search__clear">Clear</button>
        </form>
        <div class="ssc-search__results js-ssc-results"></div>
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
    window.sschpConfig = {
      action: config.action || "sschp_search",
      ajaxUrl: "/wp-admin/admin-ajax.php",
      minChars: 2,
      nonce: "test-nonce",
      inputSelector: config.inputSelector || "",
      resultSelector: config.resultSelector || "",
      isAdmin: !!config.isAdmin,
      analytics: { enabled: false }
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
        keyword: (entries.find((entry) => entry[0] === "keyword") || entries.find((entry) => entry[0] === "query") || [null, ""])[1],
        managed: (entries.find((entry) => entry[0] === "sschp_managed") || [null, ""])[1],
        nonce: (entries.find((entry) => entry[0] === "sschp_nonce") || [null, ""])[1],
        activeTags
      };

      window.__sschpRequests.push(request);

      if (transport === "fetch") {
        window.__sschpFetchCalls.push(request);
      }

      return request;
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
        const activeClass = activeTags.includes("spring handle") ? " ssc-chip--active" : "";

        this.status = 200;
        this.readyState = 4;
        this.responseText = `
          <div class="ssc-chips">
            <button type="button" class="ssc-chip${activeClass}" data-tag="spring handle">spring handle (4)</button>
            <button type="button" class="ssc-chip" data-tag="trapped key interlock">trapped key interlock (2)</button>
          </div>
          <ul class="ssc-results">
            <li class="ssc-result"><a href="#"><span class="ssc-result__title">Trapped Key Guard Door Interlock</span></a></li>
          </ul>
        `;

        if (typeof this.onreadystatechange === "function") {
          this.onreadystatechange();
        }

        this.dispatch("loadend");
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

      recordRequest("fetch", method, url, body);

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

function lastRequest(requests) {
  return requests[requests.length - 1];
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
