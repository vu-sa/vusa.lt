---
paths:
  - routes/api.php
---

# Routes

## File manager routes carry their own rate limiter
The files routes group uses `->middleware('throttle:fileManager')->withoutMiddleware('throttle:api')`. Without the `withoutMiddleware` call the shared 60/min api limiter still applies underneath and the file manager 429s again (one thumbnail request per visible image). New file-manager endpoints must go inside this group; the limiter itself is defined in AppServiceProvider::configureRateLimiting().
