<template>
  <NCard
    style="width: 600px"
    title="vusa.lt pasikeitimų sąrašas"
    :bordered="false"
    size="huge"
    role="dialog"
    aria-modal="true"
    class="prose"
  >
    <NCollapse default-expanded-names="0.3">
      <NCollapseItem name="0.3" title="v0.3">
        <section>
          <h4 class="mt-0">v0.3.1</h4>
          <ul>
            <li>
              🪄 Galimybė kurti renginius, su ta pačia informacija, kaip buvo ji
              kuriama pirmakursių stovyklų metu
            </li>
            <li>🪄 Galimybė pateikti renginius, įvykius anglų kalba</li>
            <li>
              🌟 <strong>Studentų kalendorius</strong>
              <ul>
                <li>Rodo visus įvykius, patalpintus sistemoje</li>
                <li>Pasiekiamas tik iš pagrindinio puslapio (kolkas)</li>
                <li>
                  Galima išsaugoti kalendoriaus vieną įvykį Google kalendoriuje
                  (tiek iš kalendoriaus, tiek iš renginio puslapio)
                </li>
                <li>
                  Galima sinchronizuoti renginius naudojant nuorodą.
                  Sinchronizuoti galima visus įvykius arba tik tuos, kurie
                  pažymėti, kad yra <em>accessible</em> lietuviškai
                  nekalbantiems studentams. Instrukcijos - pagrindiniame
                  puslapyje.
                </li>
              </ul>
            </li>
            <li>🪄 Pagrindinio puslapio elementų pertvarka</li>
            <li>
              🐛 Perėjus iš naujienos į turinio puslapį, anksčiau nebuvo rodoma
              galimybė pakeisti kalbą
            </li>
          </ul>
        </section>
        <section>
          <h4 class="mt-0">v0.3</h4>
          <ul>
            <li>
              🌟 Anglų kalba
              <ul>
                <li>
                  Galimybė sukurti naujienas, puslapius ir pradinio puslapio
                  mygtukus anglų kalba ir juos susieti su lietuviškais
                </li>
                <li>Galimybė išversti pareigybių ir institucijų informaciją</li>
                <li>
                  Visų šių vertimų pateikimas puslapyje, atitinkamais būdais
                </li>
              </ul>
            </li>
            <li>🌟 Galimybė pakeisti koordinatorių tvarką kontaktų skiltyje</li>
            <li>🌟 Visų studentų atstovų puslapis VU KAP</li>
            <li>🪄 Panaujintas pagrindinio meniu stilius</li>
            <li>
              🛡 Pridėtas mygtukas, leidžiantis nueiti į administravimą, iš viešų
              puslapių
            </li>
          </ul>
        </section>
      </NCollapseItem>
      <NCollapseItem name="0.2" title="v0.2">
        <section>
          <h4 class="mt-0">v0.2.5</h4>
          <ul>
            <li>🌟 Atnaujintas vusa.lt administravimo stilius</li>
          </ul>
        </section>
        <section>
          <h4>v0.2.4</h4>
          <ul>
            <li>🌟 Pridėtas START FM radijo mygtukas</li>
            <li>
              🌟 Sukurtas papildomas registracijų mechanizmas (sutvarkyti
              vėliau)
            </li>
          </ul>
        </section>
        <section>
          <h4>v0.2.3</h4>
          <ul>
            <li>🪄 Atnaujintas kategorijų stiliaus puslapis</li>
            <li>🛡 Galima pridėti pareigybes tipus pareigybei</li>
            <li>🐛 Klaidų ir stiliaus pataisymai</li>
          </ul>
        </section>
        <section>
          <h4>v0.2.2</h4>
          <ul>
            <li>🌟 Sukurtas pirmakursių stovyklų bendras puslapis</li>
            <li>🌟 Sukurtas stovyklų individualių puslapių šablonas</li>
            <li>🛡 Sukurtas nuotraukų įkėlimo mechanizmas renginiams</li>
            <li>🌟 Atnaujintas pagrindinis puslapis</li>
          </ul>
        </section>
        <section>
          <h4>v0.2.1</h4>
          <ul>
            <li>
              🌟 Turinio administratoriai gali kurti renginius. Vieninteliai
              renginiai kolkas, kurie bus matomi - tai pirmakursių stovyklos.
            </li>
            <li>🛡 Galimybė pakeisti pagrindinę institucijos nuotrauką.</li>
            <li>
              🛡 Galimybė pridėti kuratoriaus:
              <ul>
                <li>Studijų programą</li>
                <li>
                  Papildomą nuotrauką (nesikirs su koordinatoriaus nuotrauka)
                </li>
                <li>Papildomą aprašymą</li>
              </ul>
            </li>
            <li>
              🪄 Dynamiškas kuratoriaus pareigybės pavadinimas pagal lytį
              (kolkas žiūrima pagal pavardės galutinę raidę, gali reikėti
              taisyti)
            </li>
          </ul>
        </section>
        <section>
          <h4>
            <a
              class="transition hover:text-vusa-red"
              target="_blank"
              href="https://github.com/vu-sa/vusa.lt/pull/90"
              >v0.2.0 (2022-07-23)</a
            >
          </h4>
          <p>
            Didžiausias atnaujinimas po vusa.lt atnaujinimo. Daugiausiai dėmesio
            skirta platformos techniniam atnaujinimui ir administravimo
            funkcijoms.
          </p>
          <ul>
            <li>
              🌟 Sutvarkyta Sąžiningai registracija, pridėtas dienų
              suskaičiavimas iki galimos registracijos datos
            </li>
            <li>🌟 Šiek tiek pakeistas breadcrumbs stilius</li>
            <li>
              🛡 Visi masiniai modeliai dabar yra puslapiuoti (nerodomi viename
              puslapyje)
            </li>
            <li>
              🛡 Generuojant nuorodą, lietuviškos raidės konvertuojamos į
              lotyniškas
            </li>
            <li>
              🛡 Sukurti <em>preview</em>, <em>update</em> ir
              <em>destroy</em> veiksmai beveik visiems duomenų modeliams. Jie
              pasiekiami ir iš lentelės puslapio.
            </li>
            <li>🛡 Kai kur pridėtos papildomos modelių tikrinimo taisyklės</li>
            <li>🛡 Atnaujintas dizainas (kortelės, spalvos)</li>
            <li>🛡 Institucijos padarytos pagrindiniu kontaktų puslapiu</li>
            <li>🛡 Atnaujintas teksto redaktoriaus stilius</li>
          </ul>
        </section>
      </NCollapseItem>
      <NCollapseItem name="0.1" title="v0.1">
        <section>
          <h4 class="mt-0">v0.1.2 (2022-07-08)</h4>
          <ul class="mb-2">
            <li>
              🔧 Šis atnaujinimas labiausiai skirtas puslapio vidui ir kūrimo
              procesui atnaujinti.
            </li>
            <li>🛡 Administracinių ikonų suvienodinimas.</li>
            <li>
              🌟 Pridėta galimybė įsidėti VU SA naujienų <em>feed</em> į Atom
              tipo naršyklę.
            </li>
          </ul>
        </section>
        <section>
          <h4>v0.1.1 (2022-06-12)</h4>
          <ul class="mb-2">
            <li>
              🛡 Adminstratoriams, dėl aiškumo, nerodomi veiksmai, kurie
              negalimi.
            </li>
            <li>
              🪄 Galima ištrinti paveikslėlį iš kontakto ir kitų vietų, kur
              prašo įkelti paveikslėlį.
            </li>
            <li>
              🪄 Galima
              <NTooltip
                ><template #trigger><strong>atsieti</strong></template
                >Kontaktas nebebus priskiriamas pareigybei, bet nebus
                ištrintas.</NTooltip
              >
              kontaktus nuo pareigybės, pareigybės puslapyje.
            </li>
            <li>
              🐛 Galima redaguoti pirmininkų informaciją padalinių
              koordinatoriams.
            </li>
          </ul>
        </section>

        <section>
          <h4>v0.1.0 (2022-06-11)</h4>
          <ul>
            <li>
              🪄 Padalinius galima surasti puslapyje:
              <a
                class="text-vusa-red"
                target="_blank"
                :href="'/kontaktai/kategorija/padaliniai'"
                >Padaliniai</a
              >
            </li>
          </ul>
        </section>
      </NCollapseItem>
    </NCollapse>
  </NCard>
</template>

<script setup lang="ts">
import { NCard, NCollapse, NCollapseItem, NDivider, NTooltip } from "naive-ui";
</script>
