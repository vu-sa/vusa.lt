<template>
  <HeaderWithShapeDivider1 class="full-bleed" image-src="/images/photos/stovykla.jpg">
    {{ summerCampTitle }}
  </HeaderWithShapeDivider1>

  <div class="grid grid-cols-1 gap-4 pt-2 last:pb-2 lg:grid-cols-5">
    <div class="typography col-span-3 text-base leading-7">
      <template v-if="year === new Date().getFullYear()">
        <h2>{{ $t("Labas, būsimas (-a) studente!") }}</h2>

        <p class="font-bold">
          {{ $t("Įstojai į Vilniaus universitetą? Nepraleisk pirmojo studentiško nuotykio – pirmakursių stovyklos!") }}
        </p>

        <p>
          {{ $t("Džiaugiamės, kad pradėjai įdomiausią gyvenimo etapą ir pasirinkai Universitetą, kur Hinc itur ad astra – iš čia kylama į žvaigždes.") }}
        </p>

        <p>
          {{ $t("Prieš prasidedant mokslo metams Tavęs laukia ilgas, bet labai įdomus") }}
          <a class="font-bold underline" target="_blank" href="/pirmakursiams">{{ $t("susipažinimo") }}</a>
          {{ $t("su Vilniaus universitetu etapas.") }}
        </p>

        <p>
          {{ $t("Tačiau dar prieš tai,") }}
          <a class="font-bold underline" target="_blank" href="/apie">{{ $t("Vilniaus universiteto Studentų atstovybė (VU SA)") }}</a>
          {{ $t("kviečia Tave susipažinti su tais (-omis), kurie (-ios) per visus mokslo metus lydės daugiausiai – tai Tavo padalinio,") }}
          <Link
            class="font-bold underline" target="_blank" :href="route('contacts.category', {
              type: 'padaliniai',
              lang: 'lt',
              subdomain: 'www',
            })
            "
          >
            {{ $t("kuratoriai (-ės)") }}
          </Link>.
        </p>

        <p>
          {{ $t("Tai puiki galimybė ne tik praplėsti pažinčių ratą, bet ir gauti atsakymus į visus rūpimus klausimus, susijusius su studijomis ar studentišku gyvenimu. Ne iš nuogirdų, interneto platybių ar reklaminių lankstinukų, o iš pirmų lūpų – lygiai tą pačią studijų programą pasirinkusių vyresnių kursų studentų (-čių).") }}
        </p>

        <p>
          {{ $t("Net kelias dienas truksiančioje pirmakursių stovykloje susirasi bendraminčių visam likusiam gyvenimui – todėl negali pražiopsoti kvietimo įsilieti į VU bendruomenę dar net neprasidėjus studijoms!") }}
        </p>

        <p class="font-bold">
          {{ $t("Bilietų prekyba ir tikslesnė informacija bus paskelbta vėliau! Į kainą įskaičiuotas transportas į ir iš stovyklos. 🚌") }}
        </p>

        <p class="font-bold">
          {{ $t("Sek savo padalinio socialinius tinklus ir sužinok pirmas (-a)!") }}
        </p>

        <a target="_blank" href="https://vu.lt/parduotuve/" aria-label="Visit VU merchandise store">
          <img src="/images/photos/atributika_banner3.jpg" alt="VU merchandise and accessories banner" loading="lazy">
        </a>
      </template>
      <template v-else>
        <div class="mb-4 inline-flex items-center justify-center gap-3">
          <SmartLink
            :href="route('pirmakursiuStovyklos', {
              year: null,
              lang: 'lt',
            })
            "
          >
            <NButton round quartenary size="small">
              <template #icon>
                <i-fluent-arrow-left-24-filled />
              </template>
              {{ $t("Grįžti") }}
            </NButton>
          </SmartLink>
          <h3 class="mb-0">
            {{ year }} {{ $t("m. pirmakursių stovyklos") }}
          </h3>
        </div>
        <p>
          {{ $t("Pirmakursių stovyklos - tai ilgametes tradicijas turintis Vilniaus universiteto studentų atstovybės organizuojamas renginys VU pirmakursiams (-ėms), kuris vyksta kiekvienais metais.") }}
        </p>

        <SmartLink
          :href="route('pirmakursiuStovyklos', {
            year: null,
            lang: 'lt',
          })
          "
        />

        <a target="_blank" href="https://vu.lt/parduotuve/" aria-label="Visit VU merchandise store">
          <img src="/images/photos/atributika_banner2.jpg" alt="VU merchandise and accessories banner" loading="lazy">
        </a>
      </template>
      <h3 class="mt-6">
        {{ $t("Stovyklos pagal metus") }}
      </h3>
      <div class="flex gap-4">
        <SmartLink
          v-for="eventsYear in yearsWhenEventsExist" :key="eventsYear" :href="route('pirmakursiuStovyklos', {
            year: eventsYear,
            lang: 'lt',
          })
          "
        >
          <NButton round tertiary>
            {{ eventsYear }}
          </NButton>
        </SmartLink>
      </div>
    </div>

    <div class="-order-1 col-span-2 flex flex-wrap justify-center gap-6 px-12 lg:order-1 lg:content-start lg:px-0">
      <section v-for="event in events" :key="event.id" class="group h-fit w-48 rounded-b-md bg-white/0">
        <SmartLink
          :href="route('calendar.event', {
            calendar: event.id,
            lang: 'lt',
            subdomain: 'www',
          })
          "
        >
          <div v-if="get5thResponsiveImage(event)">
            <img
              class="size-full rounded-xl object-cover shadow-md transition group-hover:shadow-xl"
              :src="get5thResponsiveImage(event)"
              :alt="`VU ${$t(getFacultyNameInLocative(event.tenant))} freshmen camp`"
              loading="lazy"
            >
            <h3 class="p-2 text-center text-lg font-extrabold leading-tight">
              {{ "VU " + $t(getFacultyNameInLocative(event.tenant)) }}
            </h3>
          </div>
          <div v-else>
            <h3 class="p-2 text-center text-xl font-extrabold leading-tight">
              {{ "VU " + $t(getFacultyNameInLocative(event.tenant)) }}
            </h3>
          </div>
        </SmartLink>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";
import { trans as $t } from "laravel-vue-i18n";

import { getFacultyName } from "@/Utils/String";
import HeaderWithShapeDivider1 from "@/Components/Headers/HeaderWithShapeDivider1.vue";
import SmartLink from "@/Components/Public/SmartLink.vue";

const props = defineProps<{
  events: App.Entities.News;
  year: number;
  yearsWhenEventsExist: number[]
}>();

const summerCampTitle = computed(() => {
  return props.year === new Date().getFullYear() ? $t("Pirmakursių stovyklos") : `${props.year} ${$t("m. pirmakursių stovyklos")}`;
});

const getFacultyNameInLocative = ({ fullname }: { fullname: string }) => {
  // Extract the faculty name part in locative form (before conversion to nominative)
  let facultyName = fullname.split("Vilniaus universiteto Studentų atstovybė")[1];
  
  if (facultyName === undefined) {
    return "";
  }
  
  return facultyName.trim();
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
</script>
