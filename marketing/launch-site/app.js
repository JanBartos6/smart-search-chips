(function () {
  const demoData = {
    connector: {
      chips: [
        "M12 connector",
        "IP67",
        "sensor cable",
        "24 V DC",
        "DIN rail",
        "stainless",
        "pneumatic",
        "IO-Link",
        "panel mount"
      ],
      results: [
        ["Sensor Cable M12", "Tags: M12 connector, IP67, sensor cable", "$32.00", ["M12 connector", "IP67", "sensor cable"]],
        ["M12 Right-Angle Cable", "Tags: M12 connector, IP67, sensor cable", "$36.00", ["M12 connector", "IP67", "sensor cable"]],
        ["Valve Terminal VT-12", "Tags: M12 connector, pneumatic, DIN rail, 24 V DC", "$218.00", ["M12 connector", "pneumatic", "DIN rail", "24 V DC"]],
        ["DIN Rail Power Module", "Tags: 24 V DC, DIN rail", "$88.00", ["24 V DC", "DIN rail"]],
        ["IO-Link Master Module", "Tags: M12 connector, 24 V DC, IP67, IO-Link", "$249.00", ["M12 connector", "24 V DC", "IP67", "IO-Link"]],
        ["Stainless Field Junction Box", "Tags: M12 connector, IP67, stainless", "$176.00", ["M12 connector", "IP67", "stainless"]],
        ["Panel Mount M12 Socket", "Tags: M12 connector, panel mount, sensor cable", "$24.00", ["M12 connector", "panel mount", "sensor cable"]],
        ["Compact Pressure Sensor", "Tags: 24 V DC, IP67, sensor cable", "$96.00", ["24 V DC", "IP67", "sensor cable"]],
        ["DIN Rail Gateway", "Tags: DIN rail, 24 V DC, IO-Link", "$132.00", ["DIN rail", "24 V DC", "IO-Link"]],
        ["Washdown Connector Kit", "Tags: M12 connector, IP67, stainless", "$54.00", ["M12 connector", "IP67", "stainless"]],
        ["Pneumatic Manifold Cable Set", "Tags: M12 connector, pneumatic, sensor cable", "$64.00", ["M12 connector", "pneumatic", "sensor cable"]],
        ["Industrial Connector Guide", "Tags: guide, M12 connector, IP67", "Guide", ["guide", "M12 connector", "IP67"]],
        ["M12 Shielded Extension Lead", "Tags: M12 connector, sensor cable, IP67", "$42.00", ["M12 connector", "sensor cable", "IP67"]],
        ["Stainless Bulkhead Connector", "Tags: M12 connector, stainless, panel mount, IP67", "$58.00", ["M12 connector", "stainless", "panel mount", "IP67"]]
      ]
    },
    backpack: {
      chips: [
        "everyday",
        "travel",
        "water resistant",
        "laptop",
        "carry-on",
        "commuter",
        "camera",
        "packable",
        "hiking",
        "20L",
        "30L"
      ],
      results: [
        ["Everyday Backpack", "Tags: everyday, 20L, water resistant", "$79.00", ["everyday", "20L", "water resistant"], "./assets/backpacks/everyday-backpack.jpg"],
        ["Travel Backpack 30L", "Tags: travel, 30L, carry-on, water resistant", "$129.00", ["travel", "30L", "carry-on", "water resistant"], "./assets/backpacks/travel-backpack-30l.jpg"],
        ["Laptop Backpack", "Tags: laptop, everyday, water resistant", "$89.00", ["laptop", "everyday", "water resistant"], "./assets/backpacks/laptop-backpack.jpg"],
        ["Trail Daypack 20L", "Tags: hiking, 20L, water resistant", "$74.00", ["hiking", "20L", "water resistant"], "./assets/backpacks/trail-daypack.jpg"],
        ["Rolltop Commuter Pack", "Tags: everyday, commuter, laptop, water resistant, 20L", "$104.00", ["everyday", "commuter", "laptop", "water resistant", "20L"], "./assets/backpacks/rolltop-commuter-pack.jpg"],
        ["Carry-on Travel Pack", "Tags: travel, carry-on, 30L", "$139.00", ["travel", "carry-on", "30L"], "./assets/backpacks/travel-backpack-30l.jpg"],
        ["Camera Backpack", "Tags: camera, laptop, travel, water resistant", "$149.00", ["camera", "laptop", "travel", "water resistant"], "./assets/backpacks/camera-backpack.jpg"],
        ["Packable Daypack", "Tags: packable, travel, carry-on, 20L", "$49.00", ["packable", "travel", "carry-on", "20L"], "./assets/backpacks/packable-daypack.jpg"],
        ["Urban Sling Pack", "Tags: everyday, water resistant", "$54.00", ["everyday", "water resistant"], "./assets/backpacks/everyday-backpack.jpg"],
        ["Weekend Hiking Pack 30L", "Tags: hiking, 30L, water resistant", "$119.00", ["hiking", "30L", "water resistant"], "./assets/backpacks/trail-daypack.jpg"],
        ["Student Laptop Pack", "Tags: laptop, everyday", "$69.00", ["laptop", "everyday"], "./assets/backpacks/student-navy-backpack.jpg"],
        ["Metro Rolltop 24L", "Tags: commuter, laptop, water resistant", "$118.00", ["commuter", "laptop", "water resistant"], "./assets/backpacks/rolltop-commuter-pack.jpg"],
        ["Photo Daypack 22L", "Tags: camera, travel, laptop, water resistant", "$159.00", ["camera", "travel", "laptop", "water resistant"], "./assets/backpacks/camera-backpack.jpg"],
        ["Packable Rain Daypack", "Tags: packable, hiking, water resistant, 20L", "$59.00", ["packable", "hiking", "water resistant", "20L"], "./assets/backpacks/packable-daypack.jpg"],
        ["Campus Laptop Backpack", "Tags: everyday, laptop, 20L", "$72.00", ["everyday", "laptop", "20L"], "./assets/backpacks/student-navy-backpack.jpg"],
        ["Bike Commuter Pack", "Tags: commuter, everyday, water resistant, 20L", "$96.00", ["commuter", "everyday", "water resistant", "20L"], "./assets/backpacks/rolltop-commuter-pack.jpg"],
        ["Weekender Camera Pack 30L", "Tags: camera, travel, carry-on, 30L", "$172.00", ["camera", "travel", "carry-on", "30L"], "./assets/backpacks/camera-backpack.jpg"],
        ["How to Choose the Right Backpack", "Tags: guide, travel, everyday", "Guide", ["guide", "travel", "everyday"], "./assets/backpacks/trail-daypack.jpg"]
      ]
    },
    ceramic: {
      chips: [
        "stoneware",
        "serving",
        "neutral glaze",
        "handmade",
        "hosting",
        "porcelain"
      ],
      results: [
        ["Stoneware Tea Bowl", "Tags: stoneware, handmade, neutral glaze", "$34.00", ["stoneware", "handmade", "neutral glaze"]],
        ["Ceramic Serving Plate", "Tags: serving, neutral glaze, hosting", "$58.00", ["serving", "neutral glaze", "hosting"]],
        ["Porcelain Tasting Cup", "Tags: porcelain, handmade", "$22.00", ["porcelain", "handmade"]],
        ["Stoneware Pitcher", "Tags: stoneware, serving, hosting", "$64.00", ["stoneware", "serving", "hosting"]]
      ]
    },
    valve: {
      chips: [
        "pressure",
        "pneumatic",
        "24 V DC",
        "replacement coil",
        "brass",
        "IP67",
        "stainless",
        "fail-safe"
      ],
      results: [
        ["Safety Valve VS-24", "Tags: pressure, 24 V DC, IP67", "$118.00", ["pressure", "24 V DC", "IP67"]],
        ["Compact Pneumatic Valve", "Tags: pneumatic, pressure", "$146.00", ["pneumatic", "pressure"]],
        ["Replacement Coil Kit", "Tags: replacement coil, 24 V DC", "$42.00", ["replacement coil", "24 V DC"]],
        ["Brass Check Valve", "Tags: brass, pressure", "$74.00", ["brass", "pressure"]],
        ["Stainless Process Valve", "Tags: stainless, pressure, IP67", "$188.00", ["stainless", "pressure", "IP67"]],
        ["Fail-Safe Solenoid Valve", "Tags: fail-safe, pneumatic, 24 V DC", "$164.00", ["fail-safe", "pneumatic", "24 V DC"]],
        ["Pneumatic Valve Manifold", "Tags: pneumatic, replacement coil, 24 V DC", "$236.00", ["pneumatic", "replacement coil", "24 V DC"]],
        ["Brass Pressure Regulator", "Tags: brass, pressure, pneumatic", "$92.00", ["brass", "pressure", "pneumatic"]],
        ["IP67 Valve Connector", "Tags: IP67, 24 V DC, replacement coil", "$39.00", ["IP67", "24 V DC", "replacement coil"]],
        ["Valve Maintenance Guide", "Tags: guide, replacement coil, pressure", "Guide", ["guide", "replacement coil", "pressure"]]
      ]
    }
  };

  const queries = ["connector", "backpack", "valve", "ceramic"];
  const demoAliases = {
    m12: "connector",
    connector: "connector",
    connectors: "connector",
    cable: "connector",
    sensor: "connector",
    industrial: "connector",
    io: "connector",
    iolink: "connector",
    ventil: "valve",
    valve: "valve",
    valves: "valve",
    pneumatic: "valve",
    pressure: "valve",
    backpack: "backpack",
    backpacks: "backpack",
    travel: "backpack",
    laptop: "backpack",
    bag: "backpack",
    bags: "backpack",
    camera: "backpack",
    carry: "backpack",
    commuter: "backpack",
    hiking: "backpack",
    packable: "backpack",
    ceramic: "ceramic",
    ceramics: "ceramic",
    stoneware: "ceramic",
    tea: "ceramic",
    porcelain: "ceramic"
  };
  const header = document.querySelector("[data-header]");
  const menuButton = document.querySelector(".menu-button");
  const progress = document.querySelector(".scroll-progress");
  const queryInput = document.querySelector("#demo-query");
  const clearButton = document.querySelector("[data-clear-query]");
  const chipZone = document.querySelector("#chip-zone");
  const resultStream = document.querySelector("#result-stream");
  const resultCount = document.querySelector("#result-count");
  const activeFilterCount = document.querySelector("#active-filter-count");

  let activeChips = [];
  let currentQuery = "backpack";

  function resolveDemo(value) {
    const normalized = value.trim().toLowerCase();
    if (!normalized) {
      return {
        key: "empty",
        data: {
          chips: [],
          results: [],
          emptyMessage: "Enter a query to preview generated chips and matching results."
        }
      };
    }

    if (demoData[normalized]) {
      return { key: normalized, data: demoData[normalized] };
    }

    if (demoAliases[normalized]) {
      return { key: demoAliases[normalized], data: demoData[demoAliases[normalized]] };
    }

    const prefixMatch = queries.find((query) => query.startsWith(normalized) || normalized.startsWith(query.slice(0, 4)));
    if (prefixMatch) {
      return { key: prefixMatch, data: demoData[prefixMatch] };
    }

    return {
      key: "dynamic",
      data: buildDynamicDemo(normalized)
    };
  }

  function buildDynamicDemo(normalized) {
    const tokens = normalized.split(/\s+/).filter(Boolean);
    const matched = new Map();

    Object.values(demoData).forEach((data) => {
      data.results.forEach((result) => {
        const haystack = `${result[0]} ${result[1]} ${(result[3] || []).join(" ")}`.toLowerCase();
        const matches = tokens.every((token) => haystack.includes(token));
        if (matches && !matched.has(result[0])) {
          matched.set(result[0], result);
        }
      });
    });

    const results = Array.from(matched.values());
    const tagCounts = new Map();
    results.forEach((result) => {
      (result[3] || []).forEach((tag) => {
        const key = normalizeTag(tag);
        if (!key) {
          return;
        }
        tagCounts.set(key, {
          label: tag,
          count: (tagCounts.get(key)?.count || 0) + 1
        });
      });
    });

    const chips = Array.from(tagCounts.values())
      .filter((tag) => tag.count > 0 && tag.count < results.length)
      .sort((a, b) => b.count - a.count || a.label.localeCompare(b.label))
      .slice(0, 10)
      .map((tag) => tag.label);

    return {
      chips,
      results,
      emptyMessage: "No preview results match this sample dataset. Try connector, valve, backpack, or ceramic."
    };
  }

  function chipCount(data, label) {
    const key = normalizeTag(label);
    return data.results.filter((result) => {
      const tags = (result[3] || []).map(normalizeTag);
      return tags.includes(key);
    }).length;
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

  function renderDemo(rawQuery) {
    const resolved = resolveDemo(rawQuery);
    currentQuery = resolved.key;
    const data = resolved.data;
    const activeKeys = activeChips.map(normalizeTag);
    const filtered = activeKeys.length
      ? data.results.filter((result) => {
        const tags = (result[3] || []).map(normalizeTag);
        return activeKeys.every((key) => tags.includes(key));
      })
      : data.results;
    const results = filtered;
    const shownCount = results.length;

    animateNumber(resultCount, shownCount);
    activeFilterCount.textContent = String(activeChips.length);
    activeFilterCount.parentElement.lastChild.textContent = activeChips.length === 1 ? " chip selected" : " chips selected";

    chipZone.replaceChildren();
    data.chips.forEach((label, index) => {
      const count = chipCount(data, label);
      const chip = document.createElement("button");
      chip.type = "button";
      const isActive = activeKeys.includes(normalizeTag(label));
      chip.className = "chip" + (isActive ? " is-active" : "");
      chip.style.animationDelay = `${index * 42}ms`;
      chip.setAttribute("aria-pressed", isActive ? "true" : "false");
      chip.innerHTML = `<span>${label}</span><small>${count}</small>`;
      chip.addEventListener("click", () => {
        const key = normalizeTag(label);
        activeChips = activeChips.some((active) => normalizeTag(active) === key)
          ? activeChips.filter((active) => normalizeTag(active) !== key)
          : activeChips.concat(label);
        renderDemo(currentQuery);
      });
      chipZone.appendChild(chip);
    });

    resultStream.replaceChildren();
    if (!results.length) {
      const empty = document.createElement("article");
      empty.className = "result-row result-row--empty";
      empty.innerHTML = `
        <div class="result-thumb" aria-hidden="true"></div>
        <div>
          <strong>No preview results for this chip combination</strong>
          <p>${data.emptyMessage || "Remove one chip to broaden this search."}</p>
        </div>
      `;
      resultStream.appendChild(empty);
      return;
    }

    results.forEach(([title, detail, price, tags, image], index) => {
      const row = document.createElement("article");
      row.className = "result-row" + (image ? " has-image" : "");
      row.style.animationDelay = `${index * 58}ms`;
      row.innerHTML = `
        <div class="result-thumb${image ? " has-image" : ""}" aria-hidden="true">
          ${image ? `<img src="${image}" alt="" loading="lazy">` : ""}
        </div>
        <div>
          <strong>${title}</strong>
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

  function setupTabs() {
    const tabs = Array.from(document.querySelectorAll(".pro-tab"));
    const panels = Array.from(document.querySelectorAll(".pro-panel"));

    tabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        const id = tab.dataset.panel;
        tabs.forEach((item) => {
          const isSelected = item === tab;
          item.classList.toggle("is-active", isSelected);
          item.setAttribute("aria-selected", isSelected ? "true" : "false");
        });
        panels.forEach((panel) => {
          panel.classList.toggle("is-active", panel.id === `panel-${id}`);
        });
      });
    });
  }

  function updateChrome() {
    const max = document.documentElement.scrollHeight - window.innerHeight;
    const progressWidth = max > 0 ? (window.scrollY / max) * 100 : 0;
    progress.style.width = `${progressWidth}%`;
    header.classList.toggle("is-scrolled", window.scrollY > 20);
  }

  queryInput.addEventListener("input", () => {
    activeChips = [];
    renderDemo(queryInput.value);
  });

  clearButton.addEventListener("click", () => {
    queryInput.value = "";
    queryInput.focus();
    activeChips = [];
    renderDemo("");
  });

  function normalizeTag(value) {
    return String(value || "").trim().toLowerCase();
  }

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
  window.addEventListener("resize", updateChrome);

  renderDemo(queryInput.value);
  setupReveal();
  setupTabs();
  updateChrome();
})();
