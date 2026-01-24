<template>
  <Dialog :open @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>{{ $t('Apie posėdžių skaidrumą') }}</DialogTitle>
        <DialogDescription>
          {{ $t('Kodėl ir kaip rodomos balsavimo detalės') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-6">
        <!-- Why we publish -->
        <section>
          <h3 class="font-semibold mb-2 text-base">
{{ $t('Kodėl skelbiame?') }}
</h3>
          <p class="text-sm text-muted-foreground">
            {{ $t('VU SA užtikrina skaidrumą viešindama, kaip studentų atstovai balsuoja...') }}
          </p>
        </section>

        <!-- What each field means -->
        <section>
          <h3 class="font-semibold mb-3 text-base">
{{ $t('Ką reiškia kiekvienas laukas?') }}
</h3>
          <dl class="space-y-4">
            <!-- Student vote -->
            <div class="flex flex-col sm:flex-row sm:gap-4">
              <dt class="font-medium mb-1 sm:mb-0 sm:min-w-[140px] flex items-center gap-2">
                <VoteStatusIndicator vote="positive" type="vote" />
                <span>{{ $t('Studentų balsas') }}</span>
              </dt>
              <dd class="text-sm text-muted-foreground">
                {{ $t('Kaip balsavo studentų atstovas ar atstovai') }}
              </dd>
            </div>

            <!-- Decision -->
            <div class="flex flex-col sm:flex-row sm:gap-4">
              <dt class="font-medium mb-1 sm:mb-0 sm:min-w-[140px] flex items-center gap-2">
                <VoteStatusIndicator vote="positive" type="vote" />
                <span>{{ $t('Sprendimas') }}</span>
              </dt>
              <dd class="text-sm text-muted-foreground">
                {{ $t('Galutinis viso organo sprendimas') }}
              </dd>
            </div>

            <!-- Student benefit -->
            <div class="flex flex-col sm:flex-row sm:gap-4">
              <dt class="font-medium mb-1 sm:mb-0 sm:min-w-[140px] flex items-center gap-2">
                <VoteStatusIndicator vote="positive" type="benefit" />
                <span>{{ $t('Nauda studentams') }}</span>
              </dt>
              <dd class="text-sm text-muted-foreground">
                {{ $t('VU SA įvertinimas, ar sprendimas naudingas studentams') }}
              </dd>
            </div>

            <!-- Brought by students -->
            <div class="flex flex-col sm:flex-row sm:gap-4">
              <dt class="font-medium mb-1 sm:mb-0 sm:min-w-[140px] flex items-center gap-2">
                <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:text-zinc-400 ring-1 ring-zinc-200 dark:ring-zinc-700">
                  <UsersIcon class="h-3 w-3" />
                </span>
                <span>{{ $t('Įtraukta studentų') }}</span>
              </dt>
              <dd class="text-sm text-muted-foreground">
                {{ $t('Klausimas, kurį į posėdžio darbotvarkę įtraukė studentų atstovai') }}
              </dd>
            </div>
          </dl>
        </section>

        <!-- Symbol meanings -->
        <section>
          <h3 class="font-semibold mb-3 text-base">
{{ $t('Simbolių reikšmės') }}
</h3>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="flex items-center gap-2">
              <VoteStatusIndicator vote="positive" type="vote" />
            </div>
            <div class="flex items-center gap-2">
              <VoteStatusIndicator vote="negative" type="vote" />
            </div>
            <div class="flex items-center gap-2">
              <VoteStatusIndicator vote="neutral" type="vote" />
            </div>
          </div>

          <!-- Benefit icons -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3 pt-3 border-t">
            <div class="flex items-center gap-2">
              <VoteStatusIndicator vote="positive" type="benefit" />
              <span class="text-xs text-muted-foreground">({{ $t('nauda') }})</span>
            </div>
            <div class="flex items-center gap-2">
              <VoteStatusIndicator vote="negative" type="benefit" />
              <span class="text-xs text-muted-foreground">({{ $t('nauda') }})</span>
            </div>
            <div class="flex items-center gap-2">
              <VoteStatusIndicator vote="neutral" type="benefit" />
              <span class="text-xs text-muted-foreground">({{ $t('nauda') }})</span>
            </div>
          </div>
        </section>

        <!-- Agenda item status types -->
        <section>
          <h3 class="font-semibold mb-3 text-base">
{{ $t('Darbotvarkės klausimų būsenos') }}
</h3>
          <p class="text-sm text-muted-foreground mb-4">
            {{ $t('Kiekvienas klausimas rodomas pagal jo būseną ir balsavimo rezultatą') }}
          </p>
          <dl class="space-y-3">
            <div class="flex items-center gap-3">
              <div class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                <CheckIcon class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
              </div>
              <div>
                <dt class="font-medium text-sm">
{{ $t('Studentų pozicija priimta') }}
</dt>
                <dd class="text-xs text-muted-foreground">
{{ $t('Galutinis sprendimas atitinka studentų atstovų balsą') }}
</dd>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-6 w-6 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                <XIcon class="h-3.5 w-3.5 text-red-600 dark:text-red-400" />
              </div>
              <div>
                <dt class="font-medium text-sm">
{{ $t('Studentų pozicija nepriimta') }}
</dt>
                <dd class="text-xs text-muted-foreground">
{{ $t('Galutinis sprendimas skiriasi nuo studentų atstovų balso') }}
</dd>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-6 w-6 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                <MinusIcon class="h-3.5 w-3.5 text-zinc-600 dark:text-zinc-400" />
              </div>
              <div>
                <dt class="font-medium text-sm">
{{ $t('Neutralus sprendimas') }}
</dt>
                <dd class="text-xs text-muted-foreground">
{{ $t('Studentai susilaikė arba sprendimas neturėjo aiškios naudos/žalos') }}
</dd>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30 border border-dashed border-amber-400">
                <CircleIcon class="h-3 w-3 text-amber-500/50" />
              </div>
              <div>
                <dt class="font-medium text-sm">
{{ $t('Neaptartas') }}
</dt>
                <dd class="text-xs text-muted-foreground">
{{ $t('Balsavimo klausimas, bet balsas neįvyko arba nepažymėtas') }}
</dd>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-6 w-6 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                <ClockIcon class="h-3.5 w-3.5 text-zinc-500 dark:text-zinc-400" />
              </div>
              <div>
                <dt class="font-medium text-sm">
{{ $t('Atidėtas') }}
</dt>
                <dd class="text-xs text-muted-foreground">
{{ $t('Klausimo svarstymas atidėtas kitam posėdžiui') }}
</dd>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-6 w-6 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                <InfoIcon class="h-3.5 w-3.5 text-zinc-500 dark:text-zinc-400" />
              </div>
              <div>
                <dt class="font-medium text-sm">
{{ $t('Informacinis') }}
</dt>
                <dd class="text-xs text-muted-foreground">
{{ $t('Informacinio pobūdžio klausimas, balsavimo nereikalaujantis') }}
</dd>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                <HelpCircleIcon class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400" />
              </div>
              <div>
                <dt class="font-medium text-sm">
{{ $t('Nepažymėtas') }}
</dt>
                <dd class="text-xs text-muted-foreground">
{{ $t('Klausimo tipas dar nebuvo nurodytas administratoriaus') }}
</dd>
              </div>
            </div>
          </dl>
        </section>
      </div>

      <DialogFooter>
        <Button @click="$emit('update:open', false)">
          {{ $t('Supratau') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import {
  Check as CheckIcon,
  Circle as CircleIcon,
  Clock as ClockIcon,
  HelpCircle as HelpCircleIcon,
  Info as InfoIcon,
  Minus as MinusIcon,
  Users as UsersIcon,
  X as XIcon,
} from 'lucide-vue-next';

import VoteStatusIndicator from './VoteStatusIndicator.vue';

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';

defineProps<{
  open: boolean;
}>();

defineEmits<{
  'update:open': [value: boolean];
}>();
</script>
