<template>
  <Dialog :open="showModal" @update:open="(open) => !open && $emit('close')">
    <DialogContent class="max-w-2xl">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2.5 text-lg">
          <div class="flex size-8 items-center justify-center rounded-lg bg-vusa-red/10 text-vusa-red">
            <CalendarSync class="size-4" />
          </div>
          {{ $t('Kalendoriaus sinchronizavimo instrukcija') }}
        </DialogTitle>
        <DialogDescription>
          <template v-if="$page.props.app.locale === 'lt'">
            Sinchronizuok VU SA renginių kalendorių su savo asmeniniu kalendoriumi
          </template>
          <template v-else>
            Sync VU SR events calendar with your personal calendar
          </template>
        </DialogDescription>
      </DialogHeader>
      
      <div class="space-y-5">
        <!-- Step 1: Copy the link -->
        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
          <div class="mb-3 flex items-center gap-2">
            <span class="flex size-6 items-center justify-center rounded-full bg-vusa-red text-xs font-semibold text-white">1</span>
            <span class="font-medium">
              {{ $page.props.app.locale === 'lt' ? 'Nukopijuok nuorodą' : 'Copy the link' }}
            </span>
          </div>
          
          <div class="space-y-3">
            <div>
              <p v-if="$page.props.app.locale === 'en'" class="mb-1.5 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                All events
              </p>
              <div class="flex items-center gap-2">
                <div class="flex-1 overflow-hidden rounded-lg border border-zinc-200 bg-white px-3 py-2 font-mono text-xs text-zinc-600 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                  <span class="block truncate">{{ route("calendar.ics") }}</span>
                </div>
                <CopyToClipboardButton 
                  show-icon 
                  :text-to-copy="route('calendar.ics')"
                  error-text="Nepavyko nukopijuoti nuorodos..." 
                  success-text="Nuoroda nukopijuota!"
                  class="shrink-0"
                >
                  {{ $t("Kopijuoti") }}
                </CopyToClipboardButton>
              </div>
            </div>
            
            <template v-if="$page.props.app.locale === 'en'">
              <div>
                <p class="mb-1.5 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                  English-friendly events only
                </p>
                <div class="flex items-center gap-2">
                  <div class="flex-1 overflow-hidden rounded-lg border border-zinc-200 bg-white px-3 py-2 font-mono text-xs text-zinc-600 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                    <span class="block truncate">{{ route("calendar.ics", { lang: "en" }) }}</span>
                  </div>
                  <CopyToClipboardButton 
                    show-icon
                    :text-to-copy="route('calendar.ics', { lang: 'en' })"
                    error-text="Couldn't copy the link..." 
                    success-text="Link copied!"
                    class="shrink-0"
                  >
                    {{ $t("Kopijuoti") }}
                  </CopyToClipboardButton>
                </div>
              </div>
            </template>
          </div>
        </div>
        
        <!-- Step 2: Add to calendar -->
        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
          <div class="mb-3 flex items-center gap-2">
            <span class="flex size-6 items-center justify-center rounded-full bg-vusa-red text-xs font-semibold text-white">2</span>
            <span class="font-medium">
              {{ $page.props.app.locale === 'lt' ? 'Pridėk prie savo kalendoriaus' : 'Add to your calendar' }}
            </span>
          </div>
          
          <Tabs default-value="google">
            <TabsList class="mb-4 grid w-full grid-cols-2">
              <TabsTrigger value="google" class="gap-1.5">
                <Icon icon="mdi:google" class="size-4" />
                Google
              </TabsTrigger>
              <TabsTrigger value="outlook" class="gap-1.5">
                <Icon icon="mdi:microsoft-outlook" class="size-4" />
                Outlook
              </TabsTrigger>
            </TabsList>
            
            <TabsContent value="google" class="mt-0">
              <ol v-if="$page.props.app.locale === 'lt'" class="space-y-2.5 text-sm">
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">1</span>
                  <span>
                    Nueik į savo
                    <a target="_blank" class="font-medium text-vusa-red underline-offset-2 hover:underline" href="https://calendar.google.com/calendar/u/0/r/settings/addbyurl">
                      Google kalendorių</a>
                    <span class="text-zinc-500">(per naršyklę kompiuteryje)</span>
                  </span>
                </li>
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">2</span>
                  <span>Įklijuok nukopijuotą nuorodą</span>
                </li>
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">3</span>
                  <span>Paspausk <strong>„Add calendar"</strong></span>
                </li>
                <li class="flex items-center gap-3 text-green-600 dark:text-green-400">
                  <span class="flex size-5 shrink-0 items-center justify-center">✓</span>
                  <span>Paruošta! Renginiai atsiras per kelias minutes.</span>
                </li>
              </ol>
              <ol v-else class="space-y-2.5 text-sm">
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">1</span>
                  <span>
                    Go to your
                    <a target="_blank" class="font-medium text-vusa-red underline-offset-2 hover:underline" href="https://calendar.google.com/calendar/u/0/r/settings/addbyurl">
                      Google Calendar</a>
                    <span class="text-zinc-500">(using a browser on a PC)</span>
                  </span>
                </li>
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">2</span>
                  <span>Paste the copied link</span>
                </li>
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">3</span>
                  <span>Click <strong>„Add calendar"</strong></span>
                </li>
                <li class="flex items-center gap-3 text-green-600 dark:text-green-400">
                  <span class="flex size-5 shrink-0 items-center justify-center">✓</span>
                  <span>Done! Events will appear in a few minutes.</span>
                </li>
              </ol>
            </TabsContent>
            
            <TabsContent value="outlook" class="mt-0">
              <ol v-if="$page.props.app.locale === 'lt'" class="space-y-2.5 text-sm">
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">1</span>
                  <span>
                    Nueik į savo
                    <a target="_blank" class="font-medium text-vusa-red underline-offset-2 hover:underline" href="https://outlook.office.com/calendar/addcalendar">
                      Outlook kalendorių</a>
                  </span>
                </li>
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">2</span>
                  <span>Pasirink <strong>„Subscribe from web"</strong> sekciją</span>
                </li>
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">3</span>
                  <span>Įklijuok nukopijuotą nuorodą</span>
                </li>
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">4</span>
                  <span>Paspausk <strong>„Import"</strong></span>
                </li>
                <li class="flex items-center gap-3 text-green-600 dark:text-green-400">
                  <span class="flex size-5 shrink-0 items-center justify-center">✓</span>
                  <span>Paruošta!</span>
                </li>
              </ol>
              <ol v-else class="space-y-2.5 text-sm">
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">1</span>
                  <span>
                    Go to your
                    <a target="_blank" class="font-medium text-vusa-red underline-offset-2 hover:underline" href="https://outlook.office.com/calendar/addcalendar">
                      Outlook calendar</a>
                  </span>
                </li>
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">2</span>
                  <span>Go to <strong>„Subscribe from web"</strong> section</span>
                </li>
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">3</span>
                  <span>Paste the copied link</span>
                </li>
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">4</span>
                  <span>Click <strong>„Import"</strong></span>
                </li>
                <li class="flex items-center gap-3 text-green-600 dark:text-green-400">
                  <span class="flex size-5 shrink-0 items-center justify-center">✓</span>
                  <span>Done!</span>
                </li>
              </ol>
            </TabsContent>
          </Tabs>
        </div>
      </div>
      
      <!-- Footer notice - fixed the weird text rendering -->
      <div class="mt-2 flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2.5 text-sm text-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
        <Info class="mt-0.5 size-4 shrink-0" />
        <p>
          <template v-if="$page.props.app.locale === 'lt'">
            „Google" ir „Outlook" kartais atnaujina renginių informaciją tik <strong>kartą per dieną</strong>. 
            Dėl naujausios informacijos apsilankyk vusa.lt
          </template>
          <template v-else>
            Google and Outlook sometimes refresh these calendars only <strong>once per day</strong>. 
            For the latest events, always visit vusa.lt
          </template>
        </p>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { CalendarSync, Info } from "lucide-vue-next";
import { Icon } from "@iconify/vue";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import CopyToClipboardButton from "../Buttons/CopyToClipboardButton.vue";

defineEmits(["close"]);

defineProps<{
  showModal: boolean;
}>();
</script>
