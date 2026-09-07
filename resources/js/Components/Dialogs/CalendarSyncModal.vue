<template>
  <Dialog :open="showModal" @update:open="(open) => !open && $emit('close')">
    <DialogContent class="sm:max-w-3xl max-h-[90vh] overflow-y-auto rounded-none border border-border bg-background p-6 shadow-xl">
      <DialogHeader class="border-b border-border pb-4 text-left">
        <div class="flex items-center gap-2">
          <span class="u-eyebrow">{{ $t('Sinchronizavimas') }}</span>
        </div>
        <DialogTitle class="u-display text-xl sm:text-2xl text-foreground mt-1">
          {{ $t('Kalendoriaus sinchronizavimo instrukcija') }}
        </DialogTitle>
        <DialogDescription class="text-sm text-muted-foreground mt-1">
          {{ $t('Sinchronizuok VU SA renginių kalendorių su savo asmeniniu kalendoriumi') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4 py-2">
        <!-- Step 1: Copy the link -->
        <div class="border border-border bg-card p-4 sm:p-5">
          <div class="mb-3.5 flex items-center gap-2.5">
            <span class="flex size-6 shrink-0 items-center justify-center border border-brand bg-brand-fill font-mono text-xs font-bold text-brand-foreground">1</span>
            <h3 class="text-xs font-bold uppercase tracking-wider text-foreground">
              {{ $t('Nukopijuok nuorodą') }}
            </h3>
          </div>

          <div class="space-y-3">
            <div>
              <p v-if="$page.props.app.locale === 'en'" class="mb-1.5 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                {{ $t('Visi renginiai') }}
              </p>
              <div class="flex items-center gap-2">
                <div class="flex-1 overflow-hidden border border-border bg-background px-3 py-2 font-mono text-xs text-foreground">
                  <span class="block truncate">{{ route('calendar.ics') }}</span>
                </div>
                <CopyToClipboardButton
                  show-icon
                  :text-to-copy="route('calendar.ics')"
                  :error-text="$t('Nepavyko nukopijuoti nuorodos...')"
                  :success-text="$t('Nuoroda nukopijuota!')"
                  class="shrink-0 h-9 px-3 text-xs font-bold uppercase tracking-wide border-border hover:border-brand hover:text-brand"
                >
                  {{ $t('Kopijuoti') }}
                </CopyToClipboardButton>
              </div>
            </div>

            <template v-if="$page.props.app.locale === 'en'">
              <div>
                <p class="mb-1.5 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                  {{ $t('Tik renginiai anglų kalba') }}
                </p>
                <div class="flex items-center gap-2">
                  <div class="flex-1 overflow-hidden border border-border bg-background px-3 py-2 font-mono text-xs text-foreground">
                    <span class="block truncate">{{ route('calendar.ics', { lang: 'en' }) }}</span>
                  </div>
                  <CopyToClipboardButton
                    show-icon
                    :text-to-copy="route('calendar.ics', { lang: 'en' })"
                    :error-text="$t('Nepavyko nukopijuoti nuorodos...')"
                    :success-text="$t('Nuoroda nukopijuota!')"
                    class="shrink-0 h-9 px-3 text-xs font-bold uppercase tracking-wide border-border hover:border-brand hover:text-brand"
                  >
                    {{ $t('Kopijuoti') }}
                  </CopyToClipboardButton>
                </div>
              </div>
            </template>
          </div>
        </div>

        <!-- Step 2: Add to calendar -->
        <div class="border border-border bg-card p-4 sm:p-5">
          <div class="mb-3.5 flex items-center gap-2.5">
            <span class="flex size-6 shrink-0 items-center justify-center border border-brand bg-brand-fill font-mono text-xs font-bold text-brand-foreground">2</span>
            <h3 class="text-xs font-bold uppercase tracking-wider text-foreground">
              {{ $t('Pridėk prie savo kalendoriaus') }}
            </h3>
          </div>

          <Tabs default-value="google">
            <TabsList class="mb-4 grid w-full grid-cols-2 rounded-none border border-border bg-background p-0.5">
              <TabsTrigger
                value="google"
                :class="[
                  'gap-2 rounded-none py-2 text-xs font-bold uppercase tracking-wide transition-colors',
                  'data-[state=active]:bg-brand-fill data-[state=active]:text-brand-foreground',
                ]"
              >
                <ISimpleIconsGoogle class="size-3.5 shrink-0" />
                <span>Google Calendar</span>
              </TabsTrigger>
              <TabsTrigger
                value="outlook"
                :class="[
                  'gap-2 rounded-none py-2 text-xs font-bold uppercase tracking-wide transition-colors',
                  'data-[state=active]:bg-brand-fill data-[state=active]:text-brand-foreground',
                ]"
              >
                <ISimpleIconsMicrosoftoutlook class="size-3.5 shrink-0" />
                <span>Outlook</span>
              </TabsTrigger>
            </TabsList>

            <TabsContent value="google" class="mt-0">
              <ol class="space-y-3 text-sm text-muted-foreground">
                <li class="flex items-start gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center border border-border bg-background font-mono text-xs font-bold text-foreground">1</span>
                  <span>
                    {{ $t('Nueik į savo') }}
                    <a
                      target="_blank"
                      rel="noopener noreferrer"
                      class="font-bold text-brand underline underline-offset-2 hover:decoration-2 inline-flex items-center gap-0.5"
                      href="https://calendar.google.com/calendar/u/0/r/settings/addbyurl"
                    >
                      {{ $t('Google kalendorių') }}
                      <IFluentOpen16Regular class="size-3 ml-0.5" />
                    </a>
                    <span class="text-muted-foreground/80"> {{ $t('(per naršyklę kompiuteryje)') }}</span>
                  </span>
                </li>
                <li class="flex items-start gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center border border-border bg-background font-mono text-xs font-bold text-foreground">2</span>
                  <span>{{ $t('Įklijuok nukopijuotą nuorodą') }}</span>
                </li>
                <li class="flex items-start gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center border border-border bg-background font-mono text-xs font-bold text-foreground">3</span>
                  <span>{{ $t('Paspausk') }} <strong class="text-foreground">„Add calendar"</strong></span>
                </li>
                <li class="flex items-start gap-3 text-foreground font-medium pt-1">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center border border-brand bg-brand-fill font-mono text-xs font-bold text-brand-foreground">✓</span>
                  <span>{{ $t('Paruošta! Renginiai atsiras per kelias minutes.') }}</span>
                </li>
              </ol>
            </TabsContent>

            <TabsContent value="outlook" class="mt-0">
              <ol class="space-y-3 text-sm text-muted-foreground">
                <li class="flex items-start gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center border border-border bg-background font-mono text-xs font-bold text-foreground">1</span>
                  <span>
                    {{ $t('Nueik į savo') }}
                    <a
                      target="_blank"
                      rel="noopener noreferrer"
                      class="font-bold text-brand underline underline-offset-2 hover:decoration-2 inline-flex items-center gap-0.5"
                      href="https://outlook.office.com/calendar/addcalendar"
                    >
                      {{ $t('Outlook kalendorių') }}
                      <IFluentOpen16Regular class="size-3 ml-0.5" />
                    </a>
                  </span>
                </li>
                <li class="flex items-start gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center border border-border bg-background font-mono text-xs font-bold text-foreground">2</span>
                  <span>{{ $t('Pasirink') }} <strong class="text-foreground">„Subscribe from web"</strong> {{ $t('sekciją') }}</span>
                </li>
                <li class="flex items-start gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center border border-border bg-background font-mono text-xs font-bold text-foreground">3</span>
                  <span>{{ $t('Įklijuok nukopijuotą nuorodą') }}</span>
                </li>
                <li class="flex items-start gap-3">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center border border-border bg-background font-mono text-xs font-bold text-foreground">4</span>
                  <span>{{ $t('Paspausk') }} <strong class="text-foreground">„Import"</strong></span>
                </li>
                <li class="flex items-start gap-3 text-foreground font-medium pt-1">
                  <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center border border-brand bg-brand-fill font-mono text-xs font-bold text-brand-foreground">✓</span>
                  <span>{{ $t('Paruošta!') }}</span>
                </li>
              </ol>
            </TabsContent>
          </Tabs>
        </div>
      </div>

      <!-- Footer notice -->
      <div class="flex items-start gap-2.5 border border-border bg-secondary/40 p-3.5 text-xs text-muted-foreground">
        <IFluentInfo16Regular class="mt-0.5 size-4 shrink-0 text-brand" />
        <p class="leading-relaxed">
          {{ $t('„Google" ir „Outlook" kartais atnaujina renginių informaciją tik') }}
          <strong class="text-foreground">{{ $t('kartą per dieną') }}</strong>.
          {{ $t('Dėl naujausios informacijos apsilankyk vusa.lt') }}
        </p>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import CopyToClipboardButton from '@/Components/Buttons/CopyToClipboardButton.vue';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import ISimpleIconsGoogle from '~icons/simple-icons/google';
import ISimpleIconsMicrosoftoutlook from '~icons/simple-icons/microsoftoutlook';
import IFluentInfo16Regular from '~icons/fluent/info-16-regular';
import IFluentOpen16Regular from '~icons/fluent/open-16-regular';

defineProps<{
  showModal: boolean;
}>();

const emit = defineEmits<(e: 'close') => void>();
</script>
