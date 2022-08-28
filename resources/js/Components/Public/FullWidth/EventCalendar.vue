<template>
  <Head>
    <link
      rel="preload"
      href="/images/photos/pirmakursiu_stovykla_kaune.jpg"
      as="image"
    />
    <link rel="preload" href="/images/photos/vu.jpg" as="image" />
    <link rel="preload" href="/images/photos/stovykla.jpg" as="image" />
  </Head>

  <div
    class="mx-auto mt-8 flex max-w-7xl flex-col-reverse gap-4 px-16 lg:mt-32 lg:flex-row lg:px-24 xl:px-40"
  >
    <div
      class="prose-sm flex w-fit flex-col justify-center sm:prose lg:h-4/5 lg:w-1/2 2xl:w-3/4"
    >
      <p v-if="$page.props.locale === 'lt'" class="text-2xl font-bold lg:w-2/3">
        <span class="font-extrabold">Naujiena!</span> Sek visus VU studentų
        renginius bei įvykius
        <span class="text-vusa-red">čia! 🗓</span>
      </p>
      <p v-else class="text-2xl font-bold lg:w-2/3">
        Follow Vilnius University activities for students
        <span class="text-vusa-red">here! 🗓</span>
      </p>

      <p v-if="$page.props.locale === 'lt'" class="w-4/5">
        Kalendorius atnaujinamas kasdien!
      </p>
      <p v-else class="w-4/5">The calendar is updated every day!</p>

      <p v-if="$page.props.locale === 'lt'" class="w-4/5 text-sm">
        Arba nesuk galvos ir
        <!-- <em>patingėti</em> ir -->
        <span class="mx-1">
          <NButton size="tiny" round strong secondary @click="showModal = true"
            >sinchronizuok</NButton
          >
        </span>
        <strong>studentų kalendorių</strong> į „Google“ arba „Outlook“ ..?
      </p>

      <p v-else class="w-4/5 text-sm">
        Or you can
        <!-- <em>be lazy</em> and -->
        <span class="mx-1">
          <NButton size="tiny" round strong secondary @click="showModal = true"
            >sync</NButton
          >
        </span>
        <strong>this student calendar</strong> to „Google“ or „Outlook“ ..?
      </p>
    </div>

    <div class="relative mx-auto">
      <div class="relative flex w-fit items-center justify-center lg:top-4">
        <template v-if="showPhotos">
          <img
            class="absolute top-8 -left-32 max-w-[12rem] rounded-lg object-cover shadow-xl blur brightness-50 lg:-top-24 lg:max-w-[16rem]"
            src="/images/photos/vu.jpg"
          />
          <img
            class="absolute top-12 -left-16 z-10 max-w-[12rem] rounded-lg object-cover shadow-xl blur-sm brightness-75 lg:-top-12 lg:max-w-[16rem]"
            src="/images/photos/stovykla.jpg"
          />
          <img
            class="absolute top-14 left-16 z-10 rounded-lg object-cover shadow-2xl brightness-125 contrast-100 md:left-32 lg:left-48 lg:max-w-[16rem]"
            src="/images/photos/pirmakursiu_stovykla_kaune.jpg"
          />
        </template>
        <!-- <img
          class="absolute -top-24 -left-40 rounded-lg object-cover shadow-lg brightness-75"
          src="/images/photos/pirmakursiu_stovykla_kaune.jpg"
        /> -->
        <FadeTransition>
          <Calendar
            class="z-20 shadow-xl"
            :attributes="calendarAttributes"
            color="red"
            :locale="$page.props.locale"
          >
            <template #day-popover="{ attributes, dayTitle, format, masks }">
              <div class="max-w-md">
                <div
                  class="mb-1 text-center text-xs font-semibold text-gray-300"
                >
                  {{ dayTitle }}
                </div>
                <PopoverRow
                  v-for="attr in attributes"
                  :key="attr.key"
                  :attribute="attr"
                >
                  <div class="inline-flex items-center gap-2">
                    <a
                      target="_blank"
                      :href="
                        route('main.calendar.event', {
                          calendar: attr.key,
                          lang: $page.props.locale,
                        })
                      "
                      >{{ attr.popover.label }}</a
                    >
                    <NConfigProvider
                      class="flex h-fit items-center justify-center"
                      :theme="darkTheme"
                    >
                      <div class="my-auto flex items-center justify-center">
                        <NButton
                          text
                          size="small"
                          @click="windowOpen(attr.customData.googleLink)"
                          ><NIcon :component="Google"
                        /></NButton>
                      </div>
                    </NConfigProvider>
                  </div>
                </PopoverRow>
                <!-- <ul class="list-inside">
                    <li v-for="event in attributes" :key="event.id">
                      {{ event.popover.label }}
                    </li>
                  </ul> -->
              </div>
            </template>
          </Calendar>
        </FadeTransition>
      </div>
    </div>
  </div>
  <NModal v-model:show="showModal">
    <NCard
      class="prose-sm prose"
      style="max-width: 600px"
      :title="$t('Kalendoriaus sinchronizavimo instrukcija')"
      :bordered="false"
      size="large"
      role="card"
      aria-modal="true"
    >
      <p v-if="$page.props.locale === 'lt'">
        <strong>Pirmiausia</strong>, nusikopijuok nuorodą!
      </p>
      <p v-else><strong>First</strong>, copy the link!</p>

      <div class="flex flex-col gap-1">
        <p v-if="$page.props.locale === 'en'" class="font-bold">All events:</p>
        <div class="flex gap-4">
          <div class="flex items-center rounded-2xl bg-zinc-100/50 px-4">
            <span>{{ route("calendar.ics") }}</span>
          </div>
          <NButton @click="copyToClipboard(route('calendar.ics'))"
            ><template #icon><NIcon :component="Copy16Regular" /></template>
            {{ $t("Kopijuoti") }}</NButton
          >
        </div>
        <template v-if="$page.props.locale === 'en'">
          <p class="font-bold">
            Events held in English or accessible for non-Lithuanian speakers:
          </p>

          <div class="flex gap-4">
            <div class="flex items-center rounded-2xl bg-zinc-100/50 px-4">
              <span>{{ route("main.calendar.ics", { lang: "en" }) }}</span>
            </div>
            <NButton
              @click="
                copyToClipboard(route('main.calendar.ics', { lang: 'en' }))
              "
              ><template #icon><NIcon :component="Copy16Regular" /></template>
              {{ $t("Kopijuoti") }}</NButton
            >
          </div>
        </template>
      </div>
      <NDivider></NDivider>
      <NTabs animated
        ><NTabPane name="Google">
          <ol v-if="$page.props.locale === 'lt'">
            <li>
              Nueik į savo
              <a
                target="_blank"
                href="https://calendar.google.com/calendar/u/0/r/settings/addbyurl"
              >
                Google kalendorių</a
              >
            </li>
            <li>Įkelk VU SA studentiško kalendoriaus nuorodą</li>
            <li>
              Paspausk <strong>„Pridėti kalendorių“</strong> („Add calendar“)
            </li>
            <li>✅ (Gali užtrukti iki kelių minučių, kol renginiai atsiras)</li>
          </ol>
          <ol v-else>
            <li>
              Go to your
              <a
                target="_blank"
                href="https://calendar.google.com/calendar/u/0/r/settings/addbyurl"
              >
                Google Calendar</a
              >
            </li>
            <li>Paste the VU SR student activity calendar link</li>
            <li>Press <strong>„Add calendar“</strong></li>
            <li>
              ✅ (You may need to wait a minute or two for the events to pop in)
            </li>
          </ol> </NTabPane
        ><NTabPane name="Outlook (Office 365)">
          <ol v-if="$page.props.locale === 'lt'">
            <li>
              Nueik į savo
              <a href="https://outlook.office.com/calendar/addcalendar"
                >Outlook kalendorių</a
              >
            </li>
            <li>
              Pasirink
              <strong>„Prenumeruoti iš žiniatinklio“</strong> („Subscribe from
              web“) sekciją
            </li>
            <li>Įkelk VU SA studentiško kalendoriaus nuorodą</li>
            <li>Paspausk <strong>„Importuoti“</strong> („Import“)</li>
            <li>✅</li>
          </ol>
          <ol v-else>
            <li>
              Go to your
              <a href="https://outlook.office.com/calendar/addcalendar"
                >Outlook calendar</a
              >
            </li>
            <li>
              Go to
              <strong>„Subscribe from web“</strong> section
            </li>
            <li>Paste the VU SR student activity calendar link</li>
            <li>Press <strong>„Import“</strong></li>
            <li>✅</li>
          </ol>
        </NTabPane></NTabs
      >
    </NCard>
  </NModal>
  <!-- <NDivider /> -->
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { Calendar, PopoverRow } from "v-calendar";
import { Copy16Regular } from "@vicons/fluent";
import { Google } from "@vicons/fa";
import { Head } from "@inertiajs/inertia-vue3";
import {
  NButton,
  NCard,
  NConfigProvider,
  NDivider,
  NIcon,
  NModal,
  NTabPane,
  NTabs,
  createDiscreteApi,
  darkTheme,
} from "naive-ui";
import { ref } from "vue";
import route from "ziggy-js";

