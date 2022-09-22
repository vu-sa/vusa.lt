# Setup

## Prerequisites

> NOTE: Not required if using Laravel Sail + Docker.

- PHP 8.1 install. [Installation guide](https://linuxize.com/post/how-to-install-php-8-on-ubuntu-20-04/)
- Install PHP submodules: `php-curl php-zip php-mbstring php-dom php-gd php-sqlite3`
- Install Composer [Installation guide](https://getcomposer.org/download/).
- Install Node.js. On some computers, simple `sudo apt install nodejs` could work (check version, if below v14 and
  on Ubuntu, use this [guide](https://joshtronic.com/2021/05/09/how-to-install-nodejs-16-on-ubuntu-2004-lts/)

> For Windows computers, WSL is a good and quite a simple solution to use in this case. 
  [Installation guide](https://pureinfotech.com/install-windows-subsystem-linux-2-windows-10/)

## Laravel Sail

The easiest method to develop. You have to be able to run Docker and PHP (temporarily) on your machine.

**Steps:**

For initial setup run: `./dev/sailsetup.sh`. Note that docker must be started.

After initial setup you can run: `./dev/sailstart.sh` or do it manually below.

After updating the repository, run `./vendor/bin/sail up -d`

Other setup tips:

- Pull backend dependencies:  `./vendor/bin/sail composer update`
- Generate app key:           `./vendor/bin/sail artisan key:generate`
- Pull frontend dependencies: `./vendor/bin/sail npm update`
- Start a dev server:            `./vendor/bin/sail npm run dev`
- `./vendor/bin/sail artisan storage:link`
- Go to <http://localhost:8080> and create database manually, with name `vusa`, collation `utf8mb4_lithuanian_ci`.
- `./vendor/bin/sail artisan migrate:fresh`

More instructions on [Laravel Sail](https://laravel.com/docs/9.x/sail)

## Development

1. Clone the repository (or download ZIP);
2. Run `composer install`;
3. Run `npm install && npm run dev`
4. Copy `.env.example` into `.env` and enter values inside to suite your system;
5. Run `php artisan key:generate`
6. Setup a database:

> For SQLite you'll need to create file in `database/database.sqlite`

7. Run `php artisan migrate:fresh --seed`

> For optimal installation you'll also need the SQL file of database records. Please contact @justinaskav for this.
  I haven't had the time for any seeders.

8. Run `php artisan storage:link`
9. Modify your hosts file to direct *vusa.testas* to 127.0.0.1
10. `php artisan serve`
11. Open [vusa.testas:8000](http://vusa.testas:8000)

### For unit (padalinių) site development

1. Modify your host file to direct *if.vusa.testas* to 127.0.0.1. Only *if* domain is supported for unit site development ATM.
2. Open [if.vusa.testas:8000](http://if.vusa.testas:8000). Make sure that the server (`php artisan serve`) is on.

### Apache2 configuration

Apache2 is the recommended server for development, since it may resemble the production environment more.

How to setup: 

1. Make sure `apache2` is installed (`sudo apt install apache2`)
2. Create a `vusa.conf` file with this body.

```{}
<VirtualHost *:80>
  ServerName vusa.testas
  ServerAlias www.vusa.testas if.vusa.testas
  ServerAdmin webmaster@localhost
  DocumentRoot [INSERT YOUR DOCUMENT ROOT, something like .../vusa.lt/public]
  UseCanonicalName OFF

  <Directory [INSERT YOUR DOCUMENT ROOT DIRECTORY, something like .../vusa.lt/]>
    Options Indexes FollowSymLinks MultiViews
    AllowOverride All
    Require all granted
  </Directory>

  ErrorLog ${APACHE_LOG_DIR}/error.log
  CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

3. Modify hosts file to have a IPv6 loopback:

```{}
127.0.0.1 vusa.testas
::1 vusa.testas
127.0.0.1 if.vusa.testas
::1 if.vusa.testas
```

4. Give the `www-data` user your user group permissions, like `sudo usermod -a -G justinas www-data` (or fix the permissions yourself, however you want).
5. Give group write permissions to laravel.log `sudo chmod g+w storage/logs/laravel.log`
6. Run `sudo a2enmod rewrite && sudo service apache2 restart`
