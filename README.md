# [VU SR website](https://vusa.lt)

The code behind [vusa.lt](https://vusa.lt): the public website of Vilnius University Students' Representation (VU SR) and
**Mano VU SA**, the internal platform student representatives work in. It is one Laravel app with Vue pages, built and
kept up by students. More about VU SR [here](https://vusa.lt/en/about).

Contributions are welcome. Start with [CONTRIBUTING.md](CONTRIBUTING.md).

## Stack

Laravel 13 · Vue 3 · Inertia.js 3 · Tailwind CSS 4 · shadcn-vue · MariaDB · Redis · Typesense, all running locally
in Docker through [Laravel Sail](https://laravel.com/docs/sail).

## Set up locally

You need Git and Docker (Docker Desktop, or [colima](https://github.com/abiosoft/colima) on macOS). PHP, Composer and
Node do not have to be installed on your machine: everything runs inside the containers.

```bash
git clone https://github.com/vu-sa/vusa.lt.git   # or your fork
cd vusa.lt
./dev/setup.sh
```

`dev/setup.sh` is safe to run again. It creates `.env` and installs dependencies, then migrates and seeds the database
and generates the search keys. At the end it prints the remaining steps:

1. **Hosts file.** Point the local domains at your machine (`/etc/hosts`, or
   `C:\Windows\System32\drivers\etc\hosts`). The site only works on `www.vusa.test`, and each faculty has a
   subdomain. The script prints the full line; it starts like this:

   ```
   127.0.0.1 vusa.test www.vusa.test if.vusa.test ff.vusa.test chgf.vusa.test …
   ```

2. **Trust the local certificate.** nginx serves HTTPS with a certificate signed by a root CA generated on first
   start. Copy it out and trust it:

   ```bash
   docker compose cp nginx:/etc/nginx/certs/root-ca.crt .
   # macOS:  sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain root-ca.crt
   # Debian/Ubuntu:  sudo cp root-ca.crt /usr/local/share/ca-certificates/vusa-test.crt && sudo update-ca-certificates
   # Arch/Fedora:  sudo trust anchor root-ca.crt
   ```

   Firefox keeps its own store: import the file under *Settings → Privacy & Security → Certificates*. If the
   certificate was created before `.env` set `SSL_DOMAIN`/`SSL_ALT_NAME`, remove the `*_sail-nginx` Docker
   volume and restart, so a certificate for `*.vusa.test` is generated.

3. **Start Vite** with `./vendor/bin/sail npm run dev` and open <https://www.vusa.test>. Log in at `/mano` with
   **test@test.com** / **password**.

Later, `./vendor/bin/sail up -d` starts the containers again.

**macOS tip:** colima is much faster with `colima start --cpu 4 --memory 6 --disk 60 --vm-type=vz --mount-type=virtiofs`.

## Everyday commands

Run everything through Sail (`./vendor/bin/sail …`):

| What | Command |
| --- | --- |
| Check only what your branch changed (formatting, lint, types, related tests) | `./dev/check-changed.sh` |
| Backend tests | `./vendor/bin/sail artisan test --parallel --compact` |
| Frontend tests | `./vendor/bin/sail npm run test` |
| Storybook | `./vendor/bin/sail npm run storybook` |
| The guide (`docs/`) | `./vendor/bin/sail npm run docs:dev` |

## Where to read next

- [`docs/kurejams/`](docs/kurejams/index.md) (in Lithuanian; English in [`docs/en/developers/`](docs/en/developers/index.md))
  explains how a request travels through Laravel and Inertia to a Vue page, with a worked example. Start here.
- [`AGENTS.md`](AGENTS.md) holds the project conventions that people and AI coding agents follow. The path-scoped
  rules are in [`.ai/rules/`](.ai/rules/index.md).
- The user guide for the platform itself lives in [`docs/`](docs/) and is published at `/gidas`.

## Branches

Work targets the `dev` branch, which deploys to staging; `main` is production and is updated through release pull
requests from `dev`. Open pull requests against `dev`.

The site has been looked after by [Justinas Kavoliūnas](https://github.com/justinaskav) since September 2018. Questions
about setting up are welcome at <it@vusa.lt>.
