(function () {
  "use strict";

  var DURATION = 11200;
  var query = "backpack";
  var rangeBounds = { min: 12, max: 45, selectedMin: 20 };
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
    open: ["Smart search chips", "Type once. Keep narrowing.", "A normal WordPress search becomes a set of useful catalog filters."],
    type: ["Guaranteed search UI", "Search without theme guesswork.", "The block or shortcode uses the plugin endpoint, so the search box is reliable."],
    chips: ["Generated chips", "Useful filters appear from the result set.", "Visitors can use the language already present in your products."],
    range: ["Range chip", "Use numeric values like any other chip.", "The cursor moves first, then the range narrows the result set."],
    filter: ["Quick filtering", "One click narrows the catalog.", "The results update immediately when a visitor chooses a chip."],
    refine: ["Fast refinement", "Combine chips naturally.", "Travel plus water resistant becomes a focused list in seconds."],
    final: ["", "", ""]
  };

  var chipDefs = [
    { key: "everyday", tag: "everyday", id: "chip-everyday" },
    { key: "travel", tag: "travel", id: "chip-travel" },
    { key: "water", tag: "water resistant", id: "chip-water" },
    { key: "commuter", tag: "commuter", id: "chip-commuter" },
    { key: "laptop", tag: "laptop", id: "chip-laptop" },
    { key: "camera", tag: "camera", id: "chip-camera" }
  ];

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
    resultCount: document.getElementById("result-count"),
    filterCount: document.getElementById("filter-count"),
    chipCount: document.getElementById("chip-count"),
    results: document.getElementById("result-list"),
    cursor: document.getElementById("motion-cursor"),
    cursorLabel: document.getElementById("motion-cursor-label")
  };

  chipDefs.forEach(function (chip) {
    chip.el = document.getElementById(chip.id);
    chip.countEl = chip.el.querySelector("small");
  });

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

  function phaseFor(ms) {
    if (ms >= 9800) {
      return "final";
    }
    if (ms >= 7400) {
      return "refine";
    }
    if (ms >= 5400) {
      return "filter";
    }
    if (ms >= 3100) {
      return "range";
    }
    if (ms >= 2000) {
      return "chips";
    }
    if (ms >= 620) {
      return "type";
    }
    return "open";
  }

  function typedQueryFor(ms) {
    if (ms < 620) {
      return "";
    }

    var progress = clamp((ms - 760) / 760, 0, 1);
    return query.slice(0, Math.round(progress * query.length));
  }

  function activeTagsFor(ms) {
    if (ms >= 8400 && ms < 9800) {
      return ["travel", "water resistant"];
    }
    if (ms >= 6400 && ms < 9800) {
      return ["travel"];
    }
    return [];
  }

  function isRangeActive(ms) {
    return ms >= 4100 && ms < 9800;
  }

  function filterProducts(activeTags, typed, rangeActive) {
    if (typed.length < query.length) {
      return [];
    }

    return products.filter(function (product) {
      if (rangeActive && product.volume < rangeBounds.selectedMin) {
        return false;
      }

      return activeTags.every(function (tag) {
        return product.tags.indexOf(tag) !== -1;
      });
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

  function renderRange(rangeActive) {
    var minValue = rangeActive ? rangeBounds.selectedMin : rangeBounds.min;
    var minPercent = ((minValue - rangeBounds.min) / (rangeBounds.max - rangeBounds.min)) * 100;

    els.rangeMinLabel.textContent = String(minValue);
    els.rangeChip.classList.toggle("is-active", rangeActive);
    els.rangeChip.style.setProperty("--range-min", minPercent.toFixed(2) + "%");
    els.rangeChip.style.setProperty("--range-max", "100%");
  }

  function renderChips(results, activeTags) {
    chipDefs.forEach(function (chip) {
      var active = activeTags.indexOf(chip.tag) !== -1;
      chip.el.classList.toggle("is-active", active);
      chip.countEl.textContent = String(countTag(results.length ? results : products, chip.tag));
    });
  }

  function renderCounts(results, activeTags, typed, rangeActive) {
    els.resultCount.textContent = typed.length < query.length ? "0" : String(results.length);
    els.filterCount.textContent = String(activeTags.length + (rangeActive ? 1 : 0));
    els.chipCount.textContent = typed.length < query.length ? "0" : String(chipDefs.length + 1);
  }

  function renderResults(results) {
    var visible = results.slice(0, 6);
    var key = visible.map(function (product) { return product.title; }).join("|");

    if (key === previousResultKey) {
      return;
    }

    previousResultKey = key;

    if (!visible.length) {
      els.results.innerHTML = '<div class="result-placeholder">Start typing to generate search chips.</div>';
      return;
    }

    els.results.innerHTML = visible.map(function (product, index) {
      return [
        '<article class="result-card" style="animation-delay:' + (index * 52) + 'ms">',
        '<img src="' + product.image + '" alt="">',
        '<div><h3>' + product.title + '</h3><p>' + product.meta + '</p></div>',
        '<strong>' + product.price + '</strong>',
        '</article>'
      ].join("");
    }).join("");
  }

  function statusFor(typed, activeTags, rangeActive) {
    var activeFilterCount = activeTags.length + (rangeActive ? 1 : 0);

    if (typed.length < query.length) {
      return typed ? "Searching" : "Waiting for query";
    }

    if (activeFilterCount) {
      return activeFilterCount + (activeFilterCount === 1 ? " filter selected" : " filters selected");
    }

    return (chipDefs.length + 1) + " chips generated";
  }

  function cursorFor(ms, phase) {
    if (phase === "open" || phase === "final") {
      return null;
    }

    if (phase === "type") {
      return { x: 238, y: 132, scale: 1, label: "type backpack" };
    }

    if (phase === "chips") {
      return { x: 252, y: 221, scale: 1, label: "range + chips" };
    }

    if (phase === "range") {
      return { x: 158, y: 221, scale: 1.02, label: "set 20 L+" };
    }

    if (phase === "filter") {
      return { x: 382, y: 221, scale: 1.02, label: "choose travel" };
    }

    if (phase === "refine") {
      return { x: 508, y: 221, scale: 1.02, label: "add water resistant" };
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
    var activeTags = activeTagsFor(ms);
    var rangeActive = isRangeActive(ms);
    var results = filterProducts(activeTags, typed, rangeActive);

    els.progress.style.width = (Math.min(100, (ms / DURATION) * 100)).toFixed(3) + "%";
    els.body.setAttribute("data-phase", phase);
    els.body.setAttribute("data-active-filters", String(activeTags.length + (rangeActive ? 1 : 0)));
    els.typedQuery.textContent = typed;
    els.status.textContent = statusFor(typed, activeTags, rangeActive);

    if (phase !== previousPhase) {
      previousPhase = phase;
      renderCaptions(phase);
    }

    renderRange(rangeActive);
    renderChips(results, activeTags);
    renderCounts(results, activeTags, typed, rangeActive);
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
