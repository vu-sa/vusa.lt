# [VU SA puslapis](https://vusa.lt)

Šioje repositorijoje galite rasti VU Studentų atstovybės puslapio kodą. Daugiau apie [VU SA](https://vusa.lt/lt/apie).

Daugiau skaitykite [diskusijoje](https://github.com/vu-sa/vusa.lt/discussions/21), čia.

Kaip susikurti lokalią erdvę, parodyta sekcijoje [For start of development](#for-start-of-development)

Keletas taisyklių:

* **Commit'ai, branch'ai - tik anglų kalba.** Visa kita gali būti pasirinkta kalba - lietuvių arba anglų
* Jeigu yra noro diegti naują funkciją, būtinai turi įvykti diskusija, projektas ar *issue*, ir sutarta, kad tokia funkcija bus įdiegiama. Kitu atveju - visiškai negarantuoju, kad funkcija, pakeitimas bus įtrauktas į main šaką.

Puslapį palaiko Justinas Kavoliūnas nuo 2018 m. rugsėjo, iki tol - Mindaugas Taločka.

## In English

This is the repo for vusa.lt website. Everyone is welcome to help. :) More about [VU SA](https://vusa.lt/en/about). Also, read more on the vusa.lt repository: [here](https://github.com/vu-sa/vusa.lt/discussions/21) :smile:

---

### For start of development

#### Laravel Sail, i.e. Docker (Recommended!)

The easiest method to develop. You have to be able to run Docker on your machine.

**Steps:**

1. Clone the repository
2. After pulling the repository, run ./vendor/bin/sail up -d
3. Other setup steps:
   1. 

More instructions on [Laravel Sail](https://laravel.com/docs/9.x/sail)

#### On linux

There still should be some prerequisites missing, so I'll update this in time.

**UPDATE:** The repo is on PHP 8.1, so some steps may not work. If you don't want to run docker, then I'll advise to substitute `php8.0` with `php8.1`.

For Windows computers, WSL is a good and quite a simple solution to use in this case. [Installation guide](https://pureinfotech.com/install-windows-subsystem-linux-2-windows-10/)

Prerequisites:

* PHP 8.0 install. [Installation guide](https://linuxize.com/post/how-to-install-php-8-on-ubuntu-20-04/)
* After PHP install, install PHP modules: `sudo apt install php8.0-curl php8.0-zip php8.0-mbstring php8.0-dom php8.0-sqlite3 php8.0-gd`
* Install Composer v2. [Installation guide](https://getcomposer.org/download/).
* Install Node.js. On some computers, simple `sudo apt install nodejs` could work (check version, if below v14 and on Ubuntu, use this [guide](https://joshtronic.com/2021/05/09/how-to-install-nodejs-16-on-ubuntu-2004-lts/)

Laravel and vusa.lt installation:

1. Clone the directory with `git`: `git clone https://github.com/vu-sa/vusa.lt.git` will work.
2. Create a copy of the file `./.env.example` and name it `.env`
3. Run `php composer install` or `composer install`, depending if the Composer is global or not
4. Run `php artisan key:generate`
5. Setup a database (recommended: `touch database/database.sqlite`)
6. Run `npm install && npm run dev`
7. Run `php artisan migrate:fresh --seed`
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

---

If any questions arise how to setup for development, please write to it@vusa.lt :smile:
