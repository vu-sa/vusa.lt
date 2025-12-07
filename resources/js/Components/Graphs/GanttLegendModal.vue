<template>
  <Dialog :open="isOpen" @update:open="$emit('update:is-open', $event)">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-muted-foreground" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
          </svg>
          {{ $t('Laiko juostos legenda') }}
        </DialogTitle>
        <DialogDescription>
          {{ $t('Susipažinkite su laiko juostos simboliais ir jų reikšmėmis') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-6 py-4">
        <!-- Meetings Section -->
        <div class="space-y-3">
          <h4 class="text-sm font-semibold text-foreground flex items-center gap-2">
            <component :is="Icons.MEETING" class="h-4 w-4" />
            {{ $t('Susitikimai') }}
          </h4>
          <div class="space-y-2 pl-6">
            <div class="flex items-center gap-3">
              <span class="inline-block w-3 h-3 rounded-full bg-foreground dark:bg-white shrink-0" />
              <div>
                <span class="text-sm font-medium">{{ $t('Užbaigtas susitikimas') }}</span>
                <p class="text-xs text-muted-foreground">{{ $t('Susitikimas su visa užpildyta informacija ir darbotvarkės punktais') }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span class="inline-block w-3 h-3 rounded-full bg-red-500 shrink-0" />
              <div>
                <span class="text-sm font-medium">{{ $t('Neužbaigtas susitikimas') }}</span>
                <p class="text-xs text-muted-foreground">{{ $t('Susitikimas, kuriam trūksta informacijos arba neužpildyti darbotvarkės punktai') }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span class="inline-block w-3 h-3 rounded-full border-2 border-red-500 shrink-0" />
              <div>
                <span class="text-sm font-medium">{{ $t('Susitikimas be darbotvarkės') }}</span>
                <p class="text-xs text-muted-foreground">{{ $t('Susitikimas, kuriam nebuvo pridėta jokių darbotvarkės punktų') }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Coverage Section -->
        <div class="space-y-3">
          <h4 class="text-sm font-semibold text-foreground flex items-center gap-2">
            <component :is="Icons.NOTIFICATION" class="h-4 w-4" />
            {{ $t('Atstovavimo padengimas') }}
          </h4>
          <div class="space-y-2 pl-6">
            <div class="flex items-center gap-3">
              <span class="inline-block w-6 h-1 rounded-full bg-amber-500 shrink-0" />
              <div>
                <span class="text-sm font-medium">{{ $t('Pranešimas apie nebuvimą') }}</span>
                <p class="text-xs text-muted-foreground">{{ $t('Laikotarpis, kai institucija neturi posėdžių ir buvo pranešta apie tai') }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="w-6 h-4 rounded shrink-0 bg-gradient-to-r from-transparent via-emerald-500/30 to-transparent" />
              <div>
                <span class="text-sm font-medium">{{ $t('Saugumo zona') }}</span>
                <p class="text-xs text-muted-foreground">{{ $t('±14 dienų periodas aplink susitikimą, kai atstovavimas laikomas padengtų') }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Institution Indicators Section -->
        <div class="space-y-3">
          <h4 class="text-sm font-semibold text-foreground flex items-center gap-2">
            <component :is="Icons.INSTITUTION" class="h-4 w-4" />
            {{ $t('Institucijų indikatoriai') }}
          </h4>
          <div class="space-y-2 pl-6">
            <div class="flex items-center gap-3">
              <svg class="h-4 w-4 text-green-600 dark:text-green-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/>
                <path d="M2 12h20"/>
              </svg>
              <div>
                <span class="text-sm font-medium">{{ $t('Vieši posėdžiai') }}</span>
                <p class="text-xs text-muted-foreground">{{ $t('Institucija, kurios posėdžiai yra viešai prieinami') }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Time Indicators Section -->
        <div class="space-y-3">
          <h4 class="text-sm font-semibold text-foreground flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
            </svg>
            {{ $t('Laiko indikatoriai') }}
          </h4>
          <div class="space-y-2 pl-6">
            <div class="flex items-center gap-3">
              <span class="inline-block w-0.5 h-5 bg-blue-500 shrink-0" />
              <div>
                <span class="text-sm font-medium">{{ $t('Šiandienos linija') }}</span>
                <p class="text-xs text-muted-foreground">{{ $t('Vertikali linija, rodanti dabartinę datą') }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Vacation Periods Section -->
        <div class="space-y-3">
          <h4 class="text-sm font-semibold text-foreground flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
              <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
            </svg>
            {{ $t('Atostogų periodai') }}
          </h4>
          <div class="space-y-2 pl-6">
            <div class="flex items-center gap-3">
              <span class="inline-block w-6 h-4 rounded bg-amber-400/20 shrink-0" />
              <div>
                <span class="text-sm font-medium">{{ $t('Vasaros atostogos') }}</span>
                <p class="text-xs text-muted-foreground">{{ $t('Birželio–rugpjūčio mėnesiai') }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span class="inline-block w-6 h-4 rounded bg-blue-300/20 shrink-0" />
              <div>
                <span class="text-sm font-medium">{{ $t('Žiemos atostogos') }}</span>
                <p class="text-xs text-muted-foreground">{{ $t('Gruodžio pabaiga–sausio pradžia') }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span class="inline-block w-6 h-4 rounded bg-violet-300/20 shrink-0" />
              <div>
                <span class="text-sm font-medium">{{ $t('Velykų atostogos') }}</span>
                <p class="text-xs text-muted-foreground">{{ $t('Pavasario laikotarpis aplink Velykas') }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Interaction Tips -->
        <div class="p-3 bg-muted/50 rounded-lg space-y-2">
          <h4 class="text-sm font-semibold text-foreground">{{ $t('Patarimai') }}</h4>
          <ul class="text-xs text-muted-foreground space-y-1 list-disc pl-4">
            <li>{{ $t('Paspauskite ant susitikimo taško, kad peržiūrėtumėte susitikimo detales') }}</li>
            <li>{{ $t('Paspauskite ant tuščios vietos eilutėje, kad sukurtumėte naują susitikimą') }}</li>
            <li>{{ $t('Naudokite mastelio slankiklį, kad priartintumėte ar atitolintumėte laiko juostą') }}</li>
            <li>{{ $t('Slinkite horizontaliai, kad naršytumėte per laikotarpius') }}</li>
          </ul>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="$emit('update:is-open', false)">
          {{ $t('Uždaryti') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import Icons from '@/Types/Icons/filled';

defineProps<{
  isOpen: boolean;
}>();

defineEmits<{
  'update:is-open': [value: boolean];
}>();
</script>
