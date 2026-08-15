<?php

// Two things at once: the retry budget for assertions (Pest\Browser\Execution::waitForExpectation)
// and the Playwright client's default action timeout (Playwright::setTimeout() sets both) — the
// latter is what bounds the explicit waitForSelector() waits in waitForInertiaRender()
// (tests/Pest.php).
//
// The default 5s is tuned for a warm local machine. Note: this bump alone did NOT fix the
// CI-only hydration failure it was first added for — that failure was the assertion retry loop
// hot-spinning (Amp\delay(0), no backoff) in the same process as the in-process Laravel HTTP
// server serving the lazily-imported page chunk, so a bigger budget just spun longer. The real
// fix is the explicit Node-side wait in tests/Pest.php; this stays as the safety net for every
// assertion that still goes through the retry path.
pest()->browser()->timeout(15_000);
