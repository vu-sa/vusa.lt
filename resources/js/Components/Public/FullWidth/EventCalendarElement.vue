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
      class="prose prose-sm flex w-fit flex-col items-center justify-center dark:prose-invert lg:h-4/5 lg:w-1/2 lg:items-start 2xl:w-3/4"
    >
      <p
        v-if="$page.props.app.locale === 'lt'"
        class="text-2xl font-bold lg:w-2/3"
      >
        <span class="font-extrabold">Naujiena!</span> Sek visus VU studentų
        renginius bei įvykius
        <span class="text-vusa-red">čia!</span>
      </p>
      <p v-else class="text-2xl font-bold lg:w-2/3">
        Follow Vilnius University activities for students
        <span class="text-vusa-red">here!</span>
      </p>

      <p v-if="$page.props.app.locale === 'lt'" class="w-4/5">
        Arba nesuk galvos ir
        <!-- <em>patingėti</em> ir -->
        <span class="mx-1">
          <NButton size="tiny" round strong secondary @click="showModal = true"
            >sinchronizuok</NButton
          >
        </span>
        <strong>studentų kalendorių</strong> į „Google“ arba „Outlook“ ..? 🗓
      </p>

      <p v-else class="w-4/5">
        Or you can
        <!-- <em>be lazy</em> and -->
        <span class="mx-1">
          <NButton size="tiny" round strong secondary @click="showModal = true"
            >sync</NButton
          >
        </span>
        <strong>this student calendar</strong> to „Google“ or „Outlook“ ..? 🗓
      </p>

      <p v-if="$page.props.app.locale === 'lt'" class="w-4/5 text-sm">
        <strong>Ir dar</strong> - artėja VU SA ir VU SA PKP prisistatymai! Jeigu
        nenori jų laukti, prisijunk naudodamas
        <Link :href="route('memberRegistration', { lang: 'lt' })"
          >šią registraciją</Link
        >.
      </p>

      <p v-else class="w-4/5 text-sm">
        <strong>And also</strong> - the presentations of VU SA and VU SA PKP are
        coming! If you don't want to wait, you can join through
        <Link :href="route('memberRegistration', { lang: 'en' })">here</Link>.
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
            class="absolute top-14 left-12 z-10 rounded-lg object-cover shadow-2xl brightness-125 contrast-100 sm:left-24 md:left-32 lg:left-48 lg:max-w-[16rem]"
            src="/images/photos/pirmakursiu_stovykla_kaune.jpg"
          />
        </template>
        <!-- <img
          class="absolute -top-24 -left-40 rounded-lg object-cover shadow-lg brightness-75"
          src="/images/photos/pirmakursiu_stovykla_kaune.jpg"
        /> -->
        <FadeTransition>
          <EventCalendar
            class="z-20"
            :calendar-events="calendar"
            :locale="$page.props.app.locale"
            :is-theme-dark="isThemeDark"
          />
        </FadeTransition>
      </div>
    </div>
  </div>
  <CardModal
    v-model:show="showModal"
    :title="$t('Kalendoriaus sinchronizavimo instrukcija')"
    @close="showModal = false"
  >
    <p v-if="$page.props.app.locale === 'lt'">
      <strong>Pirmiausia</strong>, nusikopijuok nuorodą!
    </p>
    <p v-else><strong>First</strong>, copy the link!</p>

    <div class="flex flex-col gap-1">
      <p v-if="$page.props.app.locale === 'en'" class="font-bold">
        All events:
      </p>
      <div class="flex gap-4">
        <div class="flex items-center rounded-2xl bg-zinc-100/50 px-4">
          <span>{{ route("calendar.ics") }}</span>
        </div>
        <NButton @click="copyToClipboard(route('calendar.ics'))"
          ><template #icon><NIcon :component="Copy16Regular" /></template>
          {{ $t("Kopijuoti") }}</NButton
        >
      </div>
      <template v-if="$page.props.app.locale === 'en'">
        <p class="font-bold">
          Events held in English or accessible for non-Lithuanian speakers:
        </p>

        <div class="flex gap-4">
          <div class="flex items-center rounded-2xl bg-zinc-100/50 px-4">
            <span>{{ route("calendar.ics", { lang: "en" }) }}</span>
          </div>
          <NButton
            @click="copyToClipboard(route('calendar.ics', { lang: 'en' }))"
            ><template #icon><NIcon :component="Copy16Regular" /></template>
            {{ $t("Kopijuoti") }}</NButton
          >
        </div>
      </template>
    </div>
    <NDivider />
    <NTabs animated
      ><NTabPane name="Google">
        <ol v-if="$page.props.app.locale === 'lt'">
          <li>
            Nueik į savo
            <a
              target="_blank"
              href="https://calendar.google.com/calendar/u/0/r/settings/addbyurl"
            >
              Google kalendorių (per naršyklę kompiuteryje)</a
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
              Google Calendar (using a browser on a PC)</a
            >
          </li>
          <li>Paste the VU SR student activity calendar link</li>
          <li>Press <strong>„Add calendar“</strong></li>
          <li>
            ✅ (You may need to wait a minute or two for the events to pop in)
          </li>
        </ol> </NTabPane
      ><NTabPane name="Outlook (Office 365)">
        <ol v-if="$page.props.app.locale === 'lt'">
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
    <template #footer>
      <template v-if="$page.props.app.locale === 'lt'">
        „Google“ ir „Outlook“ kartais atnaujina renginių informaciją tik
        <strong> kartą per dieną </strong>. Dėl naujausios informacijos
        apsilankyk vusa.lt
      </template>
      <template v-else>
        Google and Outlook sometimes refresh these calendars only
        <strong>once per day</strong>. For the latest events, always visit
        vusa.lt
      </template>
    </template>
  </CardModal>
  <!-- <NDivider /> -->
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { Copy16Regular } from "@vicons/fluent";
import { Head, Link } from "@inertiajs/vue3";
import { NButton, NDivider, NIcon, NTabPane, NTabs } from "naive-ui";
import { ref } from "vue";

import CardModal from "@/Components/Modals/CardModal.vue";
import EventCalendar from "@/Components/Calendar/EventCalendar.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

defineProps<{
  calendar: Array<App.Entities.Calendar>;
  isThemeDark: boolean;
  showPhotos: boolean;
}>();

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
