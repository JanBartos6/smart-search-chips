# Tests

This plugin currently uses a lightweight test setup instead of PHPUnit.

Files:

- `tests/unit.php`
  Runs plain-PHP unit tests for the core logic that can be exercised without a full WordPress bootstrap.
- `tests/http-smoke.sh`
  Runs public HTTP smoke tests against a running local site. By default it targets `http://localhost:8080`.
- `tests/browser/*.spec.js`
  Runs Playwright browser tests against the local site for live search, chip interaction, and search-results navigation behavior.
  The paid-license cycle test also needs `SSC_TEST_LICENSE_KEY` set in the environment.
- `tests/run-all.sh`
  Runs both suites in sequence.

## Run

```bash
./tests/run-all.sh
```

`run-all.sh` uses the local `php` binary when available. If `php` is not installed on the host, it falls back to the WordPress container in `../ssc-test` automatically.

Or run the parts separately:

```bash
php tests/unit.php
./tests/http-smoke.sh
npm run test:browser
```

To target a different local site:

```bash
SSC_TEST_BASE_URL=http://localhost:8080 ./tests/http-smoke.sh
```

To run the Freemius free/Pro cycle test as well:

```bash
SSC_TEST_LICENSE_KEY=your_test_license npm run test:browser
```
