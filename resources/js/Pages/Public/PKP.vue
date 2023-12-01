<template>
  <Head
    :title="
      $page.props.app.locale === 'lt'
        ? 'Programos, klubai ir projektai'
        : 'Programs, clubs and projects'
    "
  />
  <h1 class="my-8">
    {{
      $page.props.app.locale === "lt"
        ? "Programos, klubai ir projektai"
        : "Programs, clubs and projects"
    }}
  </h1>
  <p class="text-lg">
    {{
      $page.props.app.locale === "lt"
        ? "Jei šiame sąraše nerandi to, ko ieškai, gali iniciatyvą įkurti pats!"
        : "Can't see something that interests you? You can create an initiative yourself!"
    }}
  </p>

  <a
    class="my-4"
    :href="
      $page.props.app.locale === 'lt'
        ? 'nauju-stud-org-ikurimas'
        : 'procedure-for-the-establishmen'
    "
  >
    <NButton type="primary" size="large" class="max-w-fit">
      {{
        $page.props.app.locale === "lt"
          ? "Sužinok kaip tai padaryti!"
          : "Find out how to do that here!"
      }}</NButton
    >
  </a>
  <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
    <NCard
      v-for="(item, index) in items"
      :key="index"
      class="max-w-prose shadow-lg"
    >
      <template #header>
        <div class="flex place-items-center break-all">
          <!--TODO: logo of all PKP-->
          <!-- <img :src="item.imageSource" :alt="item.title" class="basis-1/3" /> -->
          <h4 v-if="$page.props.app.locale === 'lt'">{{ item.titleLT }}</h4>
          <h4 v-else>{{ item.titleEN }}</h4>
        </div>
      </template>
      <div v-if="$page.props.app.locale === 'lt'">
        <p>{{ item.descriptionLT }}</p>
        <a
          :href="item.link"
          target="_blank"
          rel="noopener noreferrer nofollow"
          class="text-blue-500 hover:text-blue-700"
          >Daugiau informacijos</a
        >
      </div>
      <div v-else>
        <p>{{ item.descriptionEN }}</p>
      </div>
    </NCard>
  </div>
  <!-- <template v-if="$page.props.app.locale === 'lt'"></template>
  <template v-if="$page.props.app.locale === 'en'"> </template> -->
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { NButton, NCard } from "naive-ui";

