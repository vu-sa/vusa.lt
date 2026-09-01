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
