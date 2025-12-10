<template>
  <Dialog :open="isOpen" @update:open="$emit('update:is-open', $event)">
    <DialogContent class="sm:max-w-2xl lg:max-w-3xl xl:max-w-4xl max-h-[90vh] overflow-y-auto">
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
        <!-- Main Legend Grid - 2 columns on desktop -->
        <div class="grid gap-6 lg:grid-cols-2">
          <!-- Meetings Section -->
          <div class="space-y-3">
            <h4 class="text-sm font-semibold text-foreground flex items-center gap-2">
              <component :is="Icons.MEETING" class="h-4 w-4" />
              {{ $t('Susitikimai') }}
            </h4>
            <div class="grid gap-2 pl-6">
              <div class="flex items-center gap-3">
                <IFluentPeopleTeam20Filled class="w-4 h-4 shrink-0 text-foreground dark:text-white" />
                <div>
                  <span class="text-sm font-medium">{{ $t('Užbaigtas susitikimas') }}</span>
                  <p class="text-xs text-muted-foreground">{{ $t('Su darbotvarkės punktais') }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <IFluentPeopleTeam20Filled class="w-4 h-4 shrink-0 text-red-500" />
                <div>
                  <span class="text-sm font-medium">{{ $t('Neužbaigtas susitikimas') }}</span>
                  <p class="text-xs text-muted-foreground">{{ $t('Trūksta informacijos') }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <IFluentPeopleTeam20Regular class="w-4 h-4 shrink-0 text-red-500" />
                <div>
                  <span class="text-sm font-medium">{{ $t('Be darbotvarkės') }}</span>
                  <p class="text-xs text-muted-foreground">{{ $t('Nėra darbotvarkės punktų') }}</p>
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
            <div class="grid gap-2 pl-6">
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
            <div class="grid gap-2 pl-6">
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
              <div class="flex items-center gap-3">
                <svg class="h-4 w-4 text-blue-500 dark:text-blue-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                  <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                </svg>
                <div>
                  <span class="text-sm font-medium">{{ $t('Susijusi institucija') }}</span>
                  <p class="text-xs text-muted-foreground">{{ $t('Institucija, kurios veikla susijusi su jūsų institucijomis') }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Duty Members Section -->
          <div class="space-y-3">
            <h4 class="text-sm font-semibold text-foreground flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
              </svg>
              {{ $t('Nariai') }}
            </h4>
            <div class="grid gap-2 pl-6">
              <div class="flex items-center gap-3">
                <div class="flex -space-x-1">
                  <span class="inline-block w-4 h-4 rounded-full bg-zinc-300 dark:bg-zinc-600 ring-1 ring-zinc-400 dark:ring-zinc-500 shrink-0" />
                  <span class="inline-block w-4 h-4 rounded-full bg-zinc-300 dark:bg-zinc-600 ring-1 ring-zinc-400 dark:ring-zinc-500 shrink-0" />
                </div>
                <div>
                  <span class="text-sm font-medium">{{ $t('Nario pradžia') }}</span>
                  <p class="text-xs text-muted-foreground">{{ $t('Atstovo pareigų pradžios data institucijoje') }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="w-6 h-4 rounded shrink-0 relative overflow-hidden">
                  <svg class="absolute inset-0 w-full h-full" preserveAspectRatio="none">
                    <pattern id="legend-stripes" patternUnits="userSpaceOnUse" width="4" height="4" patternTransform="rotate(45)">
                      <rect width="4" height="4" fill="currentColor" class="text-zinc-100 dark:text-zinc-800" />
                      <line x1="0" y1="0" x2="0" y2="4" stroke="currentColor" stroke-width="2" class="text-red-400/30 dark:text-red-400/40" />
                    </pattern>
                    <rect width="100%" height="100%" fill="url(#legend-stripes)" />
                  </svg>
                </div>
                <div>
                  <span class="text-sm font-medium">{{ $t('Be aktyvių narių') }}</span>
                  <p class="text-xs text-muted-foreground">{{ $t('Laikotarpis, kai institucija neturėjo aktyvių atstovų') }}</p>
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
            <div class="grid gap-2 pl-6">
              <div class="flex items-center gap-3">
                <span class="inline-block w-0.5 h-5 bg-blue-500 shrink-0" />
                <div>
                  <span class="text-sm font-medium">{{ $t('Šiandienos linija') }}</span>
                  <p class="text-xs text-muted-foreground">{{ $t('Vertikali linija, rodanti dabartinę datą') }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <span class="inline-block w-0.5 h-5 shrink-0" style="background: repeating-linear-gradient(to bottom, rgb(239 68 68 / 0.6) 0px, rgb(239 68 68 / 0.6) 3px, transparent 3px, transparent 6px);" />
                <div>
                  <span class="text-sm font-medium">{{ $t('Centro linija') }}</span>
                  <p class="text-xs text-muted-foreground">{{ $t('Rodo, kokią datą matote ekrano centre') }}</p>
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
            <div class="grid gap-2 pl-6 sm:grid-cols-3">
              <div class="flex items-center gap-3">
                <span class="inline-block w-6 h-4 rounded bg-amber-400/20 shrink-0" />
                <div>
                  <span class="text-sm font-medium">{{ $t('Vasara') }}</span>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <span class="inline-block w-6 h-4 rounded bg-blue-300/20 shrink-0" />
                <div>
                  <span class="text-sm font-medium">{{ $t('Žiema') }}</span>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <span class="inline-block w-6 h-4 rounded bg-violet-300/20 shrink-0" />
                <div>
                  <span class="text-sm font-medium">{{ $t('Velykos') }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Keyboard Shortcuts Section -->
        <div class="p-4 bg-muted/50 rounded-lg space-y-3">
          <h4 class="text-sm font-semibold text-foreground flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.293-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd" />
            </svg>
            {{ $t('Klaviatūros spartieji klavišai') }}
          </h4>
          <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            <div class="flex items-center gap-2 text-sm">
              <kbd class="px-2 py-0.5 text-xs font-semibold bg-background border rounded">←</kbd>
              <kbd class="px-2 py-0.5 text-xs font-semibold bg-background border rounded">→</kbd>
              <span class="text-muted-foreground">{{ $t('Slinkti savaitę') }}</span>
            </div>
            <div class="flex items-center gap-2 text-sm">
              <kbd class="px-2 py-0.5 text-xs font-semibold bg-background border rounded">Shift</kbd>
              <span class="text-muted-foreground">+</span>
              <kbd class="px-2 py-0.5 text-xs font-semibold bg-background border rounded">←</kbd>
              <kbd class="px-2 py-0.5 text-xs font-semibold bg-background border rounded">→</kbd>
              <span class="text-muted-foreground">{{ $t('Slinkti mėnesį') }}</span>
            </div>
            <div class="flex items-center gap-2 text-sm">
              <kbd class="px-2 py-0.5 text-xs font-semibold bg-background border rounded">↑</kbd>
              <kbd class="px-2 py-0.5 text-xs font-semibold bg-background border rounded">↓</kbd>
              <span class="text-muted-foreground">{{ $t('Slinkti vertikaliai') }}</span>
            </div>
            <div class="flex items-center gap-2 text-sm">
              <kbd class="px-2 py-0.5 text-xs font-semibold bg-background border rounded">+</kbd>
              <kbd class="px-2 py-0.5 text-xs font-semibold bg-background border rounded">-</kbd>
              <span class="text-muted-foreground">{{ $t('Keisti mastelį') }}</span>
            </div>
            <div class="flex items-center gap-2 text-sm">
              <kbd class="px-2 py-0.5 text-xs font-semibold bg-background border rounded">Home</kbd>
              <span class="text-muted-foreground">{{ $t('Eiti į šiandien') }}</span>
            </div>
            <div class="flex items-center gap-2 text-sm">
              <kbd class="px-2 py-0.5 text-xs font-semibold bg-background border rounded">PgUp</kbd>
              <kbd class="px-2 py-0.5 text-xs font-semibold bg-background border rounded">PgDn</kbd>
              <span class="text-muted-foreground">{{ $t('Slinkti 3 mėnesius') }}</span>
            </div>
          </div>
        </div>

        <!-- Interaction Tips -->
        <div class="p-3 bg-muted/50 rounded-lg space-y-2">
          <h4 class="text-sm font-semibold text-foreground">{{ $t('Patarimai') }}</h4>
          <ul class="text-xs text-muted-foreground space-y-1 list-disc pl-4 columns-1 sm:columns-2 gap-6">
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
