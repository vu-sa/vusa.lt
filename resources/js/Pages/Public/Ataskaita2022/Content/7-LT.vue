<template>
  <NModal v-model:show="showTaryba">
    <div class="w-2/3 md:w-1/2 bg-white/95 shadow-lg rounded-lg p-6">
      <h2 class="text-center mb-4">VU SA Taryba</h2>
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 overflow-auto gap-4 duration-200"
      >
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Ignas Mugenis_Pirmininkas_CHGF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA CHGF</p>
          <strong>Ignas Mugenis</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Justinas Sacevičius_Pirmininkas_EVAF.jpg"
          />
          <p class="mt-1 text-sm">VU SA EVAF</p>
          <strong>Justinas Sacevičius</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Raimondas Šiškevičius_Pirminkas_FF.jpg"
          />
          <p class="mt-1 text-sm">VU SA FF</p>
          <strong>Raimondas Šiškevičius</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Elena Pranevičiūtė_Pirmininkė_FilF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FilF</p>
          <strong>Elena Pranevičiūtė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Martyna Vilutytė_Pirmininke_FsF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FsF</p>
          <strong>Martyna Vilutytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Kornelija Buivydaitė_Pirmininkė_GMC-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA GMC</p>
          <strong>Kornelija Buivydaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Ieva Šiaudvytytė_Pirmininke_IF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA IF</p>
          <strong>Ieva Šiaudvytytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Dominyka Goldbergaitė_pirmininkė_KF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA KF</p>
          <strong>Dominyka Goldbergaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Lijana Savickienė_Pirmininke_KnF.jpg"
          />
          <p class="mt-1 text-sm">VU SA KnF</p>
          <strong>Lijana Savickienė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Emilija Narušytė_Pirminnke_MF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA MF</p>
          <strong>Emilija Narušytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Paulina Bilinskaitė_Pirminink_MIF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA MIF</p>
          <strong>Paulina Bilinskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Paulius-Ladukas_Pirmininkas_ŠA-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA ŠA</p>
          <strong>Paulius Ladukas</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Airina Mikulėnaitė_Pirmininke_TF.jpg"
          />
          <p class="mt-1 text-sm">VU SA TF</p>
          <strong>Airina Mikulėnaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Ugnė Bičkauskaitė_Pirmininkė_TSPMI.jpg"
          />
          <p class="mt-1 text-sm">VU SA TSPMI</p>
          <strong>Ugnė Bičkauskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Justinas Brazaitis – VU SA VM Pirmininkas (iki 2021 gruodžio mėn.).jpg"
          />
          <p class="mt-1 text-sm">VU SA VM</p>
          <strong>Justinas Brazaitis (iki 2021 gruodžio mėn.)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Edgaras Kodis_Pirmininkas_VM(nuo 2021-12).jpg"
          />
          <p class="mt-1 text-sm">VU SA VM</p>
          <strong>Edgaras Kodis (nuo 2021 gruodžio mėn.)</strong>
        </div>
      </div>
    </div></NModal
  >
  <NModal v-model:show="showCB">
    <div class="w-2/3 md:w-1/2 bg-white/95 shadow-lg rounded-lg p-6">
      <h2 class="text-center mb-4">Centrinis biuras</h2>
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 overflow-auto gap-4 duration-200"
      >
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/CB/Neda Žutautaitė_Prezidentė.jpg"
          />
          <p class="mt-1 text-sm">Prezidentė</p>
          <strong>Neda Žutautaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/CB/Julija Jarutytė_Biuro vadovė.jpg"
          />
          <p class="mt-1 text-sm">Biuro vadovė</p>
          <strong>Julija Jarutytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/CB/Arnoldas Solovjovas_Akademinio proceso reikalų koordinatorius_Viceprezidentas.jpg"
          />
          <p class="mt-1 text-sm">Viceprezidentas ir akademinės srities koordinatorius</p>
          <strong>Arnoldas Solovjovas</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/CB/Ieva Budreikaitė_Socialinio proceso reikalų koordinatorė.jpg"
          />
          <p class="mt-1 text-sm">Socialinės srities koordinatorė</p>
          <strong>Ieva Budreikaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/CB/Margarita Aleksynaitė_Atstovų studijų programoms koordinatorė_Atstovų koordinatorė (nuo 2022-11).jpg"
          />
          <p class="mt-1 text-sm">
            Atstovų studijų programoms koordinatorė, atstovų koordinatorė (nuo 2021-11)
          </p>
          <strong>Margarita Aleksynaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/CB/Gabija Daunoravičiūtė_PKP koordinatorė_Marketingo koordinaotrė nuo 2022-11).jpg"
          />
          <p class="mt-1 text-sm">
            PKP koordinatorė, marketingo koordinatorė (nuo 2021-11)
          </p>
          <strong>Gabija Daunoravičiūtė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/CB/Jūratė Baltinaitė_Atstovė ryšiams su visuomene (nuo 2021-07).jpg"
          />
          <p class="mt-1 text-sm">
            Atstovė ryšiams su visuomene, LSP koordinatorė (iki 2021-09)
          </p>
          <strong>Jūratė Baltinaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/CB/Paula Zlatkutė_Komunikacijos koordinatorė (nuo 2021-10).jpg"
          />
          <p class="mt-1 text-sm">Komunikacijos koordinatorė (nuo 2021-10)</p>
          <strong>Paula Zlatkutė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/CB/Viktorija Milko_Organizacinės srities koordinatorė.jpg"
          />
          <p class="mt-1 text-sm">Organizacinės srities koordinatorė</p>
          <strong>Viktorija Milko</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/CB/Aurelija Vasiliauskaitė_Integracijos proceso koordinatorė.jpg"
          />
          <p class="mt-1 text-sm">Integracijos proceso koordinatorė</p>
          <strong>Aurelija Vasiliauskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/CB/Kamilė Kandrotaitė_Planavimo proceso koordinatorė.jpg"
          />
          <p class="mt-1 text-sm">Planavimo proceso koordinatorė</p>
          <strong>Kamilė Kandrotaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/CB/Andželika Jankauskytė_Administratorė_Atstovų koordinatorė (iki 2021-11).jpg"
          />
          <p class="mt-1 text-sm">Administratorė, atstovų koordinatorė (iki 2021-11)</p>
          <strong>Andželika Jankauskytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/CB/Aušrinė Dichavičiūtė_LSP koordinatorė (nuo 2021-09).jpg"
          />
          <p class="mt-1 text-sm">LSP koordinatorė (nuo 2021-09)</p>
          <strong>Aušrinė Dichavičiūtė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/CB/Justinas Kavoliūnas_IT koordinatorius.jpg"
          />
          <p class="mt-1 text-sm">IT koordinatorius</p>
          <strong>Justinas Kavoliūnas</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/CB/Gabija Laibakojytė_Komunikacijos koordinatorė (iki 2021-10)-2.jpg"
          />
          <p class="mt-1 text-sm">Komunikacijos koordinatorė (iki 2021-10)</p>
          <strong>Gabija Laibakojytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/CB/Rokas Toleikis_Marketingo koordinatorius (iki 2022-11)-2.jpg"
          />
          <p class="mt-1 text-sm">Marketingo koordinatorius (iki 2022-11)</p>
          <strong>Rokas Toleikis</strong>
        </div>
      </div>
    </div></NModal
  >
  <NModal v-model:show="showSocAkad">
    <div class="w-2/3 md:w-1/2 bg-white/95 shadow-lg rounded-lg p-6">
      <h2 class="text-center mb-4">Socialinės-akademinės srities koordinatoriai</h2>
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 overflow-auto gap-4 duration-200"
      >
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/socakad/Gabija Navašinskaitė_Socialinio-akademinio proceso reikalų koordinatorė_CHGF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA CHGF</p>
          <strong>Gabija Navašinskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/socakad/Eglė Romelytė_Socialinio-akademinio proceso reikalų koordinatorė_EVAF.jpg"
          />
          <p class="mt-1 text-sm">VU SA EVAF</p>
          <strong>Eglė Romelytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/socakad/Kasparas Daranda_Socakad_FF.jpg"
          />
          <p class="mt-1 text-sm">VU SA FF</p>
          <strong>Kasparas Daranda</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/socakad/Kamilė Kanapienytė_Socakad_FilF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FilF</p>
          <strong>Kamilė Kanapienytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/socakad/Vėjūnė Uldinskaitė_Socakad_Fsf-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FsF</p>
          <strong>Vėjūnė Uldinskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/socakad/Justė Žilionytė_Socakad_GMC-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA GMC</p>
          <strong>Justė Žilionytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/socakad/Vilija Valiukonytė_socakadas_IF(nuo 2021-09)-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA IF</p>
          <strong>Vilija Valiukonytė (nuo 2021-09)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/socakad/Greta Tarimaitė_Socakad_KF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA KF</p>
          <strong>Greta Tarimaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/socakad/Raminta Klimaitė_socakad_KnF.jpg"
          />
          <p class="mt-1 text-sm">VU SA KnF</p>
          <strong>Raminta Klimaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/socakad/Anastasija Volkova_Socakad_MIF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA MIF</p>
          <strong>Anastasija Volkova</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/socakad/Dominyka Banel_Socakad_TF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA TF</p>
          <strong>Dominyka Banel</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/socakad/Livija Trakelytė_socakad_TSPMI.jpg"
          />
          <p class="mt-1 text-sm">VU SA TSPMI</p>
          <strong>Livija Trakelytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/socakad/Matas Krikščiūnas_socakad_VM.jpg"
          />
          <p class="mt-1 text-sm">VU SA VM</p>
          <strong>Matas Krikščiūnas</strong>
        </div>
        <div class="m-auto text-center">
          <!-- <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          /> -->
          <p class="mt-1 text-sm">VU SA IF</p>
          <strong>Vakarė Gotautaitė (iki 2021-08)</strong>
        </div>
        <div class="m-auto text-center">
          <!-- <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          /> -->
          <p class="mt-1 text-sm">VU SA MF</p>
          <strong>Snieguolė Bičkutė</strong>
        </div>
      </div>
    </div></NModal
  >
  <NModal v-model:show="showAtstovai">
    <div class="w-2/3 md:w-1/2 bg-white/95 shadow-lg rounded-lg p-6">
      <h2 class="text-center mb-4">Atstovų koordinatoriai</h2>
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 overflow-auto gap-4 duration-200"
      >
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/atstovai/Monika Paulauskaitė_Atstovų koordinatorė_CHGF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA CHGF</p>
          <strong>Monika Paulauskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/atstovai/Adelė Čibiraitė_atstovų_EVAF(iki 2021-09)-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA EVAF</p>
          <strong>Adelė Čibiraitė (iki 2021-09)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/atstovai/Augustė Volkavičiūtė_Atstovų_EVAF(nuo 2021-09)-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA EVAF</p>
          <strong>Augustė Volkavičiūtė (nuo 2021-09)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/atstovai/Kasparas Daranda_Atstovai_FF.jpg"
          />
          <p class="mt-1 text-sm">VU SA FF</p>
          <strong>Kasparas Daranda</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/atstovai/Martynas Žaronaitis_Atstovų_FilF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FilF</p>
          <strong>Martynas Žaronaitis</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/atstovai/Erika Didrikaitė_Atstovu_Fsf-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FsF</p>
          <strong>Erika Didrikaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/atstovai/Miglė Krasauskaitė_Atstovai_GMC-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA GMC</p>
          <strong>Miglė Krasauskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/atstovai/Eva Ginkevičiūtė_atstovai_IF(nuo 2022-01).jpg"
          />
          <p class="mt-1 text-sm">VU SA IF</p>
          <strong>Eva Ginkevičiūtė (nuo 2022-01)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/atstovai/Viktorija Jankauskaitė_Atstovai_KF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA KF</p>
          <strong>Viktorija Jankauskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/atstovai/Aušrinė Tamulevičiūtė_Atsttovai_KnF.jpg"
          />
          <p class="mt-1 text-sm">VU SA KnF</p>
          <strong>Aušrinė Tamulevičiūtė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/atstovai/Miglė Vilniškytė_atstovai_MF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA MF</p>
          <strong>Miglė Vilniškytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/atstovai/Rimantas Stražinskas_atstovai_MIF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA MIF</p>
          <strong>Rimantas Stražinskas</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/atstovai/Viktorija Rubliova_atstovai_ŠA-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA ŠA</p>
          <strong>Viktorija Rubliova</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/atstovai/Kamilia Edita Fominova_Atstovu_TF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA TF</p>
          <strong>Kamilia Edita Fominova</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/atstovai/Milda Ščiglaitė_atastovai_TSPMI.jpg"
          />
          <p class="mt-1 text-sm">VU SA TSPMI</p>
          <strong>Milda Ščiglaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm h-10"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/atstovai/Ričardas Ratkevičius – VU SA VM Atstovų koordinatorius (iki 2021 gruodžio mėn.).jpg"
          />
          <p class="mt-1 text-sm">VU SA VM</p>
          <strong>Ričardas Ratkevičius (iki 2021-12)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/atstovai/Edgaras Kodis_atstovai_VM(nuo 2021-12).jpg"
          />
          <p class="mt-1 text-sm">VU SA VM</p>
          <strong>Edgaras Kodis (nuo 2021-12)</strong>
        </div>
        <div class="m-auto text-center">
          <!-- <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          /> -->
          <p class="mt-1 text-sm">VU SA IF</p>
          <strong>Visvaldas Vačiulis (iki 2021-08)</strong>
        </div>
        <div class="m-auto text-center">
          <!-- <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          /> -->
          <p class="mt-1 text-sm">VU SA IF</p>
          <strong>Bernadeta Lukošiūtė (nuo 2021-08 iki 2021-10)</strong>
        </div>
      </div>
    </div></NModal
  >
  <NModal v-model:show="showKom">
    <div class="w-2/3 md:w-1/2 bg-white/95 shadow-lg rounded-lg p-6">
      <h2 class="text-center mb-4">Komunikacijos koordinatoriai</h2>
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 overflow-auto gap-4 duration-200"
      >
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/kom/Beatričė Urbaitė_Komunikacijos koordinatorė CHGF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA CHGF</p>
          <strong>Beatričė Urbaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/kom/Aušrinė Bielskytė_Komunikacijos koordinatorė_EVAF.jpg"
          />
          <p class="mt-1 text-sm">VU SA EVAF</p>
          <strong>Aušrinė Bielskytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/kom/Artūr Karagezian_Kom_FF.jpg"
          />
          <p class="mt-1 text-sm">VU SA FF</p>
          <strong>Artūr Karagezian</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/kom/Justė Vardauskaitė_Kom_FilF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FilF</p>
          <strong>Justė Vardauskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/kom/Dovilė Mikniūtė_kom_Fsf-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FsF</p>
          <strong>Dovilė Mikniūtė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/kom/Eivinas Zableckas_kom_GMC(nuo 2021-08)-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA GMC</p>
          <strong>Eivinas Zableckas (nuo 2021-08)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/kom/Neringa Andrejevaitė_Kom_IF(iki 2021-12)-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA IF</p>
          <strong>Neringa Andrejevaitė (iki 2021-12)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/kom/Justina Reklaitytė_kom_IF(nuo 2022-01)-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA IF</p>
          <strong>Justina Reklaitytė (nuo 2022-01)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/kom/Gerda Minajevaitė_Kom_KF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA KF</p>
          <strong>Gerda Minajevaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/kom/Ieva Šilingaitė_Kom_KnF.jpg"
          />
          <p class="mt-1 text-sm">VU SA KnF</p>
          <strong>Ieva Šilingaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/kom/Lurdes Gaidelytė_Kom_MF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA MF</p>
          <strong>Lurdes Gaidelytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/kom/Agota Lužytė_kom_MIF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA MIF</p>
          <strong>Agota Lužytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/kom/Luka Tamulionytė_kom_TF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA TF</p>
          <strong>Luka Tamulionytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/kom/Kamilė Augėnaitė_kom_TSPMI.jpg"
          />
          <p class="mt-1 text-sm">VU SA TSPMI</p>
          <strong>Kamilė Augėnaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/kom/Abigailė Zizaitė_kom_VM.jpg"
          />
          <p class="mt-1 text-sm">VU SA VM</p>
          <strong>Abigailė Zizaitė</strong>
        </div>
        <div class="m-auto text-center">
          <!-- <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          /> -->
          <p class="mt-1 text-sm">VU SA GMC</p>
          <strong>Aistė Andrukaitė (iki 2021-08)</strong>
        </div>
      </div>
    </div></NModal
  >
  <NModal v-model:show="showMark">
    <div class="w-2/3 md:w-1/2 bg-white/95 shadow-lg rounded-lg p-6">
      <h2 class="text-center mb-4">Marketingo srities koordinatoriai</h2>
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 overflow-auto gap-4 duration-200"
      >
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Beatričė Urbaitė_Marketingo koordinatorė CHGF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA CHGF</p>
          <strong>Beatričė Urbaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Greta Bičiulaitytė_Markas_EVAF(iki 2021-11).jpg"
          />
          <p class="mt-1 text-sm">VU SA EVAF</p>
          <strong>Greta Bičiulaitytė (iki 2021-11)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Ieva Gruodytė_Markas_EVAF(nuo 2021-11)-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA EVAF</p>
          <strong>Ieva Gruodytė (nuo 2021-11)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Viktorija Nilova_markas_FF(iki 2022-03).jpg"
          />
          <p class="mt-1 text-sm">VU SA FF</p>
          <strong>Viktorija Nilova (iki 2022-03)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Kasparas Daranda_markas_FF(nuo 2022-03).jpg"
          />
          <p class="mt-1 text-sm">VU SA FF</p>
          <strong>Kasparas Daranda (nuo 2022-03)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Aušrinė Tverskytė_Markas_Filf-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FilF</p>
          <strong>Aušrinė Tverskytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Vesta Kubiliūtė_Markas_GMC-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA GMC</p>
          <strong>Vesta Kubiliūtė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA KF</p>
          <strong>Lukrecija Giedraitytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Gabija Burokaitė_markas_MIF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA MIF</p>
          <strong>Gabija Burokaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Kristina Lapienytė_markas_TF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA TF</p>
          <strong>Kristina Lapienytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Ugnius Sejonas_markas_TSPMI.jpg"
          />
          <p class="mt-1 text-sm">VU SA TSPMI</p>
          <strong>Ugnius Sejonas</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Marja Abramavičiūtė_markas_VM.jpg"
          />
          <p class="mt-1 text-sm">VU SA VM</p>
          <strong>Marja Abramavičiūtė</strong>
        </div>
        <div class="m-auto text-center">
          <!-- <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          /> -->
          <p class="mt-1 text-sm">VU SA IF</p>
          <strong>Evelina Volskytė (iki 2021-08)</strong>
        </div>
        <div class="m-auto text-center">
          <!-- <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          /> -->
          <p class="mt-1 text-sm">VU SA MF</p>
          <strong>Elas Aidukas</strong>
        </div>
      </div>
    </div></NModal
  >
  <NModal v-model:show="showOrg">
    <div class="w-2/3 md:w-1/2 bg-white/95 shadow-lg rounded-lg p-6">
      <h2 class="text-center mb-4">Organizacinės srities koordinatoriai</h2>
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 overflow-auto gap-4 duration-200"
      >
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/org/Egmontas Taraila_Organizacinės srities koordinarius CHGF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA CHGF</p>
          <strong>Egmontas Taraila</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/org/Einoras Tiškevičius_Organizacinės srities koordinatorius_EVAF.jpg"
          />
          <p class="mt-1 text-sm">VU SA EVAF</p>
          <strong>Einoras Tiškevičius</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/org/Kornelija Kairytė_Orgas_FF.jpg"
          />
          <p class="mt-1 text-sm">VU SA FF</p>
          <strong>Kornelija Kairytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/org/Gabrielė Jaskelevičiūtė_orgas_FiLF(iki 2021-08)-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FilF</p>
          <strong>Gabrielė Jaskelevičiūtė (iki 2021-08)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/org/Paulina Mikėnaitė_Orgas_Filf(nuo 2021-10)-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FilF</p>
          <strong>Paulina Mikėnaitė (nuo 2021-10)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/org/Vakarė Punelytė_Orgas_Fsf-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FsF</p>
          <strong>Vakarė Punelytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/org/Rusnė Uzielaitė_Orgas_GMC(nuo 2021-09)-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA GMC</p>
          <strong>Rusnė Uzielaitė (nuo 2021-09)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/org/Tomas Mikalauskas_Orgas_IF(nuo 2021-09)-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA IF</p>
          <strong>Tomas Mikalauskas (nuo 2021-09)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/org/Rugilė Barauskaitė_Orgas_KF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA KF</p>
          <strong>Rugilė Barauskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/org/Valentas Kuklis_orgas_KnF.jpg"
          />
          <p class="mt-1 text-sm">VU SA KnF</p>
          <strong>Valentas Kuklis</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/org/Beatričė Kuzminaitė_orgas_MF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA MF</p>
          <strong>Beatričė Kuzminaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/org/Gustas Štaševskis_orgas_MIF(iki 2021-09)-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA MIF</p>
          <strong>Gustas Štaševskis (iki 2021-09)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/org/Augustina Petraitytė_orgas_MIF(nuo 2021-09)-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA MIF</p>
          <strong>Augustina Petraitytė (nuo 2021-09)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/org/Akvilė Bušininkaitė_orgas_ŠA-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA ŠA</p>
          <strong>Akvilė Bušininkaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/org/Deimantė Lina Paškevičiūtė_orgas_TF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA TF</p>
          <strong>Deimantė Lina Paškevičiūtė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/org/Anelė Dromantaitė_orgas_TSMPI.jpg"
          />
          <p class="mt-1 text-sm">VU SA TSPMI</p>
          <strong>Anelė Dromantaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/org/Goda Valtytė_orgas_VM.jpg"
          />
          <p class="mt-1 text-sm">VU SA VM</p>
          <strong>Goda Valtytė</strong>
        </div>
        <div class="m-auto text-center">
          <!-- <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          /> -->
          <p class="mt-1 text-sm">VU SA GMC</p>
          <strong>Lukas Šarauskas</strong>
        </div>
        <div class="m-auto text-center">
          <!-- <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          /> -->
          <p class="mt-1 text-sm">VU SA IF</p>
          <strong>Evelina Volskytė (iki 2021-08)</strong>
        </div>
      </div>
    </div></NModal
  >
  <NModal v-model:show="showHR">
    <div class="w-2/3 md:w-1/2 bg-white/95 shadow-lg rounded-lg p-6">
      <h2 class="text-center mb-4">Žmogiškųjų išteklių koordinatoriai</h2>
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 overflow-auto gap-4 duration-200"
      >
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/HR/Jonas Stirbys_HR_EVAF(iki 2022-03).jpg"
          />
          <p class="mt-1 text-sm">VU SA EVAF</p>
          <strong>Jonas Stirbys (iki 2022-03)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/HR/Martyna Šimkutė_HR_FF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FF</p>
          <strong>Martyna Šimkutė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/HR/Karolina Kuzmaitė_HR_Fsf-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FsF</p>
          <strong>Karolina Kuzmaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/HR/Evelina Prosevičiūtė_HR_GMC-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA GMC</p>
          <strong>Evelina Prosevičiūtė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/HR/Neda Rasimavičiūtė_HR_IF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA IF</p>
          <strong>Neda Rasimavičiūtė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/HR/Mišel Zaslavskaitė_HR_KF-3.jpg"
          />
          <p class="mt-1 text-sm">VU SA KF</p>
          <strong>Mišel Zaslavskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/HR/Aušra Korkuzaitė_HR_KnF(2021-11)-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA KF</p>
          <strong>Aušra Korkuzaitė (iki 2021-11)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/HR/Indrė Bagdžiūtė_HR_KnF(nuo 2021-11).jpg"
          />
          <p class="mt-1 text-sm">VU SA KnF</p>
          <strong>Indrė Bagdžiūtė (nuo 2021-11)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/HR/Agnė Butkutė_HR_MF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA MIF</p>
          <strong>Agnė Butkutė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/HR/Dovydas Grajauskas_HR_TF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA TF</p>
          <strong>Dovydas Grajauskas</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/HR/Gintarė Kudelytė_HR_TSPMI.jpg"
          />
          <p class="mt-1 text-sm">VU SA TSPMI</p>
          <strong>Gintarė Kudelytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/HR/Edgaras Kodis – HRVM (iki 2021 gruodžio mėn.).jpg"
          />
          <p class="mt-1 text-sm">VU SA VM</p>
          <strong>Edgaras Kodis</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/HR/Karolina Alimovaitė – HRVM (nuo 2022 vasario mėn.).jpg"
          />
          <p class="mt-1 text-sm">VU SA VM</p>
          <strong>Karolina Alimovaitė</strong>
        </div>
        <div class="m-auto text-center">
          <!-- <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          /> -->
          <p class="mt-1 text-sm">VU SA CHGF</p>
          <strong>Vėjunė Vasiliauskaitė</strong>
        </div>
        <div class="m-auto text-center">
          <!-- <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          /> -->
          <p class="mt-1 text-sm">VU SA MF</p>
          <strong>Simona Žukauskaitė</strong>
        </div>
      </div>
    </div></NModal
  >
  <NModal v-model:show="showInt">
    <div class="w-2/3 md:w-1/2 bg-white/95 shadow-lg rounded-lg p-6">
      <h2 class="text-center mb-4">Integracijos proceso koordinatoriai</h2>
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 overflow-auto gap-4 duration-200"
      >
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/integr/Evaldas Kvietkauskas_Integracijos koordinatorius CHGF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA CHGF</p>
          <strong>Evaldas Kvietkauskas</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/integr/Gabija Platakytė_Integracijos proceso koordinatorė_EVAF.jpg"
          />
          <p class="mt-1 text-sm">VU SA EVAF</p>
          <strong>Gabija Platakytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/integr/Kornelija Račkauskaitė_Integracija_FF.jpg"
          />
          <p class="mt-1 text-sm">VU SA FF</p>
          <strong>Kornelija Račkauskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/integr/Rūta Balsevičiūtė_Integr_FilF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FilF</p>
          <strong>Kornelija Račkauskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/integr/Gintarė Tauterytė_Intergacija_FsF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FsF</p>
          <strong>Gintarė Tauterytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/integr/Deimantė Graželytė_Inegr_GMC(iki 2021-12)-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA GMC</p>
          <strong>Deimantė Graželytė (iki 2021-12)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/integr/Tomas Mikalauskas_Integr_IF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA IF</p>
          <strong>Tomas Mikalauskas</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/integr/Iveta Gelumbickaitė_Integrac_KF-3.jpg"
          />
          <p class="mt-1 text-sm">VU SA KF</p>
          <strong>Iveta Gelumbickaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/integr/Marija Trofimišinaitė_Integr_KnF.jpg"
          />
          <p class="mt-1 text-sm">VU SA KF</p>
          <strong>Marija Trofimišinaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/integr/Titas Masys_Integracija_MIF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA MIF</p>
          <strong>Titas Masys</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/integr/Benas Norkus_Integr_TF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA TF</p>
          <strong>Benas Norkus</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/integr/Anelė Dromantaitė_integr_TSMPI.jpg"
          />
          <p class="mt-1 text-sm">VU SA TSPMI</p>
          <strong>Anelė Dromantaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/integr/Justas Petraitis_Integr_VM.jpg"
          />
          <p class="mt-1 text-sm">VU SA VM</p>
          <strong>Justas Petraitis</strong>
        </div>
        <div class="m-auto text-center">
          <!-- <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          /> -->
          <p class="mt-1 text-sm">VU SA MF</p>
          <strong>Katažyna Aneta Borščevska (iki 2021-08)</strong>
        </div>
      </div>
    </div></NModal
  >
  <NModal v-model:show="showAdministratoriai">
    <div class="w-2/3 md:w-1/2 bg-white/95 shadow-lg rounded-lg p-6">
      <h2 class="text-center mb-4">Administratoriai</h2>
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 overflow-auto gap-4 duration-200"
      >
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/admin/Milda Striupaitytė_Administratorė_EVAF.jpg"
          />
          <p class="mt-1 text-sm">VU SA EVAF</p>
          <strong>Milda Striupaitytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/admin/Ugnė Šilingaitė_Admin_FF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FF</p>
          <strong>Ugnė Šilingaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/admin/Justė Vardauskaitė_Admin_FilF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FilF</p>
          <strong>Justė Vardauskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/admin/Vilius Alvinskas_Admin_Fsf-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FsF</p>
          <strong>Vilius Alvinskas</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/admin/Vesta Kubiliūtė_Admin_GMC-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA GMC</p>
          <strong>Vesta Kubiliūtė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/admin/Neda Rasimavičiūtė_admin_IF.jpg"
          />
          <p class="mt-1 text-sm">VU SA IF</p>
          <strong>Neda Rasimavičiūtė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/admin/Ernestas Rainys_Admin_KnF.jpg"
          />
          <p class="mt-1 text-sm">VU SA KnF</p>
          <strong>Ernestas Rainys</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/admin/Rūta Dikčiūtė_Admin_TF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA TF</p>
          <strong>Rūta Dikčiūtė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/admin/Ugnius Sejonas_admin_TSPMI.jpg"
          />
          <p class="mt-1 text-sm">VU SA TSPMI</p>
          <strong>Ugnius Sejonas</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/admin/Aistė Prialgauskaitė_admin_VM(iki 2021-09).jpg"
          />
          <p class="mt-1 text-sm">VU SA GMC</p>
          <strong>Aistė Prialgauskaitė (iki 2021-09)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/admin/Erika Radiun_admin_VM(nuo 2021-09).jpg"
          />
          <p class="mt-1 text-sm">VU SA GMC</p>
          <strong>Erika Radiun (nuo 2021-09)</strong>
        </div>
      </div>
    </div></NModal
  >
  <NModal v-model:show="showPKP">
    <div class="w-2/3 md:w-1/2 bg-white/95 shadow-lg rounded-lg p-6">
      <h2 class="text-center mb-4">Programų, klubų ir projektų koordinatoriai</h2>
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 overflow-auto gap-4 duration-200"
      >
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/pkp/Julija Baublinskaitė Be etikečių.jpg"
          />
          <p class="mt-1 text-sm">Programa "Be etikečių"</p>
          <strong>Julija Baublinskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/pkp/Gintarė Vaičekauskytė_HEMA.jpg"
          />
          <p class="mt-1 text-sm">HEMA</p>
          <strong>Gintarė Vaičekauskytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/pkp/Danielius Šematovič_The COINS.jpg"
          />
          <p class="mt-1 text-sm">The COINS</p>
          <strong>Danielius Šematovič</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/pkp/Gabija Raubaitė VU fotoklubas.jpg"
          />
          <p class="mt-1 text-sm">VU Foto klubas</p>
          <strong>Gabija Raubaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/pkp/Andranik Karagezian VU Fotoklubas.jpg"
          />
          <p class="mt-1 text-sm">VU Foto klubas</p>
          <strong>Andranik Karagezian</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/pkp/Gabija Strumylaitė VU Jaunųjų žurnalistų draugija.jpg"
          />
          <p class="mt-1 text-sm">VU Jaunųjų žurnalistų draugija</p>
          <strong>Gabija Strumylaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/pkp/Eglė Jašinskaitė VU Karjeros dienos.jpg"
          />
          <p class="mt-1 text-sm">VU Karjeros dienos</p>
          <strong>Eglė Jašinskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/pkp/Antropologų klubas Ivan Trunov_.jpg"
          />
          <p class="mt-1 text-sm">Antropologų klubas</p>
          <strong>Ivan Trunov</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/pkp/Aurelija Vasiliauskaitė SGV.jpg"
          />
          <p class="mt-1 text-sm">Studentiško gyvenimo vadovas</p>
          <strong>Aurelija Vasiliauskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/pkp/Justinas Brazaitis (nuo 2022 m. sausio mėn.) Sąžiningai.jpg"
          />
          <p class="mt-1 text-sm">Sąžiningai</p>
          <strong>Justinas Brazaitis (nuo 2022 m. sausio mėn.)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/pkp/Klaudija Pukalskaitė VU JEK.jpg"
          />
          <p class="mt-1 text-sm">VU Jaunųjų energetikų klubas</p>
          <strong>Klaudija Pukalskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/pkp/Rimantas Murza StartFM.jpg"
          />
          <p class="mt-1 text-sm">StartFM</p>
          <strong>Rimantas Murza</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/pkp/Robertas Rameika VU Debatų klubas.jpg"
          />
          <p class="mt-1 text-sm">VU Debatų klubas</p>
          <strong>Robertas Rameika</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/pkp/Simonas Bendinskas ESN Vilnius university.jpg"
          />
          <p class="mt-1 text-sm">ESN Vilnius University</p>
          <strong>Simonas Bendinskas</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/pkp/SMMS Rugilė Hokušaitė (iki 2021 m. gruodžio mėn.)_.jpg"
          />
          <p class="mt-1 text-sm">SMMS</p>
          <strong>Rugilė Hokušaitė (iki 2021 m. gruodžio mėn.)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/pkp/Urtė Marija Kasnikauskaitė (nuo 2021 m. gruodžio mėn.).jpg"
          />
          <p class="mt-1 text-sm">SMMS</p>
          <strong>Urtė Marija Kasnikauskaitė (nuo 2021 m. gruodžio mėn.)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/pkp/Viktorija Medžiaušaitė Teisės klinika.jpg"
          />
          <p class="mt-1 text-sm">Teisės klinika</p>
          <strong>Viktorija Medžiaušaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/pkp/Vytenis Almonaitis Kendo2.jpg"
          />
          <p class="mt-1 text-sm">Kendo klubas</p>
          <strong>Vytenis Almonaitis</strong>
        </div>
        <div class="m-auto text-center">
          <!-- <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          /> -->
          <p class="mt-1 text-sm">Sąžiningai</p>
          <strong>Ilijas Kulinič (iki 2022 sausio mėn.)</strong>
        </div>
        <div class="m-auto text-center">
          <!-- <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          /> -->
          <p class="mt-1 text-sm">VU Rašytojų klubas</p>
          <strong>Nikolajus Elkana Eimutis</strong>
        </div>
        <div class="m-auto text-center">
          <!-- <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          /> -->
          <p class="mt-1 text-sm">VU Studentų investicinis fondas</p>
          <strong>Ieva Turskytė (iki 2022 sausio mėn.)</strong>
        </div>
        <div class="m-auto text-center">
          <!-- <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          /> -->
          <p class="mt-1 text-sm">VU Studentų investicinis fondas</p>
          <strong>Gabrielė Stanionytė (nuo 2022 sausio mėn.)</strong>
        </div>
      </div>
    </div></NModal
  >
  <NModal v-model:show="showUzsienieciu">
    <div class="w-2/3 md:w-1/2 bg-white/95 shadow-lg rounded-lg p-6">
      <h2 class="text-center mb-4">Užsieniečių studentų reikalų koordinatoriai</h2>
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 overflow-auto gap-4 duration-200"
      >
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/uzsienieciu/Iveta Kavolynaitė_Užsieniečių studentų reikalų koordinatorė_EVAF.jpg"
          />
          <p class="mt-1 text-sm">VU SA EVAF</p>
          <strong>Iveta Kavolynaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/uzsienieciu/Marija Trofimišinaitė_Užsienio_KnF.jpg"
          />
          <p class="mt-1 text-sm">VU SA KnF</p>
          <strong>Marija Trofimišinaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/uzsienieciu/Michaela Andie Kleynhans_Uzsienio_VM.jpg"
          />
          <p class="mt-1 text-sm">VU SA VM</p>
          <strong>Michaela Andie Kleynhans</strong>
        </div>
      </div>
    </div></NModal
  >
  <NModal v-model:show="showOther">
    <div class="w-2/3 md:w-1/2 bg-white/95 shadow-lg rounded-lg p-6">
      <h2 class="text-center mb-4">Kiti koordinatoriai</h2>
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 overflow-auto gap-4 duration-200"
      >
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/Marija Kliocaitė_Studentų su individualiais poreikiais koordinatorė_FilF-2.jpg"
          />
          <p class="mt-1 text-sm">
            VU SA FilF studentų su individualiais poreikiais koordinatorė
          </p>
          <strong>Marija Kliocaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/ModestasTrubilaIT.jpg"
          />
          <p class="mt-1 text-sm">VU SA VM IT koordinatorius</p>
          <strong>Modestas Trubila (iki 2022 vasario mėn.)</strong>
        </div>
        <div class="m-auto text-center">
          <!-- <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          /> -->
          <p class="mt-1 text-sm">VU SA MIF IT koordinatorius</p>
          <strong>Haroldas Velioniškis</strong>
        </div>
      </div>
    </div></NModal
  >
  <NModal v-model:show="showLSP">
    <div class="w-2/3 md:w-1/2 bg-white/95 shadow-lg rounded-lg p-6">
      <h2 class="text-center mb-4">LSP koordinatoriai</h2>
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 overflow-auto gap-4 duration-200"
      >
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/lsp/Milda Striupaitytė_LSP_EVAF.jpg"
          />
          <p class="mt-1 text-sm">VU SA EVAF</p>
          <strong>Milda Striupaitytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/lsp/Viktorija Nilova_LSP_FF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FF</p>
          <strong>Viktorija Nilova</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/lsp/Justė Vardauskaitė_LSP_FilF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FilF</p>
          <strong>Justė Vardauskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/lsp/Vilius Alvinskas_LSP_FSf-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FsF</p>
          <strong>Vilius Alvinskas</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/lsp/Ernestas Rainys_LSP_KnF.jpg"
          />
          <p class="mt-1 text-sm">VU SA KnF</p>
          <strong>Ernestas Rainys</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/lsp/Nikita Česnavičiūtė_LSP_MF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA MF</p>
          <strong>Nikita Česnavičiūtė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/lsp/Gabrielė Kasperaitė_LSP_MIF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA MIF</p>
          <strong>Gabrielė Kasperaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/lsp/Rūta Dikčiūtė_LSP_TF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA TF</p>
          <strong>Rūta Dikčiūtė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/lsp/Ugnius Sejonas_LSP_TSPMI.jpg"
          />
          <p class="mt-1 text-sm">VU SA TSPMI</p>
          <strong>Ugnius Sejonas</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/lsp/Modestas Trubila – VU SA VM IT koordinatorius (iki 2022 vasario mėn.).jpg"
          />
          <p class="mt-1 text-sm">VU SA VM</p>
          <strong>Modestas Trubila (iki 2022-02)</strong>
        </div>
        <div class="m-auto text-center">
          <!-- <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/mark/Lukrecija Giedraitytė_Markas_KF-2.jpg"
          /> -->
          <p class="mt-1 text-sm">VU SA IF</p>
          <strong>Vakarė Gotautaitė</strong>
        </div>
      </div>
    </div></NModal
  >
  <Sritys>
    <h2 class="col-span-2 my-4 text-3xl">Ačiū, kad esate kartu!</h2>
    <NButton text class="relative" style="white-space: normal" @click="showCB = true">
      <img
        class="rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 shadow-sm hover:shadow-lg"
        src="/images/ataskaita2022/koordinatoriu-nuotraukos/bendros/Centrinis biuras-2.jpg"
      />
      <p
        class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
      >
        Centrinis biuras
      </p>
    </NButton>

    <NButton
      text
      class="relative"
      style="white-space: normal; margin: 0 auto 0 auto; width: 100%"
      @click="showTaryba = true"
    >
      <img
        class="rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 shadow-sm hover:shadow-lg"
        src="/images/ataskaita2022/koordinatoriu-nuotraukos/bendros/Taryba-2.jpg"
      />
      <p
        class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
      >
        VU SA Taryba
      </p>
    </NButton>

    <div class="relative" style="white-space: normal">
      <img
        class="rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 shadow-sm hover:shadow-lg"
        src="/images/ataskaita2022/kitos-nuotraukos/Parlamentas.jpg"
      />
      <p
        class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
      >
        VU SA Parlamentas
      </p>
    </div>

    <div class="relative" style="white-space: normal">
      <img
        class="rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 shadow-sm hover:shadow-lg"
        src="/images/ataskaita2022/kitos-nuotraukos/ISF.jpg"
      />
      <p
        class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
        style="text-shadow: 0 4px 8px rgba(0, 0, 0, 0.12), 0 2px 4px rgba(0, 0, 0, 0.08)"
      >
        Institucinio stiprinimo fondas
      </p>
    </div>

    <NButton
      text
      class="relative"
      style="white-space: normal"
      @click="showSocAkad = true"
    >
      <img
        class="rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 shadow-sm hover:shadow-lg"
        src="/images/ataskaita2022/koordinatoriu-nuotraukos/bendros/Socialinio-akademinio proceso koordinatoriai-2.jpg"
      />
      <p
        class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
      >
        Socialinio-akademinio proceso koordinatoriai
      </p>
    </NButton>
    <NButton
      text
      class="relative"
      style="white-space: normal"
      @click="showAtstovai = true"
    >
      <img
        class="rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 shadow-sm hover:shadow-lg"
        src="/images/ataskaita2022/koordinatoriu-nuotraukos/bendros/atstovu.jpg"
      />
      <p
        class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
      >
        Atstovų koordinatoriai
      </p>
    </NButton>
    <NButton text class="relative" style="white-space: normal" @click="showKom = true">
      <img
        class="rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 shadow-sm hover:shadow-lg"
        src="/images/ataskaita2022/koordinatoriu-nuotraukos/bendros/Komunikacijos koordinatoriai-2.jpg"
      />
      <p
        class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
      >
        Komunikacijos koordinatoriai
      </p>
    </NButton>
    <NButton text class="relative" style="white-space: normal" @click="showMark = true">
      <img
        class="rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 shadow-sm hover:shadow-lg"
        src="/images/ataskaita2022/koordinatoriu-nuotraukos/bendros/Marketingo srities koordinatoriai-2.jpg"
      />
      <p
        class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
      >
        Marketingo koordinatoriai
      </p>
    </NButton>
    <NButton text class="relative" style="white-space: normal" @click="showOrg = true">
      <img
        class="rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 shadow-sm hover:shadow-lg"
        src="/images/ataskaita2022/koordinatoriu-nuotraukos/bendros/organizacines.jpg"
      />
      <p
        class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
      >
        Organizacinės srities koordinatoriai
      </p>
    </NButton>
    <NButton text class="relative" style="white-space: normal" @click="showHR = true">
      <img
        class="rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 shadow-sm hover:shadow-lg"
        src="/images/ataskaita2022/koordinatoriu-nuotraukos/bendros/HR-2.jpg"
      />
      <p
        class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
      >
        Žmogiškųjų išteklių koordinatoriai
      </p>
    </NButton>
    <NButton text class="relative" style="white-space: normal" @click="showInt = true">
      <img
        class="rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 shadow-sm hover:shadow-lg"
        src="/images/ataskaita2022/koordinatoriu-nuotraukos/bendros/Integracijos proceso koordinatoriai-2.jpg"
      />
      <p
        class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
      >
        Integracijos proceso koordinatoriai
      </p>
    </NButton>
    <NButton
      text
      class="relative"
      style="white-space: normal"
      @click="showAdministratoriai = true"
    >
      <img
        class="rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 shadow-sm hover:shadow-lg"
        src="/images/ataskaita2022/koordinatoriu-nuotraukos/bendros/Administratoriai-2.jpg"
      />
      <p
        class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
      >
        Administratoriai
      </p>
    </NButton>
    <NButton text class="relative" style="white-space: normal" @click="showLSP = true">
      <img
        class="rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 shadow-sm hover:shadow-lg"
        src="/images/ataskaita2022/koordinatoriu-nuotraukos/bendros/LSP koordinatoriai-2.jpg"
      />
      <p
        class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
      >
        LSP koordinatoriai
      </p>
    </NButton>
    <NButton text class="relative" style="white-space: normal" @click="showPKP = true">
      <img
        class="rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 shadow-sm hover:shadow-lg"
        src="/images/ataskaita2022/koordinatoriu-nuotraukos/bendros/PKP-2.jpg"
      />
      <p
        class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
      >
        Programos, klubai ir projektai
      </p>
    </NButton>
    <NButton
      text
      class="relative"
      style="white-space: normal"
      @click="showUzsienieciu = true"
    >
      <img
        class="rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 shadow-sm hover:shadow-lg"
        src="/images/ataskaita2022/koordinatoriu-nuotraukos/bendros/Uzsienieciu-koordai-2.jpg"
      />
      <p
        class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
      >
        Užsieniečių studentų koordinatoriai
      </p>
    </NButton>
    <NButton text class="w-full" style="white-space: normal" @click="showOther = true">
      <p class="font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center">
        ir taip pat..!
      </p>
    </NButton>
  </Sritys>
</template>

<script setup>
import Sritys from "../7-Sritys.vue";
import { ref } from "vue";
import { NModal, NButton } from "naive-ui";

const showCB = ref(false);
const showSocAkad = ref(false);
const showAtstovai = ref(false);
const showKom = ref(false);
const showMark = ref(false);
const showOrg = ref(false);
const showHR = ref(false);
const showInt = ref(false);
const showAdministratoriai = ref(false);
const showPKP = ref(false);
const showUzsienieciu = ref(false);
const showTaryba = ref(false);
const showLSP = ref(false);
const showOther = ref(false);
</script>
