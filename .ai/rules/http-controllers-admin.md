---
paths:
  - 'app/Http/Controllers/Admin/**'
---

# Http Controllers Admin

## Large Inertia show-page props
Keep the initial show response to the shell and immediately useful overview data. Put large, non-default panels behind Inertia::defer(), grouping only panels normally fetched together; their Vue consumers need a <Deferred> skeleton fallback. Reuse a Resource or named mapper for a full payload shared by controllers, and feature-test deferred props as absent initially before loading their group with loadDeferredProps().
