<template>
  <Pradzia>
    <div
      class="min-w-[1/2] max-w-[3/4] w-auto col-span-4 md:col-span-2 md:sticky md:top-36 md:self-start"
    >
      <img
        class="my-4 md:w-4/5 col-span-4 rounded-sm shadow-md hover:shadow-lg duration-200 h-auto object-cover mx-auto mb-4 w-3/4"
        src="/images/ataskaita2022/kitos-nuotraukos/KNYGUTES VIRSELIS-01.jpg"
      />
      <div class="flex gap-2 justify-center flex-wrap">
        <Link
          :href="
            route('main.ataskaita2022', {
              lang: $page.props.locale,
              permalink: 'sveikinimai',
            })
          "
          ><NButton strong round secondary>Sveikinimai</NButton></Link
        >

        <Link
          :href="
            route('main.ataskaita2022', {
              lang: $page.props.locale,
              permalink: 'vu-sa',
            })
          "
        >
          <NButton strong round secondary>Apie VU SA</NButton></Link
        >

        <Link
          :href="
            route('main.ataskaita2022', {
              lang: $page.props.locale,
              permalink: 'mvp',
            })
          "
          ><NButton strong round secondary>Metų veiklos planas</NButton></Link
        >
        <Link
          :href="
            route('main.ataskaita2022', {
              lang: $page.props.locale,
              permalink: 'studijos',
            })
          "
          ><NButton strong round secondary>Strateginės kryptys</NButton></Link
        >

        <Link
          :href="
            route('main.ataskaita2022', {
              lang: $page.props.locale,
              permalink: 'sritys',
            })
          "
          ><NButton strong round secondary>Bendruomenė</NButton></Link
        >
      </div>
    </div>
    <div class="col-span-4 md:col-span-2 mt-4 md:mt-0 prose">
      <img
        class="carousel-img"
        style="object-position: 0% 40%"
        src="/images/ataskaita2022/sveikinimai/neda.jpg"
      />
      <div class="bg-white p-6 text-sm prose-sm">
        <p>
          Mielos studentės ir studentai, Vilniaus universiteto bendruomenės
          narės ir nariai, miela VU SA,
        </p>

        <p>
          Šie metai buvo tikrai neeiliniai – norisi ir džiaugtis, ir dėkoti, o
          kartu sunku suprasti, kiek visko dedasi aplink!
        </p>

        <p>
          Šiemet mums pavyko pasiekti svarbių rezultatų tiek atstovaujant
          studentus (-es), tiek stiprinant Organizaciją: atnaujintas VU SA
          studentų (-čių) konsultavimo procesas, pradėta plėtoti savirefleksijų
          kultūra, parengtas Organizacijos strateginis veiklos planas,
          suvienodinti reikalavimai stojantiesiems į aukštąsias mokyklas
          nepriklausomai nuo valstybės finansavimo, aukštųjų mokyklų finansavimo
          mechanizmas papildytas skatinimu už studijų kokybę, valstybės biudžete
          numatyta didesnė lėšų dalis mokslinėms veikloms finansuoti, parengtas
          pirminis socialinės dimensijos plano projektas. Taip pat bei gerinama
          užsieniečių studentų teisinė padėtis, leidimų laikinai gyventi ir vizų
          išdavimo sąlygos, teisės ir sveikatos apsauga!
        </p>

        <p>
          Didžiuojuosi Organizacija, kad visų šių darbų pavyko pasiekti vis dar
          nesibaigusioje pandemijos situacijoje. Dar labiau didžiuojuosi, kad
          sugebėjome greitai ir prasmingai sureaguoti į Rusijos pradėtą karą
          Ukrainoje – praplėsdami mentorystės programą iš Ukrainos atvykusiems
          studentams (-ėms), inicijuodami didesnės finansinės paramos teikimą,
          užtikrindami nemokamo apgyvendinimo galimybes Universitete.
        </p>

        <p>
          Šiais metais užbaigėme trejus metus trukusį projektą „Viešojo valdymo
          švietimo srityje gerinimas įtraukiant nevyriausybines organizacijas“,
          kurio dėka, VU SA jau iki tol platus temų ir studentus(-es) liečiančių
          klausimų spektras dar labiau išsiplėtė – savo veikloje galime remtis
          konkrečiais ir tyrimais pagrįstais duomenimis (kurių išorės
          šaltiniuose neretai rasti sunku arba išvis nėra), o iš jų buvo ir bus
          formuojami studentams(-ėms) reikšmingi pasiūlymai ir pakeitimai tiek
          Universiteto, tiek nacionaliniu lygmenimis.
        </p>

        <p>
          Visų šitų ir krūvos dar neišvardytų dalykų nebūtume nuveikę be stiprių
          partnerysčių ir vieningo darbo kartu! Tad tariu didžiulį AČIŪ
          kiekvienai (-am) prisidėjusiai (-iam) prie to, kad studentams (-ėms)
          ir Organizacijai būtų geriau.
        </p>

        <p>
          Kviečiu susipažinti su darbais ir pokyčiais, kuriuos sukūrėme ir
          kuriems padėjome pamatus!
        </p>

        <p>
          <strong>V</strong>ieningai <strong>U</strong>ž
          <strong>S</strong>tudentų <strong>A</strong>teitį!
        </p>
        <strong>VU SA prezidentė Neda Žutautaitė</strong>
      </div>
    </div>
  </Pradzia>
</template>

<script setup lang="ts">
import Pradzia from "../0-Pradzia.vue";

import { Link } from "@inertiajs/inertia-vue3";
import { NButton } from "naive-ui";
import route from "ziggy-js";
</script>

f
<style scoped>
.carousel-img {
  width: 100%;
  height: 280px;
  object-fit: cover;
  border-radius: 1em 1em 0 0;
  margin-top: 0;
  margin-bottom: 0;
}
</style>
