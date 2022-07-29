<template>
  <Head>
    <link
      rel="preload"
      href="/images/ataskaita2022/kitos-nuotraukos/VU SA.jpg"
      as="image"
    />
  </Head>

  <PublicLayout title="Pagrindinis">
    <div v-if="$page.props.locale === 'lt'">
      <div class="group relative">
        <ShapeDivider1 class="absolute -top-1 z-10"></ShapeDivider1>
        <ShapeDivider1
          class="absolute -bottom-2 z-10 rotate-180 lg:-bottom-1"
        ></ShapeDivider1>
        <div class="relative">
          <img
            src="/images/photos/stovykla.jpg"
            class="mt-2 h-32 w-full object-cover brightness-50 lg:my-1 lg:h-64"
            style="object-position: 0% 45%"
          />
          <!-- <h1 class="relative bottom-16 text-center text-white lg:bottom-24">
            Pirmakursių stovyklos
          </h1> -->
        </div>
      </div>
      <div
        class="mx-8 mt-4 flex flex-col justify-center gap-4 lg:mx-16 lg:flex-row lg:items-center lg:px-16"
      >
        <div class="prose-sm sm:prose">
          <p class="text-2xl font-bold lg:w-4/5">
            VU SA organizuojamos
            <span class="font-extrabold text-vusa-red"
              >pirmakursių stovyklos</span
            >
            jau ne už kalnų!
          </p>
          <p class="w-4/5">
            Daugiau informacijos ieškok savo padalinio pirmakursių stovyklų
            polapyje (arba paklausk kuratoriaus 🤚)!
          </p>
        </div>
        <div class="flex flex-wrap gap-2 lg:justify-center">
          <NButton
            round
            size="large"
            strong
            type="primary"
            @click="Inertia.visit(route('pirmakursiuStovyklos'))"
            >Į stovyklas!<template #icon
              ><NIcon :component="HatGraduation20Regular"></NIcon></template
          ></NButton>
          <NButton
            round
            size="large"
            strong
            secondary
            @click="
              Inertia.visit(
                route('main.contacts.category', {
                  alias: 'padaliniai',
                  lang: 'lt',
                })
              )
            "
            >Surask savo kuratorių!<template #icon
              ><NIcon :component="Person20Filled"></NIcon></template
          ></NButton>
        </div>
      </div>
      <NDivider />
    </div>

    <div
      v-if="$page.props.locale === 'lt' && $page.props.alias"
      class="mx-8 mb-8 lg:mx-16 lg:px-16"
    >
      <h2 class="mb-4">Pagrindinės nuorodos:</h2>
      <div class="flex flex-wrap gap-2">
        <NButton
          v-for="item in props.mainPage"
          :key="item.id"
          secondary
          size="small"
          round
          @click="goToLink(item.link)"
        >
          {{ item.text }}
        </NButton>
      </div>
    </div>

    <NewsElement v-if="$page.props.locale === 'lt'">
      <HomeCard
        v-for="item in news"
        :key="item.id"
        :has-mini-content="false"
        :has-below-card="true"
      >
        <template #mini> </template>
        <template #below-card>
          <!-- <NIcon class="mr-2" size="20"> <CalendarLtr20Regular /> </NIcon>VU SA
          ataskaitinė-rinkiminė konferencija -->
          <NIcon class="mr-2" size="20"> <Clock20Regular /> </NIcon
          >{{ item.publish_time }}
        </template>
        <template #image>
          <Link
            :href="
              route('news', {
                lang: item.lang,
                newsString: 'naujiena',
                padalinys: item.alias,
                permalink: item.permalink,
              })
            "
            ><img
              :src="item.image"
              class="mb-1 h-40 w-full rounded-sm object-cover shadow-md duration-200 hover:shadow-lg"
          /></Link>
        </template>
        <Link
          :href="
            route('news', {
              lang: item.lang,
              newsString: 'naujiena',
              padalinys: item.alias,
              permalink: item.permalink,
            })
          "
          >{{ item.title }}</Link
        >
      </HomeCard>
    </NewsElement>
    <div class="group relative">
      <ShapeDivider1 class="absolute -top-1 z-10"></ShapeDivider1>
      <ShapeDivider1 class="absolute -bottom-1 z-10 rotate-180"></ShapeDivider1>
      <Link
        :href="
          route('main.ataskaita2022', {
            lang: $page.props.locale,
            permalink: 'pradzia',
          })
        "
      >
        <img
          src="/images/ataskaita2022/kitos-nuotraukos/VU SA.jpg"
          class="my-4 h-48 w-full object-cover duration-200 hover:opacity-90 lg:h-80"
          style="object-position: 0% 35%"
        />
      </Link>
    </div>
    <div class="mx-8 max-w-[100ch] lg:mx-16 lg:px-16">
      <h2
        class="mb-4 text-lg font-bold text-gray-900 no-underline duration-200 hover:text-vusa-red lg:text-3xl"
      >
        <Link
          class="flex w-fit flex-row items-center gap-2"
          :href="
            route('main.ataskaita2022', {
              lang: $page.props.locale,
              permalink: 'pradzia',
            })
          "
          ><template v-if="$page.props.locale === 'lt'"
            >Ką veikė VU SA 2021–2022 metais?</template
          ><template v-else>What did VU SR do in 2021–2022?</template>

          <NIcon class="" style="font-size: 24pt">
            <ArrowCircleRight20Regular />
          </NIcon>
        </Link>
      </h2>
      <div class="space-between flex flex-row">
        <p
          class="prose-sm mb-4 text-sm text-gray-700 sm:prose lg:max-w-[80ch] lg:text-base"
        >
          <template v-if="$page.props.locale === 'lt'">
            Ataskaitos knygutė – kiekvienais metais skelbiama VU SA nuveiktų
            darbų, atliktų projektų, įgyvendintų iniciatyvų ataskaita. Artėjant
            VU SA ataskaitinei-rinkiminei konferencijai dalinamės ir šių,
            2021–2022 metų nuveiktų darbų pilna knygute. Ji papildyta ir VU SA
            bendruomenės narių veidais bei smagiomis akimirkomis. Kviečiame
            skaityti!
          </template>
          <template v-else
            >Report booklet – every year, a report on the work done, projects
            completed, and initiatives implemented at VU SR is published. As the
            VU SR Annual Convention approaches, we share a book full of these
            works done in 2021–2022. It is also filled with faces and fun
            moments of the VU SR community members. Feel free to read!</template
          >
        </p>
      </div>
      <NDivider />
    </div>
    <div
      v-if="$page.props.locale === 'lt'"
      class="mx-8 mb-4 rounded-lg bg-white p-4 text-gray-800 shadow-lg lg:mx-16 lg:mb-8 lg:px-8"
    >
      <h1 class="mb-2">Mūsų programos ir partneriai</h1>
      <NCarousel
        :space-between="30"
        :loop="true"
        autoplay
        :slides-per-view="bannerCount"
        draggable
        :interval="2000"
        :show-dots="false"
      >
        <NCarouselItem v-for="banner in props.banners" :key="banner.id">
          <a target="_blank" :href="banner.link_url">
            <img class="rounded-sm shadow-md" :src="banner.image_url" />
          </a>
        </NCarouselItem>
      </NCarousel>
    </div>
  </PublicLayout>
