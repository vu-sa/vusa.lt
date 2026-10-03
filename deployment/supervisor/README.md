# Supervisor programs

Reference copies of the supervisor programs running on the VPS, captured from
`/etc/supervisor/conf.d/` so they are reviewable and diffable. They were previously server-only,
undocumented and unversioned — nothing in the repo said these processes existed, which is part of why
the deploy went years without restarting them.

| Program | procs | What it runs |
|---|---|---|
| `laravel-worker` | 2 | production `queue:work` |
| `laravel-sharepoint-worker` | 1 | production `queue:work --queue=sharepoint-sync` |
| `staging-laravel-worker` | 1 | staging `queue:work` |
| `reverb` | 1 | production `reverb:start` (WebSockets) |
| `staging-reverb` | 1 | staging `reverb:start` on `127.0.0.1:6002`, only with `STAGING_BROADCASTING_ENABLED=true` |
| `staging-inertia-ssr` | 1 | staging Node SSR renderer on `127.0.0.1:13715`, used only with `INERTIA_SSR_ENABLED=true` |

`typesense.conf` and `umami.conf` also live on the server but are not Laravel processes, so they are
not mirrored here.

## Why supervisor and not systemd

Asked and checked, rather than assumed. The appealing version of the idea — run the workers as
`systemctl --user` units owned by `vusa_lt_usr`, so changing them needs no root — **cannot work on
this VPS**. It is an OpenVZ container (`systemd-detect-virt` → `openvz`, kernel 4.19, cgroup v1) and
systemd cannot create a user manager:

```
systemd[…]: Failed to create /user.slice/user-1001.slice/user@1001.service/init.scope
            control group: Permission denied
systemd[…]: Failed to allocate manager object: Permission denied
```

`user@1000`, `user@1001`, `user@1005` and `user@115` are all sitting in a failed state — no user on
the box has a working systemd session. Enabling lingering does not help; the failure is cgroup
creation, which happens before lingering is relevant.

That leaves systemd **system** units with `User=vusa_lt_usr`, which need root to install and change
exactly as supervisor does — so there is no self-service gain, only a migration. What systemd would
genuinely add is journald rotation, `Restart=`/`RestartSec=` backoff and `After=`/`Requires=`
ordering: real, but modest, and supervisor 4.2.2 already rotates its own streams (50 MB × 10).

There is also local precedent: `/etc/systemd/system/typesense-server.service` exists, has been
`failed` since 2026-07-14, and Typesense now runs under supervisor instead. `systemctl
is-system-running` reports `degraded`.

**Decision: keep supervisor.** On a fresh, non-containerised host, systemd system units would be a
reasonable choice; migrating a working queue here buys nothing that matters.

Most importantly, none of this affects picking up new code on deploy — see the last section. That is
`queue:restart`, and it works identically under either supervisor or systemd.

## `/etc` stays authoritative — do not symlink these in

Tempting, but wrong in this setup:

- The deploy does `git reset --hard` and (on staging) `git clean -fd` on the checkout. Symlinking
  `/etc/supervisor/conf.d/*.conf` into it means a bad commit, or a branch that predates a config,
  can stop the workers from starting.
- Supervisor does not notice changed files by itself. Picking up an edit needs
  `supervisorctl reread && supervisorctl update` as root, which the deploy user cannot do — so the
  symlink would buy no automation, only risk.

To change a program: edit the file here, review it in a PR, then apply it on the server as root and
reload:

```bash
sudo cp deployment/supervisor/laravel-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl status
```

## Restarting on deploy is handled in the app, not here

`deployment:run` calls `queue:restart` and `reverb:restart` (see `DeploymentRun::STEPS`). Both write
a signal the running processes check, so they exit cleanly after their current job and supervisor
starts them again on the new code — no root, and no supervisor knowledge, needed at deploy time.

