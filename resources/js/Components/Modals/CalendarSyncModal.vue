<template>
  <Dialog :open="showModal" @update:open="(open) => !open && $emit('close')">
    <DialogContent class="max-w-3xl">
      <DialogHeader>
        <DialogTitle>{{ $t('Kalendoriaus sinchronizavimo instrukcija') }}</DialogTitle>
      </DialogHeader>
      
      <div class="space-y-4">
        <p v-if="$page.props.app.locale === 'lt'">
          <strong>Pirmiausia</strong>, nusikopijuok nuorodą!
        </p>
        <p v-else>
          <strong>First</strong>, copy the link!
        </p>

        <div class="flex flex-col gap-2">
          <p v-if="$page.props.app.locale === 'en'" class="font-bold">
            All events:
          </p>
          <div class="flex flex-wrap gap-4">
            <div class="flex items-center rounded-xl bg-zinc-100/50 px-4 py-2 dark:bg-zinc-700/50 text-sm break-all">
              <span>{{ route("calendar.ics") }}</span>
            </div>
            <CopyToClipboardButton show-icon :text-to-copy="route('calendar.ics')"
              error-text="Nepavyko nukopijuoti nuorodos..." success-text="Nuoroda nukopijuota!">
              {{ $t("Kopijuoti") }}
            </CopyToClipboardButton>
          </div>
          <template v-if="$page.props.app.locale === 'en'">
            <p class="font-bold mt-2">
              Events held in English or accessible for non-Lithuanian speakers:
            </p>

            <div class="flex flex-wrap gap-4">
              <div class="flex items-center rounded-xl bg-zinc-100/50 px-4 py-2 dark:bg-zinc-700/50 text-sm break-all">
                <span>{{ route("calendar.ics", { lang: "en" }) }}</span>
              </div>
              <CopyToClipboardButton :text-to-copy="route('calendar.ics', { lang: 'en' })">
                {{ $t("Kopijuoti") }}
              </CopyToClipboardButton>
            </div>
          </template>
        </div>
        
        <Separator />
        
        <Tabs default-value="google">
          <TabsList class="grid w-full grid-cols-2">
            <TabsTrigger value="google">Google</TabsTrigger>
            <TabsTrigger value="outlook">Outlook (Office 365)</TabsTrigger>
          </TabsList>
          
          <TabsContent value="google" class="mt-4">
            <ol v-if="$page.props.app.locale === 'lt'" class="list-decimal list-inside space-y-2 text-sm">
              <li>
                Nueik į savo
                <a target="_blank" class="text-vusa-red hover:underline" href="https://calendar.google.com/calendar/u/0/r/settings/addbyurl">
                  Google kalendorių (per naršyklę kompiuteryje)</a>
              </li>
              <li>Įkelk VU SA studentiško kalendoriaus nuorodą</li>
              <li>
                Paspausk <strong>„Pridėti kalendorių"</strong> („Add calendar")
              </li>
              <li>✅ (Gali užtrukti iki kelių minučių, kol renginiai atsiras)</li>
            </ol>
            <ol v-else class="list-decimal list-inside space-y-2 text-sm">
              <li>
                Go to your
                <a target="_blank" class="text-vusa-red hover:underline" href="https://calendar.google.com/calendar/u/0/r/settings/addbyurl">
                  Google Calendar (using a browser on a PC)</a>
              </li>
              <li>Paste the VU SR student activity calendar link</li>
              <li>Press <strong>„Add calendar"</strong></li>
              <li>
                ✅ (You may need to wait a minute or two for the events to pop in)
              </li>
            </ol>
          </TabsContent>
          
          <TabsContent value="outlook" class="mt-4">
            <ol v-if="$page.props.app.locale === 'lt'" class="list-decimal list-inside space-y-2 text-sm">
              <li>
                Nueik į savo
                <a class="text-vusa-red hover:underline" href="https://outlook.office.com/calendar/addcalendar">Outlook kalendorių</a>
              </li>
              <li>
                Pasirink
                <strong>„Prenumeruoti iš žiniatinklio"</strong> („Subscribe from
                web") sekciją
              </li>
              <li>Įkelk VU SA studentiško kalendoriaus nuorodą</li>
              <li>Paspausk <strong>„Importuoti"</strong> („Import")</li>
              <li>✅</li>
            </ol>
            <ol v-else class="list-decimal list-inside space-y-2 text-sm">
              <li>
                Go to your
                <a class="text-vusa-red hover:underline" href="https://outlook.office.com/calendar/addcalendar">Outlook calendar</a>
              </li>
              <li>
                Go to
                <strong>„Subscribe from web"</strong> section
              </li>
              <li>Paste the VU SR student activity calendar link</li>
              <li>Press <strong>„Import"</strong></li>
              <li>✅</li>
            </ol>
          </TabsContent>
        </Tabs>
      </div>
      
      <DialogFooter class="text-sm text-zinc-600 dark:text-zinc-400">
        <template v-if="$page.props.app.locale === 'lt'">
          „Google" ir „Outlook" kartais atnaujina renginių informaciją tik
          <strong> kartą per dieną </strong>. Dėl naujausios informacijos
          apsilankyk vusa.lt
        </template>
        <template v-else>
          Google and Outlook sometimes refresh these calendars only
          <strong>once per day</strong>. For the latest events, always visit
          vusa.lt
        </template>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/Components/ui/dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { Separator } from "@/Components/ui/separator";
import CopyToClipboardButton from "../Buttons/CopyToClipboardButton.vue";

defineEmits(["close"]);

defineProps<{
  showModal: boolean;
}>();
</script>
