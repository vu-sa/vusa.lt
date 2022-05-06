<template>
  <PublicLayout title="Pagrindinis">
    <div class="relative group">
      <ShapeDivider1 class="absolute -top-1 z-10"></ShapeDivider1>
      <ShapeDivider1 class="absolute rotate-180 -bottom-1 z-10"></ShapeDivider1>
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
          class="h-64 lg:h-96 w-full object-cover my-4 hover:opacity-90 duration-200"
          style="object-position: 0% 35%"
        />
      </Link>
    </div>
    <div class="lg:px-16 lg:mx-16 mx-8">
      <h2
        class="text-2xl lg:text-4xl mb-4 text-gray-900 hover:text-vusa-red hover:text- duration-200"
      >
        <Link
          class="flex flex-row w-fit gap-2 items-center"
          :href="
            route('main.ataskaita2022', {
              lang: $page.props.locale,
              permalink: 'pradzia',
            })
          "
          ><template v-if="$page.props.locale === 'lt'"
            >Ką veikė VU SA 2021–2022 metais?</template
          ><template v-else>What did VU SR do in 2021–2022?</template>

          <NIcon class="" style="font-size: 24pt"><ArrowCircleRight20Regular /></NIcon
        ></Link>
      </h2>
      <div class="flex space-between flex-row">
        <p class="lg:max-w-[80ch] text-gray-700 mb-8 lg:mb-20 text-sm lg:text-base">
          <template v-if="$page.props.locale === 'lt'">
            Ataskaitos knygutė – kiekvienais metais skelbiama VU SA nuveiktų darbų,
            atliktų projektų, įgyvendintų iniciatyvų ataskaita. Artėjant VU SA
            ataskaitinei-rinkiminei konferencijai dalinamės ir šių, 2021–2022 metų
            nuveiktų darbų pilna knygute. Ji papildyta ir VU SA bendruomenės narių veidais
            bei smagiomis akimirkomis. Kviečiame skaityti!
          </template>
          <template v-else
            >Report booklet – every year, a report on the work done, projects completed,
            and initiatives implemented at VU SR is published. As the VU SR Annual
            Convention approaches, we share a book full of these works done in 2021–2022.
            It is also filled with faces and fun moments of the VU SR community members.
            Feel free to read!</template
          >
        </p>
      </div>
    </div>
    <NewsElement v-if="$page.props.locale === 'lt'">
      <HomeCard
        :hasMiniContent="false"
        :hasBelowCard="true"
        v-for="item in news"
        :key="item.id"
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
              class="rounded-sm shadow-md hover:shadow-lg duration-200 h-40 w-full mb-1 object-cover"
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
    <div
      v-if="$page.props.locale === 'lt'"
      class="py-4 px-4 lg:px-8 lg:mx-16 mx-8 lg:mb-8 mb-4 rounded-lg text-gray-800 bg-white shadow-lg"
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
            <img class="shadow-md rounded-sm" :src="banner.image_url" />
          </a>
        </NCarouselItem>
      </NCarousel>
    </div>
  </PublicLayout>
</template>

<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue";
import HomeCard from "@/Components/Public/HomeCard.vue";
// import SkeletonElement from "@/Layouts/Partials/Public/SkeletonElement.vue";
import NewsElement from "@/Layouts/Partials/Public/NewsElement.vue";
import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";
import { NIcon, NCarousel, NButton, NCarouselItem } from "naive-ui";
import {
  CalendarLtr20Regular,
  ArrowCircleRight20Regular,
  Clock20Regular,
  ArrowRight48Regular,
} from "@vicons/fluent";
import { Link } from "@inertiajs/inertia-vue3";
import { onBeforeUnmount, ref } from "vue";
import { Inertia } from "@inertiajs/inertia";

const props = defineProps({
  news: Object,
  banners: Object,
});

const calculateBannerCount = (width) => {
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
  window.removeEventListener("resize", () => {});
});

// console.log(this.window.width);

// const alias = ref("");
</script>