var items = [
  {
    titleLT: "Programa „Sąžiningai“",
    descriptionLT:
      "Unikali VU SA programa, puoselėjanti VU bendruomenės akademinę etiką bei vykdanti akademinio nesąžiningumo prevenciją.",
    description:
      "A unique VU SA program that fosters the academic ethics of the VU community and prevents academic dishonesty.",
    titleEN: 'Program "Sąžiningai',
    descriptionEN:
      "A unique VU SA program that fosters the academic ethics of the VU community and prevents academic dishonesty.",
    imageSource: null,
    link: "/saziningai",
  },
  {
    titleLT: "Programa „Be etikečių“",
    descriptionLT:
      "Nuo 2010-ųjų VU SA vykdoma programa, aktyviai veikianti socialiai pažeidžiamų grupių integracijos ir žmogaus teisių sferose.",
    titleEN: "No Labels Program",
    descriptionEN:
      "Since 2010, VU SA has been running a program actively operating in the spheres of integration of socially vulnerable groups and human rights.",
    imageSource: null,
    link: "/be-etikeciu",
  },
  {
    titleLT: "Kuratorių programa",
    descriptionLT:
      "VU SA programa, kurios dalyviai – vyresnių kursų savanoriai studentai – padeda pirmakursiams sklandžiai įsilieti į universiteto gyvenimą.",
    titleEN: "Curator program",
    descriptionEN:
      "The VU SA program, whose participants are volunteer students in their senior years, helps first-year students integrate smoothly into university life.",
    imageSource: null,
    link: "/kuratoriu-programa",
  },
  {
    titleLT: "ESN Vilnius University",
    descriptionLT:
      "VU SA programa, skatinanti studentų mobilumą bei padedanti užsienio studentams integruotis Vilniaus universitete.",
    titleEN: "ESN Vilnius University",
    descriptionEN:
      "VU SA program that promotes student mobility and helps foreign students integrate at Vilnius University.",
    imageSource: null,
    link: "/esn-vilnius-university",
  },
  {
    titleLT: "„Studentai – moksleiviams, moksleiviai – studentams“",
    descriptionLT:
      "VU SA stovykla, skirta vyresnių klasių mokiniams, norintiems iš arčiau susipažinti su VU ir juos dominančiomis studijomis jame.",
    titleEN: "SMMS program",
    descriptionEN:
      "The VU SA camp is intended for senior students who want to get to know VU and the studies they are interested in.",
    imageSource: null,
    link: "/studentai-–-moksleiviams-moksleiviai-–-studentams",
  },
  {
    titleLT: "Jaunųjų energetikų klubas",
    descriptionLT:
      "VU SA klubas, vienijantis jaunimą, besidomintį įvairių rūšių energetika ekonominiais, politiniais, techniniais ir kitais aspektais.",
    titleEN: "Club of Young Energetics",
    descriptionEN:
      "VU SA club uniting young people interested in various types of energy in economic, political, technical and other aspects.",
    imageSource: null,
    link: "/jaunuju-energetiku-klubas",
  },
  {
    titleLT: "Rašytojų klubas",
    descriptionLT:
      "VU SA klubas, vienijantis rašančius ir literatūra besidominčius Vilniaus universiteto studentus.",
    titleEN: "Writers club",
    descriptionEN:
      "VU SA club, which unites students of Vilnius University who write and are interested in literature.",
    imageSource: null,
    link: "/rasytoju-klubas",
  },
  {
    titleLT: "Kendo klubas",
    descriptionLT:
      "Nuo 2008 m. veikianti VU SA japoniško fechtavimo studija, skirta tiek pradedantiesiems, tiek norintiems tobulinti jau turimus įgūdžius.",
    titleEN: "Kendo Club",
    descriptionEN:
      "Since 2008 an active VU SA Japanese fencing studio for both beginners and those who want to improve their existing skills.",
    imageSource: null,
    link: "/kendo-klubas",
  },
  {
    titleLT: "Studentų investicinis fondas",
    descriptionLT:
      "Pirmasis ir didžiausias studentų investicinis fondas Lietuvoje, skatinantis jaunimą domėtis investicijų valdymu ir savarankiškai investuoti.",
    titleEN: "Student investment fund",
    descriptionEN:
      "The first and largest student investment fund in Lithuania, encouraging young people to be interested in investment management and to invest independently.",
    imageSource: null,
    link: "/studentu-investicinis-fondas",
  },
  {
    titleLT: "Teisės klinika",
    descriptionLT:
      "VU SA nemokamos teisinės pagalbos projektas. Konsultacijas teikia vyresniųjų kursų Vilniaus universiteto Teisės fakulteto studentai.",
    titleEN: "Law clinic",
    descriptionEN:
      "Free legal aid project of VU SA. Consultations are provided by senior year students of the Faculty of Law of Vilnius University.",
    imageSource: null,
    link: "/teises-klinika",
  },
  {
    titleLT: "Karjeros dienos",
    descriptionLT:
      "VU SA įgyvendinamas projektas, kuriuo siekiama padėti Vilniaus universiteto studentams įsitvirtinti darbo rinkoje.",
    titleEN: "Career days",
    descriptionEN:
      "VU SA is implementing a project that aims to help Vilnius University students establish themselves in the labor market.",
    imageSource: null,
    link: "/karjeros-dienos",
  },
  {
    titleLT: "„Studentiško gyvenimo vadovas“",
    descriptionLT:
      "Naujiems studentams skirtas nemokamas VU SA leidinys, kuriame pateikiama svarbiausia akademinė ir socialinė informacija.",
    titleEN: "Student Life Guidebook",
    descriptionEN:
      "VU SA's free publication for new students, which contains the most important academic and social information.",
    imageSource: null,
    link: "/studentisko-gyvenimo-vadovas",
  },
  {
    titleLT: "VU radijas „Start FM“",
    descriptionLT:
      "VU studentų radijas, siūlantis klausytojams kitokį požiūrį į muziką. „Start FM“ – vienintelis, kurio galima klausytis!",
    titleEN: "VU Radio Start FM",
    descriptionEN:
      "VU student radio offering listeners a different approach to music. Start FM is the only one to listen to!",
    imageSource: null,
    link: "/start-fm",
  },
  {
    titleLT: "VU Debatų klubas",
    descriptionLT:
      "VU studentų klubas puoselėjantis kritinį mąstymą bei gebėjimą racionaliai argumentuoti.",
    titleEN: "VU Debate Club",
    descriptionEN:
      "VU student club fostering critical thinking and the ability to reason rationally.",
    imageSource: null,
    link: "/debatu-klubas",
  },
  {
    titleLT: "Foto klubas",
    descriptionLT:
      "VU studentų klubas siekiantis atskleisti ir įamžinti mus supančias istorijas.",
    titleEN: "Photo club",
    descriptionEN:
      "The VU student club aims to reveal and perpetuate the stories around us.",
    imageSource: null,
    link: "/foto-klubas",
  },
  {
    titleLT: "Antropologų klubas",
    descriptionLT:
      "Klubas, jungiantis esamus, buvusius ir būsimus VU bendruomenės narius, kuriuos domina antropologija.",
    titleEN: "Club of Anthropologists",
    descriptionEN:
      "A club that connects current, former and future members of the VU community with an interest in anthropology.",
    imageSource: null,
    link: "/antropologu-klubas",
  },
  {
    titleLT: "VU Jaunųjų žurnalistų draugija",
    descriptionLT:
      "Burianti įvairių žurnalistinių interesų turinčius jaunuolius iš visos Lietuvos ir leidžianti jiems išpildyti savo kūrybinį potencialą praktikoje draugija.",
    titleEN: "VU Association of Young Journalists",
    descriptionEN:
      "A society that gathers young people with various journalistic interests from all over Lithuania and allows them to fulfill their creative potential in practice.",
    imageSource: null,
    link: "/jaunuju-zurnalistu-draugija",
  },
  {
    titleLT: "The COINS",
    descriptionLT:
      "Tarptautinė gyvybės mokslų konferencija, kuri yra organizuojama išskirtiniai vien tik studentų.",
    titleEN: "The COINS",
    descriptionEN:
      "An international conference on life sciences, which is organized exclusively by students.",
    imageSource: null,
    link: "/the-coins",
  },
  {
    titleLT: "HEMA",
    descriptionLT:
      "Ateik, išbandyk savo jėgas ir pasinerk į viduramžių pasaulį!",
    titleEN: "HEMA",
    descriptionEN:
      "Come, try your hand and immerse yourself in the medieval world!",
    imageSource: null,
    link: "/hema",
  },
];
</script>
