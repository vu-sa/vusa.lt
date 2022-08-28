<template>
  <Head :title="event.title"></Head>

  <FadeTransition appear>
    <article class="mx-auto mt-4">
      <header
        class="camp-header max-h-96"
        :class="{
          'py-12 text-white': !hasNoImage,
          'py-4': hasNoImage,
        }"
        :style="headerImageStyle"
      >
        <div class="mx-auto flex h-full max-w-7xl">
          <div class="h-fit self-end">
            <h1
              class="flex items-center px-12 text-4xl font-extrabold lg:text-5xl"
            >
              <span>{{
                $page.props.locale === "en"
                  ? event.attributes?.en?.title ?? event.title
                  : event.title
              }}</span>
            </h1>
          </div>
        </div>
      </header>
      <section class="mx-auto mt-8 grid max-w-7xl lg:grid-cols-3">
        <div class="px-12 lg:col-span-2">
          <h2 v-if="event.description !== ''" class="my-4">
            {{ $t("Aprašymas") }}
          </h2>
          <div
            class="prose-sm sm:prose sm:max-w-[70ch]"
            v-html="
              $page.props.locale === 'en'
                ? event.attributes?.en?.description ?? event.description
                : event.description
            "
          ></div>

          <iframe
            v-if="event.attributes?.video_url"
            class="mb-8 mt-4 aspect-video h-auto w-full rounded-2xl"
            width="560"
            height="315"
            :src="`https://www.youtube-nocookie.com/embed/${event.attributes?.video_url}`"
            title="YouTube video player"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
          ></iframe>

          <div class="mt-4">
            <NImageGroup :show-toolbar="false">
              <NSpace>
                <NImage
                  v-for="image in images"
                  :key="image.id"
                  width="150"
                  :src="image.original_url"
                  class="rounded-lg transition hover:shadow-md"
                  lazy
                />
              </NSpace>
            </NImageGroup>
          </div>
        </div>
        <div class="-order-1 mx-8 flex lg:order-1">
          <div
            style="grid-template-columns: 16px auto"
            class="sticky top-40 grid h-fit w-full max-w-lg grid-cols-2 flex-col items-center gap-2 rounded-2xl border-vusa-red p-4 lg:w-80 lg:border-2 lg:p-6 lg:shadow-md"
          >
            <div class="absolute top-6 right-6">
              <NButton
                v-if="event.attributes?.facebook_url"
                secondary
                size="small"
                circle
                @click="windowOpen(event.attributes?.facebook_url)"
                ><NIcon :component="FacebookF"></NIcon
              ></NButton>
            </div>
            <p v-if="false" class="col-span-2 mb-4 flex w-4/5 text-lg">
              {{
                $page.props.locale === "en"
                  ? event.attributes?.en?.title ?? event.title
                  : event.title
              }}
            </p>

            <NIcon :component="PeopleTeam28Regular"></NIcon>
            <span>
              {{ $t("Organizuoja") }}:
              <strong>{{ eventOrganizer }}</strong>
            </span>

            <NIcon :component="CalendarLtr24Regular"></NIcon>
            <span>
              {{ event.date }}
            </span>
            <template v-if="event.end_date">
              <NIcon :component="CalendarLtr24Filled"></NIcon>
              <span>
                {{ event.end_date }}
              </span>
            </template>
            <template v-if="event.location">
              <NIcon :component="Home32Regular"></NIcon>
              <span>{{
                $page.props.locale === "en"
                  ? event.attributes?.en?.location ?? event.location
                  : event.location
              }}</span>
            </template>

            <NDivider v-if="event.url" class="col-span-2"></NDivider>
            <div class="col-span-2 flex flex-col justify-center">
              <p v-if="timeTillEvent.days >= 0" class="text-center">
                Iki renginio liko:
              </p>

              <NGradientText
                v-if="timeTillEvent.days >= 0"
                type="error"
                class="mb-2 w-full text-center"
              >
                {{ timeTillEvent.days }} d.
                <NCountdown
                  :render="renderCountdown"
                  :active="true"
                  :duration="timeTillEvent.ms"
                ></NCountdown>
              </NGradientText>

              <NButton
                v-if="event.url"
                strong
                round
                type="primary"
                size="large"
                @click="windowOpen(event.url)"
                ><template #icon>
                  <NIcon :component="HatGraduation20Regular"></NIcon>
                </template>
                {{ $t("Dalyvauk") }}!
              </NButton>
              <div class="mt-4 w-fit lg:mx-auto">
                <NButton
                  size="small"
                  secondary
                  round
                  @click="windowOpen(googleLink)"
                >
                  <template #icon><NIcon :component="Google" /></template>
                  {{ $t("Įsidėk į Google kalendorių") }}</NButton
                >
              </div>
            </div>
          </div>
        </div>
      </section>
    </article>
  </FadeTransition>
</template>

<script lang="ts">
import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";

export default {
  layout: PublicLayout,
};
</script>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import {
  CalendarLtr24Filled,
  CalendarLtr24Regular,
  HatGraduation20Regular,
  Home32Regular,
  PeopleTeam28Regular,
} from "@vicons/fluent";
import {
  CountdownProps,
  NButton,
  NCountdown,
  NDivider,
  NGradientText,
  NIcon,
  NImage,
  NImageGroup,
  NSpace,
} from "naive-ui";
import { FacebookF, Google } from "@vicons/fa";
import { Head } from "@inertiajs/inertia-vue3";
import { computed, h } from "vue";

import FadeTransition from "@/Components/Public/Utils/FadeTransition.vue";

const props = defineProps<{
  event: App.Models.Calendar;
  images: Record<string, any> | null;
  googleLink: string;
}>();

// check if image array is empty
const hasNoImage = computed(() => {
  return props.images === null || props.images.length === 0;
});

const eventOrganizer = computed((): string => {
  return props.event.attributes?.organizer ?? props.event.padalinys.shortname;
});

const windowOpen = (url: string) => {
  window.open(url, "_blank");
};

const headerImageStyle = computed(() => {
  if (hasNoImage.value) {
    return null;
  }

  return {
    "background-image": `linear-gradient(0deg, rgba(0, 0, 0, 0.9), rgba(0, 0, 0, 0.3)), url(${
      props.images?.length > 0 ? props.images[0].original_url : ""
    })`,
  };
});

const timeTillEvent = computed(() => {
  const date = new Date(props.event.date.replace(/-/g, "/"));
  const now = new Date();
  // get full days till event
  const daysTillEvent = Math.floor(
    (date.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)
  );
  // get ms till event minus full days
  const msTillEvent =
    date.getTime() - now.getTime() - daysTillEvent * 1000 * 60 * 60 * 24;
  return {
    days: daysTillEvent,
    ms: msTillEvent,
  };
});

const renderCountdown: CountdownProps["render"] = ({
  hours,
  minutes,
  seconds,
}) => {
  return h("span", {}, [
    h("span", hours),
    " val. ",
    h("span", minutes),
    " min. ",
    h("span", seconds),
    " sek.",
  ]);
};
</script>

<style scoped>
.camp-header {
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

.carousel-img {
  width: 100%;
  height: 240px;
  object-fit: cover;
}
</style>