import FadeTransition from "../Utils/FadeTransition.vue";

const { message } = createDiscreteApi(["message"]);

const props = defineProps<{
  calendar: Array<App.Models.Calendar>;
  showPhotos: boolean;
}>();

// check if event date and end date is on the same day
const isSameDay = (date1: string, date2: string) => {
  if (date2 === null) {
    return true;
  }

  let parsedDate1 = new Date(date1.replace(/-/g, "/"));
  let parsedDate2 = new Date(date2.replace(/-/g, "/"));

  return (
    parsedDate1.getFullYear() === parsedDate2.getFullYear() &&
    parsedDate1.getMonth() === parsedDate2.getMonth() &&
    parsedDate1.getDate() === parsedDate2.getDate()
  );
};

const calendarAttributes = props.calendar.map((event) => {
  let eventColor = event.category;

  switch (event.category) {
    case "freshmen-camps":
      eventColor = "orange";
      break;

    case "grey":
      eventColor = "gray";
      break;

    default:
      break;
  }

  let calendarAttrObject = {
    dates: event.end_date
      ? {
          start: new Date(event.date.replace(/-/g, "/")),
          end: new Date(event.end_date.replace(/-/g, "/")),
        }
      : new Date(event.date.replace(/-/g, "/")),
    [isSameDay(event.date, event.end_date) ? "dot" : "highlight"]: eventColor,
    popover: {
      label: event.title,
      isInteractive: true,
    },
    key: event.id,
    customData: { googleLink: event.googleLink },
  };
  return calendarAttrObject;
});

// add today to the calendar
calendarAttributes.push({
  dates: new Date(),
  highlight: { color: "red", fillMode: "outline" },
});

const windowOpen = (url: string) => {
  window.open(url, "_blank");
};

const showModal = ref(false);

// copy link to clipboard using navigator.clipboard
const copyToClipboard = async (text: string) => {
  if (navigator.clipboard) {
    await navigator.clipboard.writeText(text);
    message.success("Nuoroda nukopijuota!");
  } else {
    message.error("Nepavyko nukopijuoti nuorodos...");
  }
};
</script>

<style scoped>
.vc-container {
  font-family: "Inter", sans-serif !important;
  border: 0 !important;
}
</style>