</template>

<script setup lang="ts">
import {
  ArrowCircleRight20Regular,
  Clock20Regular,
  HatGraduation20Regular,
  Person20Filled,
} from "@vicons/fluent";
import { Head, Link, usePage } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import { NButton, NCarousel, NCarouselItem, NDivider, NIcon } from "naive-ui";
import { onBeforeUnmount, ref } from "vue";
import route from "ziggy-js";

import HomeCard from "@/Components/Public/HomeCard.vue";
import NewsElement from "@/Components/Public/NewsElement.vue";
import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";
import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";

const props = defineProps<{
  news: Array<App.Models.News>;
  banners: Array<App.Models.Banner>;
  mainPage: Array<App.Models.MainPage>;
}>();

const calculateBannerCount = (width: number) => {
  if (width < 768) {
    return 1;
  } else if (width < 992) {
    return 2;
  } else if (width < 1200) {
    return 3;
  } else {
    return 5;
  }
};

const bannerCount = ref(calculateBannerCount(window.innerWidth));

window.addEventListener("resize", () => {
  bannerCount.value = calculateBannerCount(window.innerWidth);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", () => {
    return;
  });
});

const goToLink = (link: string) => {
  // check if link is external
  let padalinysAlias = usePage().props.value.alias;
  if (link.includes("http")) {
    window.open(link, "_blank");
  }
  // if has /lt/, truncate it
  if (link.includes("/lt/")) {
    link = link.replace("/lt/", "");
  }

  if (padalinysAlias === "vusa") {
    // if first char is /, remove it
    if (link.charAt(0) === "/") {
      link = link.substring(1);
    }
    Inertia.visit(route("main.page", { lang: "lt", permalink: link }));
  } else {
    Inertia.visit(
      route("padalinys.page", {
        lang: "lt",
        permalink: link,
        padalinys: padalinysAlias,
      })
    );
  }
};

// console.log(this.window.width);

// const alias = ref("");
</script>
