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
  <VUSA>
    <!-- <div
      class="order-1 col-span-1 top-40 sticky ml-4"
      style="height: 200px; align-self: start; justify-self: start"
    > -->
    <!-- <NAnchor>
        <NAnchorLink
          style="font-size: 12pt; font-weight: bold"
          title="Apie VU SA"
          href="#apie-vu-sa"
        />
        <NAnchorLink
          style="font-size: 12pt; font-weight: bold"
          title="VU SA strategija"
          href="#strategija"
        />
        <NAnchorLink
          style="font-size: 12pt; font-weight: bold"
          title="VU SA valdymo schema"
          href="#valdymo-schema"
        />
        <NAnchorLink
          style="font-size: 12pt; font-weight: bold"
          title="Parlamentas"
          href="#parlamentas"
        >
          <NAnchorLink
            style="font-size: 12pt; font-weight: initial"
            title="Raštai"
            href="#rastai"
          />
        </NAnchorLink>
        <NAnchorLink
          style="font-size: 12pt; font-weight: bold"
          title="Taryba"
          href="#taryba"
        />
      </NAnchor> -->
    <!-- </div> -->
    <div class="relative mb-4 h-1/2 w-full">
      <img
        class="max-w-[92ch] rounded-lg w-full h-60 object-cover mx-auto brightness-50"
        style="object-position: 0% 20%"
        src="/images/ataskaita2022/kitos-nuotraukos/IMG_8823.jpg"
      />
      <p
        class="absolute text-white font-bold text-3xl sm:text-4xl md:text-5xl w-full text-center bottom-8"
      >
        VU SA
      </p>
    </div>

    <div class="w-full mx-auto" style="align-self: end">
      <NCollapse class="max-w-[92ch] mx-auto" arrow-placement="right">
        <NCollapseItem>
          <template #header
            ><h2 id="apie-vu-sa" class="rounded-lg">Apie VU SA</h2></template
          >
          <div class="bg-white py-6 px-12 rounded-lg max-w-[80ch] prose mx-auto my-4">
            <p>
              <strong>Vilniaus universiteto Studentų atstovybė (VU SA)</strong> –
              seniausia ir didžiausia Lietuvoje ekspertinė švietimo organizacija,
              atstovaujanti studentams Universitete ir už jo ribų, puoselėjanti
              universitetinę kultūrą ir kurianti palankiausias sąlygas studento
              saviraiškai.
            </p>

            <p>
              <strong>VU SA</strong> – tai 15 atstovavimo funkciją atliekančių padalinių
              kiekviename VU kamieniniame akademiniame padalinyje, 19 programų, klubų,
              projektų ir viešųjų įstaigų (PKP), Centrinis biuras, koordinuojantis
              padalinius ir PKP, bei VU Studentų savivaldos Alumni draugija. Šiuo metu VU
              SA vienija apie 1 000 narių.
            </p>

            <strong>VU SA:</strong>
            <ul>
              <li>
                atstovauja studentams – gina studentų teises Universiteto valdymo
                organuose ir nacionaliniu lygmeniu;
              </li>
              <li>
                prisideda prie studijų proceso tobulinimo Universitete, yra visų
                svarbiausių VU valdymo institucijų dalis;
              </li>
              <li>
                rūpinasi socialine gerove – užtikrina patogią ir studentams pritaikytą
                aplinką, rūpinasi studentų integracija, dalyvauja stipendijų skirstymo
                procese;
              </li>
              <li>organizuoja turiningą laisvalaikį;</li>
              <li>
                skleidžia informaciją – rūpinasi, kad visos svarbiausios naujienos
                pasiektų studentus.
              </li>
            </ul>
          </div>
        </NCollapseItem>
        <NCollapseItem>
          <template #header
            ><h2 id="strategija">
              Vilniaus universiteto Studentų atstovybės strategija
            </h2></template
          >
          <div class="bg-white py-6 px-12 rounded-lg max-w-[80ch] prose mx-auto my-4">
            <p>
              <strong>Vizija</strong> – atvira aplinka savarankiškam studento tobulėjimui
              vieningoje Vilniaus universiteto bendruomenėje.
            </p>
            <p>
              <strong>Misija</strong> – efektyviai atstovaujant Vilniaus universiteto
              studentams bei puoselėjant universitetinę kultūrą, kurti palankiausias
              sąlygas studento saviraiškai.
            </p>
            <strong>Vertybės:</strong>
            <ul>
              <li>atvirumas</li>
              <li>atsakomybė</li>
              <li>veiklos kokybė</li>
              <li>vieningumas</li>
              <li>tobulėjimas</li>
            </ul>
            <strong>Strateginės kryptys:</strong>
            <ul>
              <li>kokybiškos studijos ir joms pritaikyta aplinka</li>
              <li>stipri organizacija</li>
              <li>darni universitetinė bendruomenė</li>
            </ul>
          </div>
        </NCollapseItem>
        <NCollapseItem>
          <template #header><h2 id="valdymo-schema">VU SA valdymo schema</h2></template>
          <div class="bg-white py-6 px-12 rounded-lg max-w-[80ch] prose mx-auto my-4">
            <p>
              Vilniaus universiteto Studentų atstovybės valdymo struktūrą sudaro:
              Konferencija (aukščiausia VU SA savivaldos institucija), Prezidentas,
              Parlamentas, Taryba bei Revizijos komisija. Šie valdymo organai pagal savo
              kompetenciją priima sprendimus jiems paskirtose srityse arba užtikrina
              kituose VU SA valdymo organuose priimtų nutarimų vykdymą.
            </p>
            <p>
              Plačiau su VU SA struktūra galite susipažinti čia:
              <Link
                :href="
                  route('main.page', {
                    lang: $page.props.locale,
                    permalink: 'vu-sa-struktura',
                  })
                "
                >https://vusa.lt/lt/vu-sa-struktura</Link
              >
            </p>
          </div>
        </NCollapseItem>
        <NCollapseItem>
          <template #header><h2 id="parlamentas">VU SA Parlamentas</h2></template>
          <div class="relative">
            <img
              class="max-w-[80ch] rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 mx-auto"
              src="/images/ataskaita2022/kitos-nuotraukos/Parlamentas.jpg"
            />
            <p
              class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
            >
              Parlamentas
            </p>
          </div>
          <div class="bg-white py-6 px-12 rounded-lg max-w-[80ch] prose mx-auto my-4">
            <p>
              VU SA Parlamentui pirmininkavo Aistė Rinkevičiūtė (iki 2022 m. vasario mėn.)
              ir Ugnius Sejonas (nuo 2022 m. vasario mėn.).
            </p>

            <p>2021–2022 m. VU SA Parlamentą sudarė:</p>
            <ul>
              <li>VU SA prezidentė Neda Žutautaitė</li>
              <li>VU SA viceprezidentas Arnoldas Solovjovas</li>
            </ul>
            <h3>VU SA padalinių atstovai</h3>
            <NCollapse>
              <NCollapseItem title="VU SA CHGF" name="1">
                <ul>
                  <li>Ignas Mugenis</li>
                  <li>Gabija Navašinskaitė</li>
                  <li>Viktorija Vrubliauskaitė</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA EVAF" name="2">
                <ul>
                  <li>Justinas Sacevičius</li>
                  <li>Vanesa Ražinskytė</li>
                  <li>Nadia Ronlev</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA FilF" name="3">
                <ul>
                  <li>Elena Pranevičiūtė</li>
                  <li>Anastasija Liubinskytė</li>
                  <li>Lauryna Martin Marin</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA FsF" name="4">
                <ul>
                  <li>Martyna Vilutytė</li>
                  <li>Vakarė Punelytė (iki 2022 m. vasario mėn.)</li>
                  <li>Aistė Rinkevičiūtė (iki 2022 m. vasario mėn.)</li>
                  <li>Vismantas Daujotas (nuo 2022 m. vasario mėn.)</li>
                  <li>Andrė Treinytė (nuo 2022 m. vasario mėn.)</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA FF" name="5">
                <ul>
                  <li>Raimondas Šiškevičius</li>
                  <li>Kasparas Daranda</li>
                  <li>Kornelija Kairytė</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA GMC" name="6">
                <ul>
                  <li>Kornelija Buivydaitė</li>
                  <li>Evelina Prosevičiūtė (iki 2022 m. vasario mėn.)</li>
                  <li>Lukas Šarauskas (iki 2022 m. vasario mėn.)</li>
                  <li>Eglė Jašinskaitė (nuo 2022 m. vasario mėn.)</li>
                  <li>Daniel Šematovič (nuo 2022 m. vasario mėn.)</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA IF" name="7">
                <ul>
                  <li>Ieva Šiaudvytytė</li>
                  <li>Aušrinė Dichavičiūtė</li>
                  <li>Tomas Mikalauskas</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA KnF" name="8">
                <ul>
                  <li>Lijana Savickienė</li>
                  <li>Indrė Bagdžiūtė</li>
                  <li>Ieva Šilingaitė</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA KF" name="9">
                <ul>
                  <li>Dominyka Goldbergaitė</li>
                  <li>Gabija Laibakojytė</li>
                  <li>Kristijonas Ražickas</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA MIF" name="10">
                <ul>
                  <li>Paulina Bilinskaitė</li>
                  <li>Nikita Gladčenko</li>
                  <li>Rita Žilinskaitė</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA MF" name="11">
                <ul>
                  <li>Emilija Narušytė</li>
                  <li>Julija Elena Koehnke</li>
                  <li>Gabija Venclovaitė</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA ŠA" name="12">
                <ul>
                  <li>Paulius Ladukas</li>
                  <li>Akvilė Bušininkaitė</li>
                  <li>Ieva Šukytė</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA TSPMI" name="13">
                <ul>
                  <li>Ugnė Bičkauskaitė</li>
                  <li>Ilijas Kulinič</li>
                  <li>Ugnius Sejonas</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA TF" name="14">
                <ul>
                  <li>Airina Mikulėnaitė</li>
                  <li>Klaudija Maliauskaitė</li>
                  <li>Dominykas Mečanec</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA VM" name="15">
                <ul>
                  <li>Justinas Brazaitis (iki 2021 m. gruodžio mėn.)</li>
                  <li>Edgaras Kodis (nuo 2021 m. gruodžio mėn.)</li>
                  <li>Matas Krikščiūnas</li>
                  <li>Ričardas Ratkevičius</li>
                </ul>
              </NCollapseItem>
            </NCollapse>
            <p>
              VU SA Parlamentas – VU SA valdymo organas, svarstantis svarbiausius
              socialinius ir akademinius, su studentais susijusius ar kitus VU SA
              strateginius klausimus. Parlamentą sudaro organizacijos prezidentas,
              viceprezidentas, atstovavimo funkciją vykdančių VU SA padalinių pirmininkai
              ir po du atstovus iš kiekvieno padalinio. Parlamento darbą organizuoja
              pirmininkas.
            </p>
            <p>
              Ataskaitiniu laikotarpiu įvyko 14 VU SA Parlamento posėdžių (iš jų – 5
              elektroniniai, 8 nuotoliniai ir 1 kontaktinis).
            </p>
            <NDivider></NDivider>
            <section id="rastai">
              <h3 class="text-center text-2xl">Raštai</h3>
              <NDivider></NDivider>
              <NCollapse accordion>
                <NCollapseItem>
                  <template #header>
                    <h4 class="mb-0">
                      Sudaryta laikinoji darbo grupė ir patvirtinta rezoliucija dėl
                      užsienio studentų integracijos Vilniaus universitete
                    </h4>
                  </template>

                  <p>
                    VU SA Parlamentas, tvirtindamas pagrindines integracijos proceso
                    prielaidas ir teikdamas šio proceso tobulinimo pasiūlymus, priėmė
                    rezoliuciją dėl užsienio studentų integracijos Universitete.
                    Suvokdamas integracijos svarbą, išryškėjančią užsienio studentams
                    bandant įsilieti į socialinį ir akademinį Universiteto gyvenimą, VU SA
                    Parlamentas šiuo raštu stengiasi atkreipti dėmesį į informacijos
                    sklaidą Universiteto komunikacijos kanaluose ir fizinėse erdvėse –
                    skatina skleisti žinią ne tik lietuvių, bet ir anglų kalbomis, bei
                    taip suteikti visą reikalingą informaciją užsienio studentams (-ėms).
                    Taip pat rašte akcentuojama Universiteto personalo užsienio kalbų
                    kompetencijų tematika, Universiteto darinių atskaitingumas ir
                    įtraukumas, papildomų veiklų užsienio kalba kūrimas visiems
                    bendruomenės nariams (pateikti ir pasiūlymai, kaip visa tai
                    įgyvendinti).
                  </p>
                </NCollapseItem>

                <NCollapseItem>
                  <template #header>
                    <h4 class="mb-0">
                      Sudaryta laikinoji darbo grupė ir patvirtinta pozicija dėl
                      akademinės etikos pažeidimų prevencijos
                    </h4>
                  </template>

                  <p>
                    Remdamasis pozicija dėl akademinės etikos pažeidimų prevencijos, VU SA
                    Parlamentas deklaravo siekį prisidėti prie akademinės etikos kodekso
                    principinių nuostatų pažeidimų likvidavimo.
                  </p>
                  <p>
                    Pagrindinės iškeltos problemos: apsunkintas informacijos prieinamumas
                    ir akademinės etikos konsultanto (-ės) studentams (-ėms) pareigybės
                    trūkumas – rašte pateikiami pasiūlymai, kaip šias problemas spręsti,
                    įtraukiant siūlymus atlikti tyrimus akademinės etikos srityje ar
                    įsteigti studentus (-es) konsultuojančio asmens pareigybę.
                  </p>
                </NCollapseItem>

                <NCollapseItem>
                  <template #header>
                    <h4 class="mb-0">
                      Sudaryta laikinoji darbo grupė ir patvirtinta pozicija dėl užsienio
                      studentų (-čių) teisinės padėties
                    </h4>
                  </template>

                  <p>
                    VU SA Parlamentas, siekdamas atkreipti Lietuvos valstybės dėmesį į
                    užsienio studentų (-čių) teisinę padėtį Lietuvoje ir augantį jų
                    skaičių, akcentuoja, jog užsienio ir Lietuvos studentų (-čių) teisinis
                    statusas turi būti lygiavertis, taip sudarant tinkamas sąlygas
                    užsienio studentams (-ėms) tapti visaverčiais Lietuvos Respublikos
                    gyventojais.
                  </p>
                  <p>
                    Pagrindiniai siūlymai sprendžiant šią problemą yra orientuoti į
                    vienodai prieinamas socialines paslaugas, vienodas akademinio
                    tobulėjimo galimybes bei finansinės naštos palengvinimą.
                  </p>
                </NCollapseItem>

                <NCollapseItem>
                  <template #header>
                    <h4 class="mb-0">
                      Sudaryta laikinoji darbo grupė ir patvirtinta rezoliucija dėl
                      studentų (-čių) mokslinės veiklos Lietuvos universitetuose
                    </h4>
                  </template>

                  <p>
                    VU SA Parlamentas, siekdamas atkreipti dėmesį į studentų (-čių)
                    mokslinės veiklos svarbą ir suvokdamas jų poreikius bei neišnaudotas
                    galimybes vykdant mokslinę veiklą, rašte įtraukė siūlymus, skirtus
                    studentų (-čių) mokslinės veiklos galimybių ir kokybės gerinimui.
                    Pagrindiniai rezoliucijoje minimi siūlymai yra orientuoti į „studentų
                    (-čių) mokslinės veiklos“ sąvokos apibrėžimą, studentų (-čių)
                    mokslinės veiklos finansavimo didinimą, tinkamų sąlygų sudarymą
                    mokslinės veiklos vykdymui bei universitetų atskaitomybės didinimą
                    sudarant tinkamas sąlygas studentams (-ėms) vykdyti mokslinę veiklą.
                  </p>
                </NCollapseItem>

                <NCollapseItem>
                  <template #header>
                    <h4 class="mb-0">
                      Sudaryta laikinoji darbo grupė ir patvirtinta pozicija dėl studentų
                      (-čių) praktikų organizavimo gerinimo
                    </h4>
                  </template>

                  <p>
                    Siekdamas atkreipti dėmesį į praktikų organizavimo procese kylančias
                    problemas, šiuo raštu VU SA Parlamentas atkreipia dėmesį ir į Vilniaus
                    universiteto, ir į nacionaliniu lygmeniu praktikos kokybę
                    reglamentuojančių bei reguliuojančių mechanizmų trūkumus. Pagrindiniai
                    siūlymai yra orientuoti į patiriamų išlaidų kompensavimą neapmokamos
                    praktikos metu arba kuriant apmokamos praktikos vietas, taip pat
                    kuriant grįžtamojo ryšio kultūrą ir sistemą praktikų metu. Be to,
                    orientuojamasi į lygių Lietuvos ir užsienio studentų (-čių) praktikos
                    galimybių užtikrinimą praktikos atlikimo vietoje.
                  </p>
                </NCollapseItem>

                <NCollapseItem>
                  <template #header>
                    <h4 class="mb-0">
                      Sudaryta laikinoji darbo grupė ir patvirtinta pozicija dėl Vilniaus
                      Universiteto studentų (-čių) žemo finansinio raštingumo lygio
                    </h4>
                  </template>

                  <p>
                    Siekdamas atkreipti dėmesį į žemą studentų finansinio raštingumo lygį
                    Vilniaus universitete, šiuo raštu VU SA Parlamentas teikia siūlymus
                    problemos sprendimo būdams, akcentuodamas finansinio raštingumo svarbą
                    per studijų prienamumo ir studento (-ės) finansinės gerovės prizmę.
                    Pagrindiniai siūlymai yra orientuoti į mokymų rengimą, visuomenės
                    švietimą bei visai Vilniaus universiteto studentų (-čių) bendruomenei
                    prieinamus bendrųjų universitetinių studijų dalykus, kurie plėstų
                    studentų (-čių) finansinio raštingumo žinias.
                  </p>
                </NCollapseItem>

                <NCollapseItem>
                  <template #header>
                    <h4 class="mb-0">
                      Posėdžio metu patvirtintas nutarimas dėl organizacijos pozicijos
                      atsižvelgiant į Ukrainoje vykstančius procesus
                    </h4>
                  </template>

                  <p>
                    Šiuo nutarimu VU SA Parlamentas, išreikšdamas organizacijos palaikymą
                    Ukrainos Respublikai karo akivaizdoje bei smerkdamas Rusijos
                    Federacijos karinius veiksmus ir smurto protrūkį Ukrainos Respublikos
                    teritorijoje, suformavo organizacijos poziciją karo Ukrainoje
                    atžvilgiu.
                  </p>
                </NCollapseItem>
              </NCollapse>
            </section>
            <NDivider></NDivider>

            <strong>Kiti nuveikti darbai</strong>

            <ul>
              <li>
                Išrinkti studentų atstovai VU Senate (Ugnė Bičkauskaitė, Kornelija
                Buivydaitė, Julija Jarutytė, Andželika Jankauskytė, Justas Kvedaravičius,
                Vanesa Ražinskytė, Arnoldas Solovjovas, Daniel Šematovič) ir VU Tarybos
                išorinis studentų atstovas Justinas Kondratas;
              </li>
              <li>
                Patvirtinti VU SA MF nuostatai, įsteigiant Vilniaus universiteto Studentų
                atstovybės Medicinos fakultete Tarptautinių studentų tarybą ir
                patvirtinant pastarosios darbo reglamentą;
              </li>
              <li>
                Patvirtintos VU SA strateginės metų veiklos gairės (rugsėjo mėn.) ir po
                perplanavimų atnaujintas metų veiklos planas (vasario mėn.);
              </li>
              <li>
                Patvirtinta tvarka dėl išorinio studentų atstovo (-ės) Vilniaus
                universiteto Taryboje rinkimų (tokio atstovo (-ės) VU SA anksčiau
                neturėjo);
              </li>
              <li>Inicijuotas darbo reglamento atnaujinimo procesas;</li>
              <li>
                Inicijuotas VU SA Parlamento nuolatinių darbo grupių (komitetų) steigimo
                procesas ir pradėtas darbas su teisiniu reglamentavimu;
              </li>
              <li>
                Kovo mėn. posėdžio metu pristatytas 2021 m. pirmakursių integracijos
                proceso Universitete tyrimas;
              </li>
              <li>
                Kiekvieno posėdžio metu taikyta VU SA Parlamentui aktualios informacijos,
                kurią pristatydavo VU SA Prezidentė, praktika;
              </li>
              <li>
                Sudaryta VU SA Parlamento komunikacijos darbo grupė, kuri susirinkimų metu
                diskutavo bei generavo pasiūlymus, skirtus Parlamento komunikacijos soc.
                tinkluose atnaujinimui;
              </li>
              <li>
                Sudarytas VU SA parlamentaro gidas, kuriame pateikta visa svarbiausia
                informacija apie Parlamentą ir jo veikimą, skirta naujiems ir į Parlamentą
                besiruošiantiems keltis asmenims;
              </li>
              <li>
                Visi nuotoliniai posėdžiai buvo transliuojami VU SA „Facebook“ paskyroje;
              </li>
              <li>
                Po kiekvieno nuotolinio ir kontaktinio VU SA Parlamento posėdžio VU SA
                puslapyje publikuoti straipsniai, informuojantys apie tai, kas buvo
                aptarta ir patvirtinta posėdžių metu;
              </li>
              <li>
                Vasarą organizuoti VU SA Parlamento mokymai, skirti stiprinti parlamentarų
                įgūdžius advokacijos, raštų rengimo, Parlamento ir organizacijos suvokimo
                srityse bei bendrųjų kompetencijų kėlimui;
              </li>
              <li>
                Du kartus vykdyti individualūs pokalbiai tarp VU SA Parlamento pirmininkų
                ir VU SA padalinių parlamentarų, skirti padalinių problemų ir nuomonės
                rinkimui;
              </li>
              <li>Patvirtinta 2022 m. finansinių išlaidų ir pajamų sąmata;</li>
              <li>
                Posėdžių metu buvo pristatyti tyrimai apie:
                <ul>
                  <li>studentų (-čių) integraciją;</li>
                  <li>studentų (-čių) mokslinę veiklą;</li>
                  <li>užsienio studentų (-čių) integraciją;</li>
                  <li>
                    studentų (-čių) praktikų vykdymą ir vykdymo kokybės užtikrinimą
                    Universitete bei už jo ribų.
                  </li>
                </ul>
              </li>
            </ul>
            <p>
              VU SA Parlamento posėdžiuose dalyvavo ir įvairiomis temomis kalbėjo
              kviestiniai svečiai – Vilniaus universiteto Studijų kokybės ir plėtros
              skyriaus vadovas Andrius Uždanavičius ir Vilniaus universiteto Centrinės
              akademinės etikos komisijos pirmininkė Vigita Vėbraitė.
            </p>
          </div>
        </NCollapseItem>
        <NCollapseItem>
          <template #header><h2 id="taryba">VU SA Taryba</h2></template>
          <div class="relative">
            <NButton
              text
              class="relative"
              style="white-space: normal; margin: 0 auto 0 auto; width: 100%"
              @click="showTaryba = true"
            >
              <img
                class="rounded-lg max-w-[80ch] w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 mx-auto"
                src="/images/ataskaita2022/koordinatoriu-nuotraukos/bendros/Taryba-2.jpg"
              />
            </NButton>
            <p
              class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
            >
              VU SA Taryba
            </p>
          </div>
          <div class="bg-white py-6 px-12 rounded-lg max-w-[80ch] prose mx-auto my-4">
            <p>2021–2022 m. VU SA Tarybą sudarė:</p>
            <ul>
              <li><strong>VU SA Prezidentė:</strong> Neda Žutautaitė</li>
              <li><strong>VU SA CHGF:</strong> Ignas Mugenis</li>
              <li><strong>VU SA EVAF:</strong> Justinas Sacevičius</li>
              <li><strong>VU SA FF:</strong> Raimondas Šiškevičius</li>
              <li><strong>VU SA FilF:</strong> Elena Pranevičiūtė</li>
              <li><strong>VU SA FsF:</strong> Martyna Vilutytė</li>
              <li><strong>VU SA GMC:</strong> Kornelija Buivydaitė</li>
              <li><strong>VU SA IF:</strong> Ieva Šiaudvytytė</li>
              <li><strong>VU SA KF:</strong> Dominyka Goldbergaitė</li>
              <li><strong>VU SA KnF:</strong> Lijana Savickienė</li>
              <li><strong>VU SA MF:</strong> Emilija Narušytė</li>
              <li><strong>VU SA MIF:</strong> Paulina Bilinskaitė</li>
              <li><strong>VU SA ŠA:</strong> Paulius Ladukas</li>
              <li><strong>VU SA TF:</strong> Airina Mikulėnaitė</li>
              <li><strong>VU SA TSPMI:</strong> Ugnė Bičkauskaitė</li>
              <li>
                <strong>VU SA VM:</strong> Justinas Brazaitis (iki 2021 m. gruodžio mėn.),
                Edgaras Kodis (nuo 2021 m. gruodžio mėn.)
              </li>
            </ul>

            <p>
              VU SA Taryba – VU SA valdymo organas, sprendžiantis bendradarbiavimo ir
              organizacijos vidaus klausimus. Šį valdymo organą sudaro atstovavimo
              funkciją vykdančių Organizacijos padalinių vadovai (pirmininkai) ir
              prezidentas, kuris organizuoja šio valdymo organo veiklą. Ataskaitiniu
              laikotarpiu įvyko 9 VU SA Tarybos posėdžiai. VU SA Taryba svarstė ir priėmė
              sprendimus dėl institucinio stiprinimo, vidaus reikalų ir biudžeto.
            </p>

            <h3>Vidaus klausimai</h3>

            <p>
              VU SA Taryba ataskaitiniu laikotarpiu patvirtino VU Institucinio stiprinimo
              fondo sudėtį, VU SA viceprezidento poziciją, aktyviai įsitraukė į VU SA
              Strateginio veiklos plano 2022–2025 m. atnaujinimo procesą.
            </p>
            <strong>VU SA Taryba priėmė sprendimus:</strong>
            <ul>
              <li>Patvirtinti atnaujintą VU SA mokymų organizavimo paraiškos šabloną;</li>

              <li>
                Kiekviename VU SA P turėti už egzaminų stebėjimą atsakingą asmenį taip
                užtikrinant kokybišką VU SA programos „Sąžiningai“ veiklą;
              </li>
              <li>
                Sukuriant darbo grupę atnaujinti ,,Pirmininko vadovą“ siekiant palengvinti
                patirties perdavimo procesą naujiems padalinių vadovams;
              </li>

              <li>Įgyvendinti VU SA Duomenų apsaugos grupės proceso atnaujinimą;</li>

              <li>
                2022 m. ataskaitines-rinkimines konferencijas organizuoti kontaktiniu
                būdu;
              </li>
              <li>
                Nuo 2022 m. liepos 1 d. vykdyti centralizuotą VU SA marketingo procesą,
                kuriame veikia VU SA P marketingo srities koordinatoriai (jei tokie
                padaliniuose yra) bei VU SA marketingo koordinatoriaus surinkti
                savanoriai;
              </li>
              <li>
                Patvirtinti narystės kelio organizacijoje koncepciją, kuri grindžiama
                efektyvesniu narių iveiklinimu, kuratorių, studentų atstovų, programų,
                klubų, projektų ir ilgamečių narių lygiaverčiu įtraukimu į vykstančius
                procesus narystės kelią grindžiant savirefleksijų kultūra;
              </li>
              <li>
                Narystės užfiksavimo sistemos atnaujinimui nario testą praplėsti žiniomis
                apie VU (KAP) strukūrinius darinius, universiteto suteikiamas paslaugas,
                VU SA strategiją, organizacijos struktūrą bei įvaizdį. Buvo nutarta šalia
                testo kurti emokymų kursą, kuris padės užtikrinti gilesnį kompetencijų
                kėlimą kiekvienoje temoje.
              </li>
            </ul>
          </div>
        </NCollapseItem>
        <NCollapseItem>
          <template #header><h2 id="taryba">VU SA Revizijos komisija</h2></template>
          <div class="bg-white py-6 px-12 rounded-lg max-w-[80ch] prose mx-auto my-4">
            <p>
              Revizijos komisija yra VU SA savivaldos institucijų, VU SA padalinių ir kitų
              VU SA struktūrinių vienetų, taip pat VU SA įsteigtų juridinių asmenų
              finansinį ir veiklos patikrinimą atliekanti kontrolės institucija. Komisiją
              sudaro penki nariai, renkami VU SA ataskaitinėje-rinkiminėje konferencijoje.
            </p>

            <p>Ataskaitiniu laikotarpiu Revizijos komisiją sudarė:</p>
            <ul>
              <li>Evaldas Matijošaitis (komisijos pirmininkas)</li>
              <li>Tomas Bedulskij</li>
              <li>Aušrinė Gedminaitė</li>
              <li>Kamilė Gineikaitė</li>
              <li>Agnė Tamošiūnaitė</li>
            </ul>

            <p>
              2021–2022 m. Revizijos komisija atnaujino buvusią VU SA veiklos vertinimo
              metodiką pagal organizacijos strategiją ir organizacijoje vykstančių procesų
              aprašus. Metų eigoje pagal šią metodiką atliko tarpinį bei galutinį veiklos
              vertinimus. Vertinimo metu Revizijos komisija reguliariai palaikė ryšį su
              revizuojamais vienetais, tikrino VU SA, VU SA P, VU SA PKP, VU SA Duomenų
              apsaugos grupės narių, VU SA ISF ir VU SA Parlamento atitiktį kiekybiniams
              bei kokybiniams vertinimo kriterijams, įvertino veiklą vykdant apklausas ir
              susitikimus su revizuojamais vienetais.
            </p>

            <p>
              Ataskaitiniu laikotarpiu įvyko 2 atviri Revizijos komisijos posėdžiai ir 25
              uždari darbiniai susitikimai. Revizijos komisija dalyvavo Parlamento ir
              Tarybos posėdžiuose, organizacijos vykdomuose mokymuose, strateginių tikslų
              planavimuose ir strateginio plano kūrimo sesijose. Organizacijos veiklos
              vertinimas buvo atliktas renkant informaciją skirtingais būdais: dalyvaujant
              bendrose organizacijos veiklose, organizuojant individualius susitikimus su
              pareigas einančiais VU SA nariais bei vykdant apklausas raštu. Taip pat
              Revizijos komisija konsultavo VU SA narius ir teikė atsakymus į jų
              klausimus, susijusius su VU SA veikla bei teisės aktais.
            </p>

            <p>
              Organizacijos veiklos vertinimo ataskaitą Revizijos komisija teikia VU SA
              ataskaitinės-rinkiminės konferencijos metu (su vertinimo ataskaita galima
              susipažinti vusa.lt/lt/veiklos-ir-tyrimu-ataskaitos).
            </p>
          </div>
        </NCollapseItem>
      </NCollapse>
    </div>
    <!-- <div class="mt-6 text-right max-w-[92ch]">
      <Link
        class="underline duration-200 hover:text-red-600"
        :href="
          route('main.ataskaita2022', { lang: $page.props.locale, permalink: 'mvp' })
        "
        >Toliau: Metų veiklos planas >></Link
      >
    </div> -->
  </VUSA>
</template>

<script setup>
import VUSA from "../2-VU-SA.vue";
import { Link } from "@inertiajs/inertia-vue3";
import {
  NCollapse,
  NCollapseItem,
  NDivider,
  NAnchor,
  NAnchorLink,
  NModal,
  NButton,
} from "naive-ui";
import { ref } from "vue";

const showTaryba = ref(false);
</script>

<style scoped>
h2 {
  font-size: 1.5rem;
  font-weight: bold;
  /* text-align: center; */
  margin: 0em 0 0em 0;
}
h3,
h4 {
  margin: 1em 0 1em 0;
}
h4.mb-0 {
  margin: 0em 0 0em 0;
}

/* section {
  scroll-margin-top: 120px;
} */

:target::before {
  content: "";
  display: block;
  height: 180px;
  margin-top: -180px;
}
</style>