Note `--max-time=3600`: without the `queue:restart` step, workers only pick up new code when they age
out, so a deploy's changes could take up to an hour to reach the queue. Reverb has no such recycling
and had accumulated 53 days of uptime — dozens of deploys — before that step existed.

## Optional public Inertia SSR pilot

`inertia-ssr.conf` is a new reference, not an installed VPS program. It runs one Node 24 worker,
bound to `127.0.0.1:13714`, with a 384 MiB heap limit. Its `autostart=false` prevents installation
from enabling the pilot. Build with `vendor/bin/sail npm run build:ssr`; CI packages
`bootstrap/ssr` with the client assets. Enabled deployments stop the renderer after `online`;
Supervisor restarts it with the new bundle. Disabled deployments leave it alone.

The Laravel switch is `INERTIA_SSR_ENABLED`; it defaults to false. Only anonymous visits to
`home`, `page` and `news` use SSR. Admin and signed-in visits use the existing client entry.
A failed render or a request exceeding one second falls back to client rendering. Published
HTML renders on the server; legacy nested JSON previews render after client hydration.

Before enabling, check all three page types in both languages, navigation, dark mode, and browser
hydration errors. Use anonymous Inertia page objects (clear `csrf_token`) as benchmark fixtures:

```bash
vendor/bin/sail node dev/benchmark-ssr.mjs storage/app/ssr-fixtures.json http://127.0.0.1:13714 300 5
```

The benchmark records cold renders, five warm renders per fixture, five requests/second for five
minutes, and a 35-request burst. Require zero errors and p95 below 500 ms, peak renderer RSS below
512 MiB, at least 2 GiB available RAM, and no increase in the VPS memory cgroup's `memory.failcnt`.
The heap cap is not an RSS cap; check the process separately during the run.

To roll back, set `INERTIA_SSR_ENABLED=false`, rebuild the config cache, and stop the SSR program.
Do not copy or symlink this reference into `/etc` until the pilot checks pass.

### Staging renderer

`staging-inertia-ssr.conf` is the place to try SSR before production. It differs from the
production reference in two ways: `INERTIA_SSR_PORT=13715`, so the two renderers never share a
port, and `autostart=true`, so it survives a reboot. `staging:verify-isolation` refuses an enabled
staging whose `INERTIA_SSR_URL` is not on loopback or names production's port — there, a staging
deploy's `inertia:stop-ssr` would stop production's renderer.

To switch it on:

```bash
sudo cp deployment/supervisor/staging-inertia-ssr.conf /etc/supervisor/conf.d/
sudo supervisorctl reread && sudo supervisorctl update
# in naujas.vusa.lt/.env: INERTIA_SSR_ENABLED=true, INERTIA_SSR_URL=http://127.0.0.1:13715
/opt/php85/bin/php artisan config:cache && /opt/php85/bin/php artisan staging:verify-isolation
curl -s http://127.0.0.1:13715/health
```

While `INERTIA_SSR_ENABLED=false` the process idles and deploys do not restart it, so it keeps the
bundle it started with: `sudo supervisorctl restart staging-inertia-ssr` when enabling. If it shows
`FATAL`, `bootstrap/ssr/ssr.js` is missing — the deployed revision predates the SSR build.

### VPS capacity check — 2026-09-30

A temporary renderer on port 13715 processed 1,559 requests, including the five-minute load and
35-request burst, with zero errors. Combined p50 was 53 ms and p95 76 ms; the maximum during the
burst was 1.12 seconds, which exceeds Laravel's one-second timeout and would use client fallback.
Peak renderer RSS was 336 MiB, available RAM stayed above 3.4 GiB, and the cgroup allocation-failure
counter stayed at 137931. The renderer used 59.7 CPU seconds across the run (about 20% of one core).
This supports a single-worker anonymous-public pilot at the sampled load, with fallback for bursts;
it does not measure long-term peaks or the application's PHP request time. The temporary worker
and files were removed. Live production SSR was not enabled.
