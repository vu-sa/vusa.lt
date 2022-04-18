<template>
  <PublicLayout title="Pagrindinis">
    <div class="relative">
      <ShapeDivider1 class="absolute -top-1"></ShapeDivider1>
      <ShapeDivider1 class="absolute rotate-180 -bottom-1"></ShapeDivider1>
      <img
        src="/images/placeholders/foto3.jpg"
        class="h-64 lg:h-96 w-full object-cover my-4"
      />
    </div>
    <div class="lg:px-16 lg:mx-16 mx-8">
      <h2 class="text-3xl lg:text-5xl mb-4 text-gray-900">
        Pasižiūrėkite, ką nuveikėme 2022 metais 💡
      </h2>
      <p class="lg:w-[65ch] text-gray-700 mb-20 text-sm lg:text-base">
        Nuo nacionalinio atstovavimo studentų interesams iki naujų galimybių saviraiškai –
        Vilniaus universiteto Studentų atstovybė (čia mes 👋) apima daugiau nei čia tilptų
        parašyti. Daugiau neskaitykite, čia tik Lorem ipsum.
      </p>
    </div>
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
                newsString: 'naujiena',
                padalinys: item.alias,
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
              newsString: 'naujiena',
              padalinys: item.alias,
              permalink: item.permalink,
            })
          "
          >{{ item.title }}</Link
        >
      </HomeCard>
    </NewsElement>
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
import { Link, usePage } from "@inertiajs/inertia-vue3";
import { onMounted, ref } from "vue";

const props = defineProps({
  news: Object,
});

const alias = ref("");

onMounted(() => {
  alias.value = usePage().props.value.alias;
});
</script>
