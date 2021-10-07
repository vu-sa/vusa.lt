# [VU SA puslapis](https://vusa.lt)

Šioje repositorijoje galite rasti VU Studentų atstovybės puslapio kodą. Daugiau apie [VU SA](https://vusa.lt/lt/apie).

Kaip susikurti lokalią erdvę, parodyta sekcijoje [For start of development](#for-start-of-development-on-linux)

Keletas taisyklių:

* **Commit'ai, branch'ai - tik anglų kalba.** Visa kita gali būti pasirinkta kalba - lietuvių arba anglų
* Jeigu yra noro diegti naują funkciją, būtinai turi įvykti diskusija, projektas ar *issue*, ir sutarta, kad tokia funkcija bus įdiegiama. Kitu atveju - visiškai negarantuoju, kad funkcija, pakeitimas bus įtrauktas į main šaką.

Puslapį palaiko Justinas Kavoliūnas nuo 2018 m. rugsėjo, iki tol - Mindaugas Taločka.

## In English

This is the repo for vusa.lt website. Everyone is welcome to help. :) More about [VU SA](https://vusa.lt/en/about).

---

### For start of development (*on linux*)

There still should be some prerequisites missing, so I'll update this in time.

For Windows computers, WSL is a good and quite a simple solution to use in this case. [Installation guide](https://pureinfotech.com/install-windows-subsystem-linux-2-windows-10/)

Prerequisites:

* PHP 8.0 install. [Installation guide](https://linuxize.com/post/how-to-install-php-8-on-ubuntu-20-04/)
* After PHP install, install PHP modules: `sudo apt install php8.0-curl php8.0-zip php8.0-mbstring php8.0-dom php8.0-sqlite`
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
8. Modify your hosts file to direct *vusa.testas* to 127.0.0.1
9. `php artisan serve`
10. Open [vusa.testas:8000](http://vusa.testas:8000)

### For unit (padalinių) site development

1. Modify your host file to direct *if.vusa.testas* to 127.0.0.1. Only *if* domain is supported for unit site development ATM.
2. Open [if.vusa.testas:8000](http://if.vusa.testas:8000). Make sure that the server (`php artisan serve`) is on.

---

If any questions arise how to setup for development, please write to it@vusa.lt :smile:
