<template>
  <HomeCard :has-mini-content="false" :has-below-card="true">
    <template #mini />
    <template #below-card>
      <!-- <NIcon class="mr-2" size="20"> <CalendarLtr20Regular /> </NIcon>VU SA
                ataskaitinė-rinkiminė konferencija -->
      <IFluentClock20Regular class="mr-1" />
      {{ formatStaticTime(new Date(news.publish_time), { year: "numeric", month: "long", day: "numeric" },
        $page.props.app.locale) }}
    </template>
    <template #image>
      <img :src="news.image" class="mb-1 h-40 w-full rounded-sm object-cover shadow-md duration-200 hover:shadow-lg">
    </template>
    <span class="hover:text-vusa-red duration-200">{{ news.title }}</span>
  </HomeCard>
</template>

<script setup lang="ts">
import { formatStaticTime } from "@/Utils/IntlTime";
import HomeCard from "../Public/HomeCard.vue";

defineProps<{
  news: App.Entities.News;
}>();
</script>
