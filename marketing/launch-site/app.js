(function () {
  const chipOrder = [
    "everyday",
    "travel",
    "water resistant",
    "laptop",
    "carry-on",
    "commuter",
    "camera",
    "packable",
    "hiking"
  ];

  const searchStopWords = new Set(["backpack", "backpacks", "bag", "bags", "pack", "packs"]);
  const hiddenSuggestionTags = new Set(["guide"]);
  const volumeBounds = { min: 12, max: 45 };

  const demoResults = [
    ["City Mini Backpack 12L", "Tags: everyday, commuter, 12L", "$46.00", ["everyday", "commuter", "12L"], "./assets/backpacks/everyday-backpack.jpg", 12],
    ["Compact Camera Sling 14L", "Tags: camera, everyday, 14L, water resistant", "$84.00", ["camera", "everyday", "14L", "water resistant"], "./assets/backpacks/camera-backpack.jpg", 14],
    ["Student Laptop Pack 16L", "Tags: laptop, everyday, 16L", "$69.00", ["laptop", "everyday", "16L"], "./assets/backpacks/student-navy-backpack.jpg", 16],
    ["Office Commuter Backpack 18L", "Tags: commuter, laptop, 18L, water resistant", "$92.00", ["commuter", "laptop", "18L", "water resistant"], "./assets/backpacks/rolltop-commuter-pack.jpg", 18],
    ["Everyday Backpack 20L", "Tags: everyday, 20L, water resistant", "$79.00", ["everyday", "20L", "water resistant"], "./assets/backpacks/everyday-backpack.jpg", 20],
    ["Trail Daypack 20L", "Tags: hiking, 20L, water resistant", "$74.00", ["hiking", "20L", "water resistant"], "./assets/backpacks/trail-daypack.jpg", 20],
    ["Packable Daypack 20L", "Tags: packable, travel, carry-on, 20L", "$49.00", ["packable", "travel", "carry-on", "20L"], "./assets/backpacks/packable-daypack.jpg", 20],
    ["Packable Rain Daypack 20L", "Tags: packable, hiking, water resistant, 20L", "$59.00", ["packable", "hiking", "water resistant", "20L"], "./assets/backpacks/packable-daypack.jpg", 20],
    ["Photo Daypack 22L", "Tags: camera, travel, laptop, 22L, water resistant", "$159.00", ["camera", "travel", "laptop", "22L", "water resistant"], "./assets/backpacks/camera-backpack.jpg", 22],
    ["Metro Rolltop 24L", "Tags: commuter, laptop, 24L, water resistant", "$118.00", ["commuter", "laptop", "24L", "water resistant"], "./assets/backpacks/rolltop-commuter-pack.jpg", 24],
    ["Modular Tech Backpack 26L", "Tags: commuter, laptop, travel, 26L, water resistant", "$134.00", ["commuter", "laptop", "travel", "26L", "water resistant"], "./assets/backpacks/laptop-backpack.jpg", 26],
    ["Studio Camera Pack 28L", "Tags: camera, travel, laptop, 28L", "$168.00", ["camera", "travel", "laptop", "28L"], "./assets/backpacks/camera-backpack.jpg", 28],
    ["Travel Backpack 30L", "Tags: travel, 30L, carry-on, water resistant", "$129.00", ["travel", "30L", "carry-on", "water resistant"], "./assets/backpacks/travel-backpack-30l.jpg", 30],
    ["Carry-on Travel Pack 30L", "Tags: travel, carry-on, 30L", "$139.00", ["travel", "carry-on", "30L"], "./assets/backpacks/travel-backpack-30l.jpg", 30],
    ["Weekend Hiking Pack 30L", "Tags: hiking, 30L, water resistant", "$119.00", ["hiking", "30L", "water resistant"], "./assets/backpacks/trail-daypack.jpg", 30],
    ["Weekender Camera Pack 30L", "Tags: camera, travel, carry-on, 30L", "$172.00", ["camera", "travel", "carry-on", "30L"], "./assets/backpacks/camera-backpack.jpg", 30],
    ["Trail Overnight Pack 35L", "Tags: hiking, travel, 35L, water resistant", "$148.00", ["hiking", "travel", "35L", "water resistant"], "./assets/backpacks/trail-daypack.jpg", 35],
    ["Expedition Carry Pack 40L", "Tags: hiking, travel, 40L, water resistant", "$186.00", ["hiking", "travel", "40L", "water resistant"], "./assets/backpacks/travel-backpack-30l.jpg", 40],
    ["Family Weekender 45L", "Tags: travel, carry-on, 45L", "$198.00", ["travel", "carry-on", "45L"], "./assets/backpacks/travel-backpack-30l.jpg", 45],
    ["How to Choose the Right Backpack", "Tags: guide, travel, everyday", "Guide", ["guide", "travel", "everyday"], "./assets/backpacks/trail-daypack.jpg", 24]
  ];

  const header = document.querySelector("[data-header]");
  const menuButton = document.querySelector(".menu-button");
  const progress = document.querySelector(".scroll-progress");
  const queryInput = document.querySelector("#demo-query");
  const clearButton = document.querySelector("[data-clear-query]");
  const chipZone = document.querySelector("#chip-zone");
  const resultStream = document.querySelector("#result-stream");
  const resultCount = document.querySelector("#result-count");
  const activeFilterCount = document.querySelector("#active-filter-count");
  const suggestionCount = document.querySelector("#suggestion-count");
  const suggestionLabel = document.querySelector("#suggestion-label");
  const volumeMinInput = document.querySelector("#volume-min");
  const volumeMaxInput = document.querySelector("#volume-max");
  const volumeMinLabel = document.querySelector("#volume-min-label");
  const volumeMaxLabel = document.querySelector("#volume-max-label");
  const volumeRangeChip = document.querySelector("#volume-range-chip");
  const diagnosticsWorkspace = document.querySelector(".diagnostics-workspace");
  const dashboardMockup = document.querySelector(".plugin-window--concept");
  const checkoutButtons = document.querySelectorAll("[data-sschp-checkout]");
  const cookieConsent = document.querySelector("[data-cookie-consent]");
  const cookieAcceptButton = document.querySelector("[data-cookie-accept]");
  const cookieRejectButton = document.querySelector("[data-cookie-reject]");
  const cookieSettingsButton = document.querySelector("[data-cookie-settings]");
  const googleAdsTagId = "AW-18214815432";
  const cookieConsentKey = "sschp_cookie_consent_v1";
  const checkoutConfig = {
    productId: "27046",
    planId: "45154",
    publicKey: "pk_69b2a1db30add56864a42e4ea9929",
    productName: "Smart Search Chips"
  };

  let activeChips = [];
  let activeVolumeMin = volumeBounds.min;
  let activeVolumeMax = volumeBounds.max;
  let visibleVolumeBounds = { min: volumeBounds.min, max: volumeBounds.max };

  function normalizeTag(value) {
    return String(value || "").trim().toLowerCase();
  }

  function tokenizeQuery(value) {
    const normalized = normalizeTag(value);
    if (!normalized) {
      return [];
    }

    return normalized
      .split(/\s+/)
      .map((token) => token.trim())
      .filter((token) => token && !searchStopWords.has(token));
  }

  function resultText(result) {
    return `${result[0]} ${result[1]} ${(result[3] || []).join(" ")}`.toLowerCase();
  }

  function searchResults(tokens) {
    if (!tokens.length) {
      return queryInput.value.trim() ? demoResults : [];
    }

    return demoResults.filter((result) => {
      const haystack = resultText(result);
      return tokens.every((token) => haystack.includes(token));
    });
  }

  function tagCount(results, label) {
    const key = normalizeTag(label);
    return results.filter((result) => (result[3] || []).map(normalizeTag).includes(key)).length;
  }

  function isVolumeTag(label) {
    return /^\d+\s*l$/i.test(String(label || "").trim());
  }

  function isRedundantChip(label, tokens) {
    const key = normalizeTag(label);
    const queryKey = normalizeTag(tokens.join(" "));
    return key === queryKey || tokens.includes(key);
  }

  function buildChipSuggestions(results, tokens) {
    const counts = new Map();
    results.forEach((result) => {
      (result[3] || []).forEach((tag) => {
        const key = normalizeTag(tag);
        if (!key || hiddenSuggestionTags.has(key) || isVolumeTag(tag) || isRedundantChip(tag, tokens)) {
          return;
        }

        counts.set(key, {
          label: tag,
          count: (counts.get(key)?.count || 0) + 1
        });
      });
    });

    return Array.from(counts.values())
      .sort((a, b) => {
        const aIndex = chipOrder.indexOf(a.label);
        const bIndex = chipOrder.indexOf(b.label);
        const safeA = aIndex === -1 ? chipOrder.length : aIndex;
        const safeB = bIndex === -1 ? chipOrder.length : bIndex;
        return safeA - safeB || b.count - a.count || a.label.localeCompare(b.label);
      })
      .slice(0, 11)
      .map((item) => item.label);
  }

  function animateNumber(element, target) {
    const start = Number(element.textContent.replace(/,/g, "")) || 0;
    const duration = 320;
    const started = performance.now();

    function tick(now) {
      const portion = Math.min(1, (now - started) / duration);
      const eased = 1 - Math.pow(1 - portion, 3);
      element.textContent = Math.round(start + (target - start) * eased).toLocaleString();
      if (portion < 1) {
        requestAnimationFrame(tick);
      }
    }

    requestAnimationFrame(tick);
  }

  function volumeOf(result) {
    return Number(result[5]) || 0;
  }

  function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
  }

  function volumeBoundsForResults(results) {
    const volumes = results
      .map(volumeOf)
      .filter((volume) => Number.isFinite(volume) && volume > 0);

    if (!volumes.length) {
      return { min: volumeBounds.min, max: volumeBounds.max };
    }

    return {
      min: Math.min(...volumes),
      max: Math.max(...volumes)
    };
  }

  function syncVolumeBounds(results, preserveActiveSelection) {
    const nextBounds = volumeBoundsForResults(results);

    if (preserveActiveSelection) {
      activeVolumeMin = clamp(activeVolumeMin, nextBounds.min, nextBounds.max);
      activeVolumeMax = clamp(activeVolumeMax, nextBounds.min, nextBounds.max);
    } else {
      activeVolumeMin = nextBounds.min;
      activeVolumeMax = nextBounds.max;
    }

    if (activeVolumeMin > activeVolumeMax) {
      activeVolumeMin = nextBounds.min;
      activeVolumeMax = nextBounds.max;
    }

    visibleVolumeBounds = nextBounds;
  }

  function volumeRangeIsActive() {
    return activeVolumeMin > visibleVolumeBounds.min || activeVolumeMax < visibleVolumeBounds.max;
  }

  function updateVolumeRangeUi() {
    if (!volumeMinInput || !volumeMaxInput || !volumeRangeChip) {
      return;
    }

    if (activeVolumeMin > activeVolumeMax) {
      const midpoint = Math.round((activeVolumeMin + activeVolumeMax) / 2);
      activeVolumeMin = Math.min(midpoint, activeVolumeMax);
      activeVolumeMax = Math.max(midpoint, activeVolumeMin);
    }

    volumeMinInput.min = String(visibleVolumeBounds.min);
    volumeMinInput.max = String(visibleVolumeBounds.max);
    volumeMaxInput.min = String(visibleVolumeBounds.min);
    volumeMaxInput.max = String(visibleVolumeBounds.max);
    volumeMinInput.value = String(activeVolumeMin);
    volumeMaxInput.value = String(activeVolumeMax);
    volumeMinLabel.textContent = String(activeVolumeMin);
    volumeMaxLabel.textContent = String(activeVolumeMax);

    const rangeIsActive = volumeRangeIsActive();
    volumeRangeChip.classList.toggle("ssc-range-chip--active", rangeIsActive);
    volumeRangeChip.dataset.selected = rangeIsActive ? "true" : "false";
    volumeRangeChip.setAttribute("data-range-bounds-min", String(visibleVolumeBounds.min));
    volumeRangeChip.setAttribute("data-range-bounds-max", String(visibleVolumeBounds.max));
    volumeRangeChip.setAttribute("data-range-value", `range|volume|l|${activeVolumeMin}|${activeVolumeMax}|${visibleVolumeBounds.min}|${visibleVolumeBounds.max}`);
  }

  function filterByVolume(results) {
    return results.filter((result) => {
      const volume = volumeOf(result);
      return volume >= activeVolumeMin && volume <= activeVolumeMax;
    });
  }

  function renderDemo(rawQuery) {
    const tokens = tokenizeQuery(rawQuery);
    const baseResults = searchResults(tokens);
    const activeKeys = activeChips.map(normalizeTag);
    const chipFilteredResults = activeKeys.length
      ? baseResults.filter((result) => {
        const tags = (result[3] || []).map(normalizeTag);
        return activeKeys.every((key) => tags.includes(key));
      })
      : baseResults;
    const rangeWasActive = volumeRangeIsActive();

    syncVolumeBounds(chipFilteredResults, rangeWasActive);

    const filteredResults = filterByVolume(chipFilteredResults);
    const suggestions = buildChipSuggestions(filteredResults, tokens);
    const activeFilterTotal = activeChips.length + (volumeRangeIsActive() ? 1 : 0);

    updateVolumeRangeUi();
    animateNumber(resultCount, filteredResults.length);
    activeFilterCount.textContent = String(activeFilterTotal);
    activeFilterCount.parentElement.lastChild.textContent = activeFilterTotal === 1 ? " filter selected" : " filters selected";
    suggestionCount.textContent = String(suggestions.length);
    suggestionLabel.textContent = suggestions.length === 1 ? "suggested chip" : "suggested chips";

    chipZone.querySelectorAll(".chip").forEach((chip) => chip.remove());
    suggestions.forEach((label, index) => {
      const count = tagCount(filteredResults, label);
      const key = normalizeTag(label);
      const isActive = activeKeys.includes(key);
      const chip = document.createElement("button");
      chip.type = "button";
      chip.className = "chip" + (isActive ? " is-active" : "");
      chip.style.animationDelay = `${index * 42}ms`;
      chip.setAttribute("aria-pressed", isActive ? "true" : "false");
      chip.innerHTML = `<span>${label}</span><small>${count}</small>`;
      chip.addEventListener("click", () => {
        activeChips = activeChips.some((active) => normalizeTag(active) === key)
          ? activeChips.filter((active) => normalizeTag(active) !== key)
          : activeChips.concat(label);
        renderDemo(queryInput.value);
      });
      chipZone.appendChild(chip);
    });

    resultStream.replaceChildren();
    if (!filteredResults.length) {
      const empty = document.createElement("article");
      empty.className = "result-row result-row--empty";
      empty.innerHTML = `
        <div class="result-thumb" aria-hidden="true"></div>
        <div>
          <div class="result-title-line">
            <strong>No preview results</strong>
          </div>
          <p>Try backpack, travel, laptop, commuter, camera, hiking, or widen the volume range.</p>
        </div>
      `;
      resultStream.appendChild(empty);
      return;
    }

    filteredResults.forEach(([title, detail, price, tags, image], index) => {
      const row = document.createElement("article");
      row.className = "result-row" + (image ? " has-image" : "");
      row.style.animationDelay = `${index * 58}ms`;
      row.innerHTML = `
        <div class="result-thumb${image ? " has-image" : ""}" aria-hidden="true">
          ${image ? `<img src="${image}" alt="" loading="lazy">` : ""}
        </div>
        <div>
          <div class="result-title-line">
            <strong>${title}</strong>
          </div>
          <p>${detail}</p>
        </div>
        <span class="result-price">${price}</span>
      `;
      resultStream.appendChild(row);
    });
  }

  function setupReveal() {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15, rootMargin: "0px 0px -40px" });

    document.querySelectorAll(".reveal").forEach((element) => observer.observe(element));
  }

  function setupCheckout() {
    if (!checkoutButtons.length) {
      return;
    }

    let checkoutHandler = null;

    checkoutButtons.forEach((button) => {
      button.addEventListener("click", (event) => {
        if (!window.FS || typeof window.FS.Checkout !== "function") {
          return;
        }

        event.preventDefault();

        if (!checkoutHandler) {
          checkoutHandler = new window.FS.Checkout({
            product_id: checkoutConfig.productId,
            plan_id: checkoutConfig.planId,
            public_key: checkoutConfig.publicKey,
            image: "https://smartsearchchips.app/icon.png"
          });
        }

        checkoutHandler.open({
          name: checkoutConfig.productName,
          licenses: Number(button.dataset.licenses) || 1
        });
      });
    });
  }

  function readCookieConsent() {
    try {
      return window.localStorage.getItem(cookieConsentKey);
    } catch (error) {
      return null;
    }
  }

  function storeCookieConsent(value) {
    try {
      window.localStorage.setItem(cookieConsentKey, value);
    } catch (error) {
      // If storage is unavailable, honor the current click for this page view.
    }
  }

  function loadGoogleAdsTag() {
    window[`ga-disable-${googleAdsTagId}`] = false;
    window.__sschpGoogleAdsLoaded = true;
    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function gtag() {
      window.dataLayer.push(arguments);
    };

    updateGoogleConsent(true);
  }

  function updateGoogleConsent(granted) {
    if (typeof window.gtag !== "function") {
      return;
    }

    const value = granted ? "granted" : "denied";
    window.gtag("consent", "update", {
      ad_storage: value,
      ad_user_data: value,
      ad_personalization: value,
      analytics_storage: value
    });
  }

  function hideCookieConsent() {
    if (cookieConsent) {
      cookieConsent.hidden = true;
    }
  }

  function showCookieConsent() {
    if (cookieConsent) {
      cookieConsent.hidden = false;
    }
  }

  function setupCookieConsent() {
    if (!cookieConsent || !cookieAcceptButton || !cookieRejectButton) {
      return;
    }

    cookieAcceptButton.addEventListener("click", () => {
      storeCookieConsent("accepted");
      hideCookieConsent();
      loadGoogleAdsTag();
    });

    cookieRejectButton.addEventListener("click", () => {
      storeCookieConsent("rejected");
      window[`ga-disable-${googleAdsTagId}`] = true;
      updateGoogleConsent(false);
      hideCookieConsent();
    });

    if (cookieSettingsButton) {
      cookieSettingsButton.addEventListener("click", showCookieConsent);
    }

    const existingConsent = readCookieConsent();

    if (existingConsent === "accepted") {
      hideCookieConsent();
      loadGoogleAdsTag();
      return;
    }

    if (existingConsent === "rejected") {
      hideCookieConsent();
      return;
    }

    showCookieConsent();
  }

  function updateChrome() {
    const max = document.documentElement.scrollHeight - window.innerHeight;
    const progressWidth = max > 0 ? (window.scrollY / max) * 100 : 0;
    progress.style.width = `${progressWidth}%`;
    header.classList.toggle("is-scrolled", window.scrollY > 20);
  }

  function syncDashboardMockupScale() {
    if (!diagnosticsWorkspace || !dashboardMockup) {
      return;
    }

    if (window.matchMedia("(min-width: 901px)").matches) {
      diagnosticsWorkspace.style.removeProperty("--dashboard-scale");
      diagnosticsWorkspace.style.removeProperty("--dashboard-height");
      return;
    }

    const workspaceWidth = diagnosticsWorkspace.getBoundingClientRect().width;
    const baseWidth = dashboardMockup.offsetWidth
      || Number(dashboardMockup.dataset.dashboardWidth)
      || dashboardMockup.scrollWidth
      || 1080;
    const scale = Math.min(1, workspaceWidth / baseWidth);
    const height = Math.ceil(dashboardMockup.scrollHeight * scale);

    diagnosticsWorkspace.style.setProperty("--dashboard-scale", scale.toFixed(4));
    diagnosticsWorkspace.style.setProperty("--dashboard-height", `${height}px`);
  }

  function handleResize() {
    updateChrome();
    syncDashboardMockupScale();
  }

  queryInput.addEventListener("input", () => {
    activeChips = [];
    renderDemo(queryInput.value);
  });

  clearButton.addEventListener("click", () => {
    queryInput.value = "";
    queryInput.focus();
    activeChips = [];
    activeVolumeMin = volumeBounds.min;
    activeVolumeMax = volumeBounds.max;
    renderDemo("");
  });

  [volumeMinInput, volumeMaxInput].forEach((input) => {
    if (!input) {
      return;
    }

    input.addEventListener("input", () => {
      activeVolumeMin = Math.min(Number(volumeMinInput.value), Number(volumeMaxInput.value));
      activeVolumeMax = Math.max(Number(volumeMinInput.value), Number(volumeMaxInput.value));
      renderDemo(queryInput.value);
    });
  });

  menuButton.addEventListener("click", () => {
    const isOpen = header.classList.toggle("is-open");
    menuButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
  });

  document.querySelectorAll(".site-nav a").forEach((link) => {
    link.addEventListener("click", () => {
      header.classList.remove("is-open");
      menuButton.setAttribute("aria-expanded", "false");
    });
  });

  window.addEventListener("scroll", updateChrome, { passive: true });
  window.addEventListener("resize", handleResize);
  window.addEventListener("load", syncDashboardMockupScale);

  renderDemo(queryInput.value);
  setupReveal();
  setupCheckout();
  setupCookieConsent();
  updateChrome();
  syncDashboardMockupScale();
})();
