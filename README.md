# [VU SR website](https://vusa.lt)

👋 This is the repository for vusa.lt website. More about [VU SR here](https://vusa.lt/en/about). Also, read more on the vusa.lt repository: [here](https://github.com/vu-sa/vusa.lt/discussions/21)

Contributions are welcome!

## Contributions 

* Try to keep commits and branches in English.
* New features must be approved beforehand, to be merged into `main`.

The site is supported by [Justinas Kavoliūnas](https://github.com/justinaskav) from September 2018.

## Installation

I found [Laravel Sail](https://laravel.com/docs/11.x/sail) to be the best option for development.

**Steps:**

1. Fork the repository
2. Clone to your workstation
3. Run the `./dev/sailsetup.sh` script

### Local domain setup 

Local domain (and subdomain) setup must be configured for the site to work properly.

1. Modify your host file to direct *www.vusa.test* to 127.0.0.1.
2. Redirect any other subdomains, e.g. *if.vusa.test* to 127.0.0.1

**Testing**: Run `./vendor/bin/sail npm run test` for frontend tests and `./vendor/bin/sail artisan test` for backend tests.

If any questions arise how to setup for development, please write to <it@vusa.lt> :smile:

**macOS Performance Tip:** For faster development with colima, use:

```bash
colima start --cpu 4 --memory 6 --disk 60 --vm-type=vz --mount-type=virtiofs
```
