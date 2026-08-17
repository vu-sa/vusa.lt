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
          {{ $t('Sinchronizuok VU SA renginių kalendorių su savo asmeniniu kalendoriumi') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-5">
        <!-- Step 1: Copy the link -->
        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
          <div class="mb-3 flex items-center gap-2">
            <span class="flex size-6 items-center justify-center rounded-full bg-vusa-red text-xs font-semibold text-white">1</span>
            <span class="font-medium">
              {{ $t('Nukopijuok nuorodą') }}
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
                  :error-text="$t('Nepavyko nukopijuoti nuorodos...')"
                  :success-text="$t('Nuoroda nukopijuota!')"
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
              {{ $t('Pridėk prie savo kalendoriaus') }}
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
              <ol class="space-y-2.5 text-sm">
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">1</span>
                  <span>
                    {{ $t('Nueik į savo') }}
                    <a target="_blank" class="font-medium text-vusa-red underline-offset-2 hover:underline" href="https://calendar.google.com/calendar/u/0/r/settings/addbyurl">{{ $t('Google kalendorių') }}</a>
                    <span class="text-zinc-500">{{ $t('(per naršyklę kompiuteryje)') }}</span>
                  </span>
                </li>
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">2</span>
                  <span>{{ $t('Įklijuok nukopijuotą nuorodą') }}</span>
                </li>
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">3</span>
                  <span>{{ $t('Paspausk') }} <strong>„Add calendar"</strong></span>
                </li>
                <li class="flex items-center gap-3 text-green-600 dark:text-green-400">
                  <span class="flex size-5 shrink-0 items-center justify-center">✓</span>
                  <span>{{ $t('Paruošta! Renginiai atsiras per kelias minutes.') }}</span>
                </li>
              </ol>
            </TabsContent>

            <TabsContent value="outlook" class="mt-0">
              <ol class="space-y-2.5 text-sm">
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">1</span>
                  <span>
                    {{ $t('Nueik į savo') }}
                    <a target="_blank" class="font-medium text-vusa-red underline-offset-2 hover:underline" href="https://outlook.office.com/calendar/addcalendar">{{ $t('Outlook kalendorių') }}</a>
                  </span>
                </li>
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">2</span>
                  <span>{{ $t('Pasirink') }} <strong>„Subscribe from web"</strong> {{ $t('sekciją') }}</span>
                </li>
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">3</span>
                  <span>{{ $t('Įklijuok nukopijuotą nuorodą') }}</span>
                </li>
                <li class="flex gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium dark:bg-zinc-700">4</span>
                  <span>{{ $t('Paspausk') }} <strong>„Import"</strong></span>
                </li>
                <li class="flex items-center gap-3 text-green-600 dark:text-green-400">
                  <span class="flex size-5 shrink-0 items-center justify-center">✓</span>
                  <span>{{ $t('Paruošta!') }}</span>
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
          {{ $t('„Google" ir „Outlook" kartais atnaujina renginių informaciją tik') }}
          <strong>{{ $t('kartą per dieną') }}</strong>.
          {{ $t('Dėl naujausios informacijos apsilankyk vusa.lt') }}
        </p>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="tsx">
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarSync, Info } from 'lucide-vue-next';
import { Icon } from '@iconify/vue';

import CopyToClipboardButton from '../Buttons/CopyToClipboardButton.vue';

import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';

defineEmits(['close']);

defineProps<{
  showModal: boolean;
}>();
</script>
