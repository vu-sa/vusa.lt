<template>
  <PublicLayout :title="event.title">
    <article>
      <header
        class="camp-header h-60 py-6 px-10 text-white lg:py-12 lg:px-20"
        :style="headerImageStyle"
      >
        <div class="mx-auto flex h-full max-w-7xl">
          <div class="h-fit self-end">
            <h1
              class="flex items-center text-2xl font-extrabold lg:px-12 lg:text-5xl"
            >
              <span>{{ event.title }}</span>
            </h1>
          </div>
        </div>
      </header>
      <section class="mx-auto mt-8 grid max-w-7xl lg:grid-cols-3">
        <div class="px-12 lg:col-span-2">
          <h2 class="my-4">Kas čia vyks?</h2>
          <div
            class="prose-sm sm:prose sm:max-w-[70ch]"
            v-html="event.description"
          ></div>
          <h2 class="mt-8 mb-4">Kaip tai vyko anksčiau:</h2>
          <iframe
            v-if="event.attributes?.video_url"
            class="mb-8 aspect-video h-auto w-full rounded-2xl"
            width="560"
            height="315"
            :src="`https://www.youtube-nocookie.com/embed/${event.attributes?.video_url}`"
            title="YouTube video player"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
          ></iframe>

          <NImageGroup :show-toolbar="false">
            <NSpace>
              <NImage
                v-for="image in images"
                :key="image.id"
                width="150"
                :src="image.original_url"
                class="rounded-lg transition hover:shadow-md"
                alt="Event image"
              />
            </NSpace>
          </NImageGroup>
          <NDivider></NDivider>
          <h2
            class="my-6 flex flex-col justify-between gap-2 lg:flex-row lg:items-center"
          >
            <span>Turi klausimų? Paklausk savo kuratoriaus!</span
            ><a
              class="inline-flex items-center gap-2 text-sm font-normal"
              target="_blank"
              :href="
                route('padalinys.contacts.alias', {
                  alias: 'kuratoriai',
                  lang: 'lt',
                  padalinys: event.padalinys.alias,
                })
              "
              ><span class="whitespace-pre lg:w-fit"
                >Visi {{ event.padalinys.shortname }} kuratoriai</span
              >
              <NIcon :size="16" :component="ArrowCircleRight24Regular"></NIcon
            ></a>
          </h2>
          <NCarousel
            class="hidden xl:block"
            style="height: 280px"
            :slides-per-view="2"
            :space-between="15"
            :loop="true"
            autoplay
            :interval="2000"
            :show-dots="false"
            draggable
          >
            <template v-for="duty in curatorDuties" :key="duty.id">
              <ContactWithPhotoForUsers
                v-for="contact in duty.users"
                :key="contact.id"
                :contact="contact"
                :duty="duty"
              ></ContactWithPhotoForUsers> </template
          ></NCarousel>
        </div>
        <div class="-order-1 mx-8 flex justify-center lg:order-1">
          <div
            style="grid-template-columns: min-content"
            class="sticky top-40 grid h-fit w-full grid-cols-2 flex-col items-center gap-2 rounded-2xl border-2 border-vusa-red p-6 shadow-md lg:w-80"
          >
            <p class="col-span-2 mb-4 flex font-bold">
              <span class="w-fit">{{ event.title }}</span>
              <NButton
                v-if="event.attributes.facebook_url"
                text
                target="_blank"
                tag="a"
                :href="event.attributes.facebook_url"
                ><template #icon
                  ><NIcon :component="FacebookF"></NIcon></template
              ></NButton>
            </p>

            <NIcon :component="PeopleTeam28Regular"></NIcon>
            <span>
              Organizuoja:
              <strong>{{ event.padalinys.shortname }}</strong>
            </span>

            <NIcon :component="CalendarLtr24Regular"></NIcon>
            <span>
              {{ computedEventDateRange }}
            </span>
            <NIcon :component="Home32Regular"></NIcon>
            <span>{{ event.location }}</span>

            <NDivider class="col-span-2"></NDivider>
            <div class="col-span-2 flex flex-col justify-center">
              <p class="text-center">Iki stovyklos liko:</p>

              <NGradientText type="error" class="mb-2 w-full text-center">
                {{ timeTillEvent.days }} d.
                <NCountdown
                  :render="renderCountdown"
                  :active="true"
                  :duration="timeTillEvent.ms"
                ></NCountdown>
              </NGradientText>

              <NButton
                :disabled="!event.url"
                strong
                round
                type="primary"
                size="large"
                @click="openEventUrl"
                ><template v-if="event.url" #icon>
                  <NIcon :component="HatGraduation20Regular"></NIcon> </template
                >{{
                  event.url ? "Dalyvauk!" : "Registracija tuoj atsidarys..."
                }}</NButton
              >
            </div>
          </div>
        </div>
      </section>
    </article>
  </PublicLayout>
</template>

<script setup lang="ts">
import {
  ArrowCircleRight24Regular,
  CalendarLtr24Regular,
  HatGraduation20Regular,
  Home32Regular,
  PeopleTeam28Regular,
} from "@vicons/fluent";
import {
  CountdownProps,
  NButton,
  NCarousel,
  NCountdown,
  NDivider,
  NGradientText,
  NIcon,
  NImage,
  NImageGroup,
  NSpace,
} from "naive-ui";
import { FacebookF } from "@vicons/fa";
import { computed, h } from "vue";
import ContactWithPhotoForUsers from "@/Components/Public/ContactWithPhotoForUsers.vue";
import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";
import route from "ziggy-js";

const props = defineProps<{
  event: App.Models.Calendar;
  images?: Record<string, any> | null;
  curatorDuties: Array<App.Models.Duty>;
}>();

const headerImageStyle = computed(() => {
  return {
    "background-image": `linear-gradient(0deg, rgba(0, 0, 0, 0.9), rgba(0, 0, 0, 0.3)), url(${
      props.images?.length > 0 ? props.images[0].original_url : ""
    })`,
  };
});

// compute event datetime from yyyy-MM-dd HH:mm:ss to yyyy-MM-dd HH

const dateIsValid = (date) => {
  return !Number.isNaN(new Date(date).getTime());
};

const computedEventDateRange = computed(() => {
  if (
    props.event.attributes.date_range === undefined ||
    props.event?.attributes?.date_range === null
  )
    return "";

  console.log(props.event.attributes.date_range / 1000);

  let date1 = new Date(props.event.attributes?.date_range[0]);
  let day1 = new Intl.DateTimeFormat("lt-LT", {
    month: "long",
    day: "numeric",
  }).format(date1);
  // get date string without ending "d."
  day1 = day1.slice(0, -3);
  // capitalize day1 string
  day1 = day1.charAt(0).toUpperCase() + day1.slice(1);

  let date2 = new Date(props.event.attributes?.date_range[1]);

  // if (!dateIsValid(date2)) return "";

  let day2 = new Intl.DateTimeFormat("lt-LT", {
    day: "numeric",
  }).format(date2);
  // remove leading 0 from day2 string if it exists
  if (day2.charAt(0) === "0") day2 = day2.slice(1);

  // return capitalized string of date1 month long and day number
  return `${day1}–${day2} d.`;
});

const timeTillEvent = computed(() => {
  const date = new Date(props.event.date);
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

const openEventUrl = () => {
  window.open(props.event?.url, "_blank");
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
