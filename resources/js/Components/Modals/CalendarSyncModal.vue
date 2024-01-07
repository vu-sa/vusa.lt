<template>
  <CardModal
    :show="showModal"
    :title="$t('Kalendoriaus sinchronizavimo instrukcija')"
    @close="$emit('close')"
  >
    <p v-if="$page.props.app.locale === 'lt'">
      <strong>Pirmiausia</strong>, nusikopijuok nuorodą!
    </p>
    <p v-else><strong>First</strong>, copy the link!</p>

    <NMessageProvider>
      <div class="flex flex-col gap-1">
        <p v-if="$page.props.app.locale === 'en'" class="font-bold">
          All events:
        </p>
        <div class="flex gap-4">
          <div class="flex items-center rounded-2xl bg-zinc-100/50 px-4">
            <span>{{ route("calendar.ics") }}</span>
          </div>
          <CopyToClipboardButton :text-to-copy="route('calendar.ics')" />
        </div>
        <template v-if="$page.props.app.locale === 'en'">
          <p class="font-bold">
            Events held in English or accessible for non-Lithuanian speakers:
          </p>

          <div class="flex gap-4">
            <div class="flex items-center rounded-2xl bg-zinc-100/50 px-4">
              <span>{{ route("calendar.ics", { lang: "en" }) }}</span>
            </div>
            <CopyToClipboardButton
              :text-to-copy="route('calendar.ics', { lang: 'en' })"
            />
          </div>
        </template>
      </div>
    </NMessageProvider>
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
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { NDivider, NMessageProvider, NTabPane, NTabs } from "naive-ui";
import CardModal from "@/Components/Modals/CardModal.vue";
import CopyToClipboardButton from "../Buttons/CopyToClipboardButton.vue";

defineEmits(["close"]);

defineProps<{
  showModal: boolean;
}>();
</script>
