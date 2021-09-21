# [VU SA puslapis](https://vusa.lt)

Šioje repositorijoje galite rasti VU Studentų atstovybės puslapio kodą. Daugiau apie [VU SA](https://vusa.lt/lt/apie).

Kaip susikurti lokalią erdvę, parodyta sekcijoje [For start of development](#for-start-of-development-on-linux)

Keletas punktų žinojimui:

* **Visi commit'ai, pull'ai tik anglų kalba.**
* Jeigu yra noro diegti naują funkciją, būtinai turi įvykti diskusija, projektas ar *issue*, ir sutarta, kad tokia funkcija bus įdiegiama. Kitu atveju - visiškai negarantuoju, kad funkcija, pakeitimas bus įtrauktas į main.

Puslapį palaiko Justinas Kavoliūnas nuo 2018 m. rugsėjo, iki tol - Mindaugas Taločka.

## In English

This is the repo for vusa.lt website. Everyone is welcome to help. :) More about [VU SA](https://vusa.lt/en/about).

---

### For start of development (*on linux*)

I may have missed many prerequisites, so I'll update this in time.

1. Clone the directory with `git`
2. Create an .env file from .env.example (recommended: DB_CONNECTION=sqlite)
3. `php artisan key:generate`
4. Setup a database (recommended: `touch database/database.sqlite`)
5. Composer installation is SUGGESTED! Instructions here: <https://getcomposer.org/download/>
6. Run `php composer install`
7. Run `npm install && npm run dev`
8. Run `php artisan migrate:fresh --seed`
9. Modify your hosts file to direct *vusa.testas* to 127.0.0.1
10. `php artisan serve`
11. Open [vusa.testas:8000](http://vusa.testas:8000)

### For unit (padalinių) site development

1. Modify your host file to direct *if.vusa.testas* to 127.0.0.1. Only *if* domain is supported for unit site development ATM.
2. Open [if.vusa.testas:8000](http://if.vusa.testas:8000). Make sure that the server (`php artisan serve`) is on.

---

If any questions arise how to setup for development, please write to it@vusa.lt :smile:
