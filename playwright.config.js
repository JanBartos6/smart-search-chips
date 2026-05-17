const { defineConfig } = require("@playwright/test");

module.exports = defineConfig({
  testDir: "./tests/browser",
  timeout: 30000,
  expect: {
    timeout: 10000
  },
  fullyParallel: false,
  reporter: [["list"], ["html", { open: "never" }]],
  use: {
    baseURL: process.env.SSCHP_TEST_BASE_URL || "http://localhost:8080",
    headless: true,
    trace: "retain-on-failure",
    screenshot: "only-on-failure",
    video: "retain-on-failure"
  }
});
