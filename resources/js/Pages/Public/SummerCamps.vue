<template>
  <PublicLayout title="Pirmakursių stovyklos">
    <!-- Create a modern card with picture and title with Tailwind -->

    <div class="group relative">
      <ShapeDivider1 class="absolute -top-1 z-10"></ShapeDivider1>
      <ShapeDivider1
        class="absolute bottom-5 z-10 rotate-180 lg:-bottom-1"
      ></ShapeDivider1>
      <div class="relative">
        <img
          src="/images/photos/stovykla.jpg"
          class="mt-2 h-32 w-full object-cover brightness-50 lg:my-1 lg:h-48"
          style="object-position: 0% 45%"
        />
        <h1 class="relative bottom-16 text-center text-white lg:bottom-24">
          Pirmakursių stovyklos
        </h1>
      </div>
    </div>
    <div
      class="mx-auto grid max-w-7xl grid-cols-1 gap-4 px-4 pt-2 last:pb-2 lg:grid-cols-5 lg:px-16"
    >
      <div class="prose-sm col-span-3 px-12 sm:prose">
        <h2>Labas! 👋</h2>

        <p>
          Egzaminai išlaikyti, pakvietimai studijuoti jau išsiųsti, studijų
          sutartys pasirašytos – tad dabar prasideda pats įdomiausias gyvenimo
          etapas! Džiaugiamės, jog pasirinkai <strong>Universitetą</strong>, kur
          <em> Hinc itur ad astra </em> – iš čia kylama į žvaigždes.
        </p>

        <p>
          Prieš prasidedant mokslo metams Tavęs laukia ilgas, bet labai įdomus
          susipažinimo su Vilniaus universitetu etapas.
        </p>

        <p>
          Tačiau dar prieš tai,
          <a target="_blank" href="/apie"
            >Vilniaus universiteto Studentų atstovybė (VU SA)</a
          >
          kviečia Tave susipažinti su tais, kurie per visus mokslo metus lydės
          daugiausiai – tai Tavo padalinio, kurso, grupės draugai bei, žinoma,
          <a
            target="_blank"
            :href="
              route('contacts.category', {
                alias: 'padaliniai',
                lang: 'lt',
              })
            "
            >kuratoriai</a
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
      </div>

      <div
        class="-order-1 col-span-2 flex flex-wrap justify-center gap-6 px-12 lg:order-1 lg:px-0"
      >
        <section
          v-for="event in events"
          :key="event.id"
          class="group h-fit w-48 rounded-b-md bg-white/0"
        >
          <Link :href="route('calendar.event', event.id)">
            <img
              class="rounded-xl object-cover shadow-md transition group-hover:shadow-xl"
              :src="get5thResponsiveImage(event)"
            />
            <h3 class="p-2 text-center text-lg font-extrabold leading-tight">
              {{ "VU" + getFacultyName(event) }}
            </h3>
          </Link>
        </section>
      </div>
    </div>
  </PublicLayout>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";
import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";

const props = defineProps<{
  events: App.Models.News;
}>();

const getFacultyName = (event: App.Models.Calendar) => {
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

  return facultyName;
};

const get5thResponsiveImage = (event: App.Models.Calendar) => {
  if (event.media.length === 0) return "";

  let mainUrl = event.media[0].original_url;
  let fileName = event.media[0].file_name;

  // strsplit main url by filename
  let mainUrlParts = mainUrl.split(fileName);
  // add /responsive_images/ and event.media[0].responsive_images.media_library_original.urls[5] to main url
  let responsiveUrl =
    mainUrlParts[0] +
    "responsive-images/" +
    event.media[0].responsive_images.media_library_original.urls[5];
  return responsiveUrl;
};
</script>
