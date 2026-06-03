(function () {
  "use strict";

  var DURATION = 24000;
  var query = "backpack";
  var bounds = { min: 12, max: 45 };
  var products = [
    { title: "City Mini Backpack 12L", meta: "Tags: everyday, commuter, 12L", price: "$46.00", tags: ["everyday", "commuter", "12L"], image: "./launch-site/assets/backpacks/everyday-backpack.jpg", volume: 12 },
    { title: "Compact Camera Sling 14L", meta: "Tags: camera, everyday, 14L, water resistant", price: "$84.00", tags: ["camera", "everyday", "14L", "water resistant"], image: "./launch-site/assets/backpacks/camera-backpack.jpg", volume: 14 },
    { title: "Student Laptop Pack 16L", meta: "Tags: laptop, everyday, 16L", price: "$69.00", tags: ["laptop", "everyday", "16L"], image: "./launch-site/assets/backpacks/student-navy-backpack.jpg", volume: 16 },
    { title: "Office Commuter Backpack 18L", meta: "Tags: commuter, laptop, 18L, water resistant", price: "$92.00", tags: ["commuter", "laptop", "18L", "water resistant"], image: "./launch-site/assets/backpacks/rolltop-commuter-pack.jpg", volume: 18 },
    { title: "Everyday Backpack 20L", meta: "Tags: everyday, 20L, water resistant", price: "$79.00", tags: ["everyday", "20L", "water resistant"], image: "./launch-site/assets/backpacks/everyday-backpack.jpg", volume: 20 },
    { title: "Trail Daypack 20L", meta: "Tags: hiking, 20L, water resistant", price: "$74.00", tags: ["hiking", "20L", "water resistant"], image: "./launch-site/assets/backpacks/trail-daypack.jpg", volume: 20 },
    { title: "Packable Daypack 20L", meta: "Tags: packable, travel, carry-on, 20L", price: "$49.00", tags: ["packable", "travel", "carry-on", "20L"], image: "./launch-site/assets/backpacks/packable-daypack.jpg", volume: 20 },
    { title: "Packable Rain Daypack 20L", meta: "Tags: packable, hiking, water resistant, 20L", price: "$59.00", tags: ["packable", "hiking", "water resistant", "20L"], image: "./launch-site/assets/backpacks/packable-daypack.jpg", volume: 20 },
    { title: "Photo Daypack 22L", meta: "Tags: camera, travel, laptop, 22L, water resistant", price: "$159.00", tags: ["camera", "travel", "laptop", "22L", "water resistant"], image: "./launch-site/assets/backpacks/camera-backpack.jpg", volume: 22 },
    { title: "Metro Rolltop 24L", meta: "Tags: commuter, laptop, 24L, water resistant", price: "$118.00", tags: ["commuter", "laptop", "24L", "water resistant"], image: "./launch-site/assets/backpacks/rolltop-commuter-pack.jpg", volume: 24 },
    { title: "Modular Tech Backpack 26L", meta: "Tags: commuter, laptop, travel, 26L, water resistant", price: "$134.00", tags: ["commuter", "laptop", "travel", "26L", "water resistant"], image: "./launch-site/assets/backpacks/laptop-backpack.jpg", volume: 26 },
    { title: "Studio Camera Pack 28L", meta: "Tags: camera, travel, laptop, 28L", price: "$168.00", tags: ["camera", "travel", "laptop", "28L"], image: "./launch-site/assets/backpacks/camera-backpack.jpg", volume: 28 },
    { title: "Travel Backpack 30L", meta: "Tags: travel, 30L, carry-on, water resistant", price: "$129.00", tags: ["travel", "30L", "carry-on", "water resistant"], image: "./launch-site/assets/backpacks/travel-backpack-30l.jpg", volume: 30 },
    { title: "Carry-on Travel Pack 30L", meta: "Tags: travel, carry-on, 30L", price: "$139.00", tags: ["travel", "carry-on", "30L"], image: "./launch-site/assets/backpacks/travel-backpack-30l.jpg", volume: 30 },
    { title: "Weekend Hiking Pack 30L", meta: "Tags: hiking, 30L, water resistant", price: "$119.00", tags: ["hiking", "30L", "water resistant"], image: "./launch-site/assets/backpacks/trail-daypack.jpg", volume: 30 },
    { title: "Weekender Camera Pack 30L", meta: "Tags: camera, travel, carry-on, 30L", price: "$172.00", tags: ["camera", "travel", "carry-on", "30L"], image: "./launch-site/assets/backpacks/camera-backpack.jpg", volume: 30 },
    { title: "Trail Overnight Pack 35L", meta: "Tags: hiking, travel, 35L, water resistant", price: "$148.00", tags: ["hiking", "travel", "35L", "water resistant"], image: "./launch-site/assets/backpacks/trail-daypack.jpg", volume: 35 },
    { title: "Expedition Carry Pack 40L", meta: "Tags: hiking, travel, 40L, water resistant", price: "$186.00", tags: ["hiking", "travel", "40L", "water resistant"], image: "./launch-site/assets/backpacks/travel-backpack-30l.jpg", volume: 40 },
    { title: "Family Weekender 45L", meta: "Tags: travel, carry-on, 45L", price: "$198.00", tags: ["travel", "carry-on", "45L"], image: "./launch-site/assets/backpacks/travel-backpack-30l.jpg", volume: 45 },
    { title: "How to Choose the Right Backpack", meta: "Tags: guide, travel, everyday", price: "Guide", tags: ["guide", "travel", "everyday"], image: "./launch-site/assets/backpacks/trail-daypack.jpg", volume: 24 }
  ];

  var captions = {
    open: ["WordPress search, guided", "Make search feel like filtering, not guessing.", "Smart Search Chips adds a reliable search UI, catalog chips, numeric ranges, diagnostics, analytics, and Pro workflows."],
    search: ["Guaranteed search UI", "Type once. Useful chips appear from the result set.", "Use the shortcode or block anywhere. Compatible theme AJAX can be enhanced safely."],
    range: ["Pro range chips", "Numeric catalog values become compact slider chips.", "Volume, wattage, dimensions, weight, and mapped fields can become guided range filters."],
    diagnostics: ["Integrated debugging", "Know which path ran and why chips appeared.", "Test Search explains local index, Relevanssi, WP_Query fallback, chips, tagged results, and warnings."],
    analytics: ["Local search analytics", "See what visitors search, refine, and click.", "Track chip CTR, result clicks, zero-result reports, exports, and scheduled email digests."],
    workflows: ["Pro workflows", "Curate chips and scale catalog tagging.", "Rules, field mapping, range diagnostics, analytics reports, AI prompts, and clean WP.org boundaries."],
    final: ["", "", ""]
  };

  var els = {
    body: document.body,
    progress: document.getElementById("timeline-progress"),
    overline: document.getElementById("caption-overline"),
    title: document.getElementById("caption-title"),
    bodyText: document.getElementById("caption-body"),
    typedQuery: document.getElementById("typed-query"),
    status: document.getElementById("status-pill"),
    rangeChip: document.getElementById("volume-range-chip"),
    rangeMinLabel: document.getElementById("range-min-label"),
    rangeMaxLabel: document.getElementById("range-max-label"),
    rangeFill: document.getElementById("range-fill"),
    rangeDotMin: document.getElementById("range-dot-min"),
    rangeDotMax: document.getElementById("range-dot-max"),
    resultCount: document.getElementById("result-count"),
    filterCount: document.getElementById("filter-count"),
    chipCount: document.getElementById("chip-count"),
    results: document.getElementById("result-list"),
    everyday: document.getElementById("chip-everyday"),
    travel: document.getElementById("chip-travel"),
    water: document.getElementById("chip-water"),
    commuter: document.getElementById("chip-commuter"),
    cursor: document.getElementById("motion-cursor"),
    cursorLabel: document.getElementById("motion-cursor-label")
  };

  var previousResultKey = "";
  var previousPhase = "";
  var startedAt = 0;
  var staticFrame = getStaticFrame();

  function getStaticFrame() {
    var params = new URLSearchParams(window.location.search);
    var frame = params.get("frame");
    if (frame === null) {
      return null;
    }

    var parsed = Number(frame);
    return Number.isFinite(parsed) ? Math.max(0, Math.min(DURATION, parsed)) : null;
  }

  function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
  }

  function easeOutCubic(value) {
    return 1 - Math.pow(1 - clamp(value, 0, 1), 3);
  }

  function lerp(from, to, amount) {
    return from + (to - from) * amount;
  }

  function phaseFor(ms) {
    if (ms >= 21200) {
      return "final";
    }
    if (ms >= 17600) {
      return "workflows";
    }
    if (ms >= 13600) {
      return "analytics";
    }
    if (ms >= 10200) {
      return "diagnostics";
    }
    if (ms >= 6200) {
      return "range";
    }
    if (ms >= 1100) {
      return "search";
    }
    return "open";
  }

  function typedQueryFor(ms) {
    if (ms < 1600) {
      return "";
    }

    var progress = clamp((ms - 1250) / 920, 0, 1);
    return query.slice(0, Math.round(progress * query.length));
  }

  function rangeMinFor(ms) {
    if (ms < 6200) {
      return bounds.min;
    }

    return Math.round(lerp(bounds.min, 20, easeOutCubic((ms - 6500) / 900)));
  }

  function visibleProducts(rangeMin) {
    if (rangeMin <= bounds.min) {
      return products;
    }

    return products.filter(function (product) {
      return product.volume >= rangeMin && product.volume <= bounds.max;
    });
  }

  function countTag(results, tag) {
    return results.filter(function (product) {
      return product.tags.indexOf(tag) !== -1;
    }).length;
  }

  function renderCaptions(phase) {
    var next = captions[phase] || captions.open;
    els.overline.textContent = next[0];
    els.title.textContent = next[1];
    els.bodyText.textContent = next[2];
  }

  function renderRange(rangeMin) {
    var active = rangeMin > bounds.min;
    var minPercent = ((rangeMin - bounds.min) / (bounds.max - bounds.min)) * 100;

    els.rangeMinLabel.textContent = String(rangeMin);
    els.rangeMaxLabel.textContent = String(bounds.max);
    els.rangeChip.classList.toggle("is-active", active);
    els.rangeChip.style.setProperty("--range-min", minPercent.toFixed(2) + "%");
    els.rangeChip.style.setProperty("--range-max", "100%");
    els.filterCount.textContent = active ? "1" : "0";
  }

  function renderCounts(results, active) {
    els.resultCount.textContent = String(results.length);
    els.chipCount.textContent = active ? "8" : "9";
    els.everyday.textContent = String(countTag(results, "everyday"));
    els.travel.textContent = String(countTag(results, "travel"));
    els.water.textContent = String(countTag(results, "water resistant"));
    els.commuter.textContent = String(countTag(results, "commuter"));
  }

  function renderResults(results) {
    var visible = results.slice(0, 6);
    var key = visible.map(function (product) { return product.title; }).join("|");

    if (key === previousResultKey) {
      return;
    }

    previousResultKey = key;
    els.results.innerHTML = visible.map(function (product, index) {
      return [
        '<article class="result-card" style="animation-delay:' + (index * 58) + 'ms">',
        '<img src="' + product.image + '" alt="">',
        '<div><h3>' + product.title + '</h3><p>' + product.meta + '</p></div>',
        '<strong>' + product.price + '</strong>',
        '</article>'
      ].join("");
    }).join("");
  }

  function statusFor(phase, typed, active) {
    if (!typed) {
      return "Waiting for query";
    }
    if (phase === "analytics") {
      return "Tracking locally";
    }
    if (phase === "workflows") {
      return "Pro toolkit";
    }
    if (phase === "diagnostics") {
      return "Debug path ready";
    }
    if (active) {
      return "1 filter selected";
    }
    return "9 chips generated";
  }

  function cursorFor(ms, phase) {
    if (phase === "open" || phase === "final") {
      return null;
    }

    if (phase === "search") {
      return { x: 230, y: 142, scale: 1, label: ms < 2500 ? "type query" : "chips generated" };
    }

    if (phase === "range") {
      return { x: 292, y: 238, scale: 1.02, label: "drag range" };
    }

    if (phase === "diagnostics") {
      return { x: 640, y: 142, scale: 0.98, label: "test search" };
    }

    if (phase === "analytics") {
      return { x: 858, y: 418, scale: 0.98, label: "read reports" };
    }

    if (phase === "workflows") {
      return { x: 862, y: 126, scale: 0.98, label: "curate rules" };
    }

    return null;
  }

  function renderCursor(ms, phase) {
    var cursor = cursorFor(ms, phase);

    if (!cursor) {
      els.cursor.classList.remove("is-visible");
      return;
    }

    els.cursor.style.setProperty("--cursor-x", cursor.x + "px");
    els.cursor.style.setProperty("--cursor-y", cursor.y + "px");
    els.cursor.style.setProperty("--cursor-scale", String(cursor.scale));
    els.cursorLabel.textContent = cursor.label;
    els.cursor.classList.add("is-visible");
  }

  function renderAt(ms) {
    var phase = phaseFor(ms);
    var typed = typedQueryFor(ms);
    var rangeMin = rangeMinFor(ms);
    var active = rangeMin > bounds.min;
    var results = visibleProducts(rangeMin);

    els.progress.style.width = (Math.min(100, (ms / DURATION) * 100)).toFixed(3) + "%";
    els.body.setAttribute("data-phase", phase);
    els.typedQuery.textContent = typed;
    els.status.textContent = statusFor(phase, typed, active);

    if (phase !== previousPhase) {
      previousPhase = phase;
      renderCaptions(phase);
    }

    renderRange(rangeMin);
    renderCounts(results, active);
    renderResults(results);
    renderCursor(ms, phase);

    if (ms >= DURATION) {
      window.sschpMotionDone = true;
    }
  }

  function tick(now) {
    var elapsed = now - startedAt;
    renderAt(elapsed);

    if (elapsed < DURATION) {
      window.requestAnimationFrame(tick);
    } else {
      window.sschpMotionDone = true;
    }
  }

  function preloadImages() {
    return Promise.all(products.map(function (product) {
      return new Promise(function (resolve) {
        var image = new Image();
        image.onload = resolve;
        image.onerror = resolve;
        image.src = product.image;
      });
    }));
  }

  preloadImages().then(function () {
    if (staticFrame !== null) {
      renderAt(staticFrame);
      window.sschpMotionDone = true;
      return;
    }

    startedAt = performance.now();
    renderAt(0);
    window.requestAnimationFrame(tick);
  });
})();
