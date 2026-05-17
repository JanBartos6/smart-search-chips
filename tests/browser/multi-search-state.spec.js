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
        const entries = [];

        if (data && typeof data.entries === "function") {
          for (const pair of data.entries()) {
            entries.push([pair[0], String(pair[1])]);
          }
        }

        const activeTags = entries
          .filter((entry) => entry[0] === "active_tags[]")
          .map((entry) => entry[1]);
        const activeClass = activeTags.includes("spring handle") ? " ssc-chip--active" : "";

        window.__sschpRequests.push({
          action: (entries.find((entry) => entry[0] === "action") || [null, ""])[1],
          keyword: (entries.find((entry) => entry[0] === "keyword") || [null, ""])[1],
          managed: (entries.find((entry) => entry[0] === "sschp_managed") || [null, ""])[1],
          nonce: (entries.find((entry) => entry[0] === "sschp_nonce") || [null, ""])[1],
          activeTags
        });

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
  }, options);
}

async function readRequests(page) {
  return page.evaluate(() => window.__sschpRequests);
}

function lastRequest(requests) {
  return requests[requests.length - 1];
}

test.describe("Multiple search box state", () => {
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
});
