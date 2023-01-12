<template>
  <Head title="Pirmakursių stovyklos" />

  <FadeTransition appear>
    <div>
      <div class="group relative">
        <ShapeDivider1
          :is-theme-dark="isThemeDark"
          class="absolute -top-1 z-10"
        ></ShapeDivider1>
        <ShapeDivider1
          :is-theme-dark="isThemeDark"
          class="absolute bottom-5 z-10 rotate-180 lg:-bottom-1"
        ></ShapeDivider1>
        <div class="relative">
          <img
            src="/images/photos/stovykla.jpg"
            class="mt-2 h-32 w-full object-cover brightness-50 lg:my-1 lg:h-48"
            style="object-position: 0% 45%"
          />
          <h1 class="relative bottom-16 text-center text-zinc-100 lg:bottom-24">
            Pirmakursių stovyklos
          </h1>
        </div>
      </div>
      <div
        class="mx-auto grid max-w-7xl grid-cols-1 gap-4 px-4 pt-2 last:pb-2 lg:grid-cols-5 lg:px-16"
      >
        <div class="prose prose-sm col-span-3 px-12 dark:prose-invert">
          <h2>Labas! 👋</h2>

          <p class="font-bold">2022 m. pirmakursių stovyklos jau pasibaigė!</p>

          <p>
            Egzaminai išlaikyti, pakvietimai studijuoti jau išsiųsti, studijų
            sutartys pasirašytos – tad dabar prasideda pats įdomiausias gyvenimo
            etapas! Džiaugiamės, jog pasirinkai <strong>Universitetą</strong>,
            kur <em> Hinc itur ad astra </em> – iš čia kylama į žvaigždes.
          </p>

          <p>
            Prieš prasidedant mokslo metams Tavęs laukia ilgas, bet labai įdomus
            <a class="font-bold underline" target="_blank" href="/pirmakursiams"
              >susipažinimo</a
            >
            su Vilniaus universitetu etapas.
          </p>

          <p>
            Tačiau dar prieš tai,
            <a class="font-bold underline" target="_blank" href="/apie"
              >Vilniaus universiteto Studentų atstovybė (VU SA)</a
            >
            kviečia Tave susipažinti su tais, kurie per visus mokslo metus lydės
            daugiausiai – tai Tavo padalinio, kurso, grupės draugai bei, žinoma,
            <Link
              class="font-bold underline"
              target="_blank"
              :href="
                route('contacts.category', {
                  alias: 'padaliniai',
                  lang: 'lt',
                })
              "
              >kuratoriai</Link
            >.
          </p>

          <p>
            Tai puiki galimybė ne tik praplėsti pažinčių ratą, bet ir gauti
            atsakymus į visus rūpimus klausimus, susijusius su studijomis ar
            studentišku gyvenimu. Ne iš
            <em> nuogirdų, interneto ar reklaminių lankstinukų, </em>
            o iš pirmų lūpų – lygiai tą pačią studijų programą pasirinkusių
            vyresnių kursų studentų (-čių).
          </p>

          <p>
            Net kelias dienas truksiančioje pirmakursių stovykloje susirasi
            bendraminčių bei draugų visam likusiam gyvenimui – tad nevalia
            pražiopsoti kvietimo įsilieti į VU bendruomenę dar net neprasidėjus
            studijoms!
          </p>

          <p>
            Daugiau informacijos rasi savo padalinio polapyje. Susimatome jau
            greitai!
          </p>

          <a target="_blank" href="https://vu.lt/parduotuve/"
            ><img src="/images/photos/atributika_banner2.jpg"
          /></a>
        </div>

        <div
          class="-order-1 col-span-2 flex flex-wrap justify-center gap-6 px-12 lg:order-1 lg:content-start lg:px-0"
        >
          <section
            v-for="event in events"
            :key="event.id"
            class="group h-fit w-48 rounded-b-md bg-white/0"
          >
            <Link :href="route('calendar.event', event.id)">
              <img
                class="h-full w-full rounded-xl object-cover shadow-md transition group-hover:shadow-xl"
                :src="get5thResponsiveImage(event)"
              />
              <h3 class="p-2 text-center text-lg font-extrabold leading-tight">
                {{ "VU" + getFacultyName(event) }}
              </h3>
            </Link>
          </section>
        </div>
      </div>
    </div>
  </FadeTransition>
</template>



<script setup lang="ts">
import { Head } from "@inertiajs/inertia-vue3";
import { Link } from "@inertiajs/inertia-vue3";
import { onMounted, ref } from "vue";


import { isDarkMode, updateDarkMode } from "@/Composables/darkMode";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";

defineProps<{
  events: App.Entities.News;
}>();

const isThemeDark = ref(isDarkMode());

const getFacultyName = (event: App.Entities.Calendar) => {
  if (!event.padalinys) return "";

  // split string into two parts, separated by string "Vilniaus universiteto Studentų atstovybė"
  let facultyName = event.padalinys.fullname.split(
    "Vilniaus universiteto Studentų atstovybė"
  )[1];

  // change faculty name only at the string ending from "ete" to "etas"
  if (facultyName.endsWith("ete")) {
    facultyName = facultyName.replace("ete", "etas");
  }
  // also apply this to "tre" to "tas"
  if (facultyName.endsWith("tre")) {
    facultyName = facultyName.replace("tre", "tras");
  }

  // also if ends with "ykloje", change to "ykla"
  if (facultyName.endsWith("ykloje")) {
    facultyName = facultyName.replace("ykloje", "ykla");
  }

  // change "ute" to "utas"
  if (facultyName.endsWith("ute")) {
    facultyName = facultyName.replace("ute", "utas");
  }

  return facultyName;
};

const get5thResponsiveImage = (event: App.Entities.Calendar) => {
  if (event.media.length === 0) return "";

  let mainUrl = event.media[0].original_url;
  let fileName = event.media[0].file_name;

  // strsplit main url by filename
  let mainUrlParts = mainUrl.split(fileName);
  // add /responsive_images/ and event.media[0].responsive_images.media_library_original.urls[5] to main url
  let responsiveUrl =
    mainUrlParts[0] +
    "responsive-images/" +
    event.media[0].responsive_images.media_library_original.urls[
      event.media[0].responsive_images.media_library_original.urls.length - 3
    ];
  return responsiveUrl;
};

onMounted(() => {
  updateDarkMode(isThemeDark);
});
</script>
