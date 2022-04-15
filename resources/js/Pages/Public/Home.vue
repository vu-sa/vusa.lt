<template>
  <PublicLayout>
    <NewsElement>
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
                news: 'naujiena',
                permalink: item.permalink,
              })
            "
            ><ImageForCard :src="item.image"
          /></Link>
        </template>
        <Link
          :href="
            route('news', {
              lang: item.lang,
              news: 'naujiena',
              permalink: item.permalink,
            })
          "
          >{{ item.title }}</Link
        >
      </HomeCard>
    </NewsElement>
    <div class="relative">
      <ShapeDivider1 class="absolute -top-1"></ShapeDivider1>
      <ShapeDivider1 class="absolute rotate-180 -bottom-1"></ShapeDivider1>
      <img
        src="/images/photos/observatorijos_kiemelis.jpg"
        class="h-96 w-full object-cover my-8"
      />
    </div>
    <div class="px-16 mx-16">
      <h2 class="text-5xl mb-4 text-gray-900">
        Pasižiūrėkite, ką nuveikėme per metus 💡
      </h2>
      <p class="w-3/5 text-gray-700 mb-20 text-base leading-tight">
        Nuo nacionalinio atstovavimo studentų interesams iki naujų galimybių saviraiškai –
        Vilniaus universiteto Studentų atstovybė (čia mes 👋) apima daugiau nei čia tilptų
        parašyti. Daugiau neskaitykite, čia tik Lorem ipsum.
      </p>
    </div>
  </PublicLayout>
</template>

<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue";
import HomeCard from "@/Components/Public/HomeCard.vue";
import ImageForCard from "@/Components/Public/ImageForCard.vue";
import SkeletonElement from "@/Layouts/Partials/Public/SkeletonElement.vue";
import NewsElement from "@/Layouts/Partials/Public/NewsElement.vue";
import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";
import { NIcon, NButton } from "naive-ui";
import {
  CalendarLtr20Regular,
  Clock20Regular,
  ArrowRight48Regular,
} from "@vicons/fluent";
import { Link } from "@inertiajs/inertia-vue3";

const props = defineProps({
  news: Object,
});
</script>
