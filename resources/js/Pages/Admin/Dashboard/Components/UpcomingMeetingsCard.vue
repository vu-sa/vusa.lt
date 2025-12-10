<template>
    <Card data-tour="meetings-card" :class="[
    'flex flex-col relative overflow-hidden shadow-sm dark:shadow-zinc-950/50',
    'border-zinc-200 dark:border-zinc-600 bg-gradient-to-br from-white to-zinc-50 dark:from-zinc-800 dark:to-zinc-900'
  ]" role="region" :aria-label="$t('Tavo artėjantys susitikimai')">
    <!-- Status indicator - small amber accent when meetings exist -->
    <div :class="[
      'absolute top-0 right-0 w-12 h-12 -mr-6 -mt-6 rotate-45',
      upcomingMeetings.length > 0 ? 'bg-amber-400/60 dark:bg-amber-700/35' : 'bg-zinc-200 dark:bg-zinc-700'
    ]" aria-hidden="true" />

    <CardHeader class="pb-3 relative z-10">
      <CardTitle class="flex items-center gap-2">
        <component :is="Icons.MEETING" :class="[
          'h-5 w-5',
          upcomingMeetings.length > 0 ? 'text-amber-600 dark:text-amber-400/80' : 'text-zinc-600 dark:text-zinc-400'
        ]" aria-hidden="true" />
        {{ $t('Tavo artėjantys susitikimai') }}
      </CardTitle>
    </CardHeader>

    <CardContent class="flex-1 relative z-10 flex flex-col justify-center">
      <!-- Metrics section -->
      <div class="flex items-end gap-4 mb-6">
        <span :class="[
          'text-4xl font-bold',
          upcomingMeetings.length > 0 ? 'text-zinc-800 dark:text-zinc-100' : 'text-zinc-700 dark:text-zinc-300'
        ]" :aria-label="$t('Susitikimų skaičius') + ': ' + upcomingMeetings.length">
          <NNumberAnimation :from="0" :to="upcomingMeetings.length" />
        </span>
        <div :class="[
          'px-2 py-1 rounded-full text-xs font-medium mb-2',
          upcomingMeetings.length > 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400/80' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700/50 dark:text-zinc-300'
        ]" role="status" :aria-label="$t('Būsenos indikatorius')">
          {{ upcomingMeetings.length > 0 ? $t('Reikia dėmesio') : $t('Viskas tvarkoje') }}
        </div>
      </div>

      <!-- Content section - flex-1 to push actions down -->
      <div class="flex-1 flex flex-col justify-center min-h-[200px]">
        <div v-if="upcomingMeetings.length > 0" class="space-y-2">
          <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ upcomingMeetings.length === 1 ? $t('Kitas susitikimas') : $t('Artimiausi susitikimai') }}:
          </p>

          <!-- Show up to 3 upcoming meetings -->
          <div class="space-y-2">
            <Link
              v-for="(meeting, index) in upcomingMeetings.slice(0, 3)"
              :key="meeting.id"
              class="block p-3 bg-white/60 dark:bg-zinc-800/50 rounded-md border border-zinc-200 dark:border-zinc-700 hover:bg-white/80 dark:hover:bg-zinc-700/50 hover:border-amber-300 dark:hover:border-amber-700/50 transition-colors"
              :href="route('meetings.show', meeting.id)"
            >
              <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                  <div class="font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                    {{ formatStaticTime(new Date(meeting.start_time), {
                      month: 'short', day: 'numeric', hour: 'numeric', minute: 'numeric'
                    }) }}
                  </div>
                  <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1 truncate flex items-center gap-1">
                    {{ meeting.institutions?.[0]?.name }}
                    <Globe v-if="meeting.institutions?.[0]?.has_public_meetings" 
                      class="h-3 w-3 text-green-600 dark:text-green-400 shrink-0" 
                      :title="$t('Vieši posėdžiai')" />
                  </div>
                </div>

                <!-- Show position badge for first meeting -->
                <div v-if="index === 0" class="flex-shrink-0">
                  <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-400/80">
                    {{ $t('Kitas') }}
                  </span>
                </div>
              </div>
            </Link>
          </div>

          <!-- Show "and X more" if there are more than 3 -->
          <div v-if="upcomingMeetings.length > 3" class="text-center pt-2">
            <button
              @click="$emit('show-all-meetings')"
              class="text-xs text-zinc-600 dark:text-zinc-400 hover:text-amber-600 dark:hover:text-amber-400 hover:underline transition-colors"
            >
              {{ $t('ir dar') }} {{ upcomingMeetings.length - 3 }}...
            </button>
          </div>
        </div>

        <div v-else class="text-center py-8">
          <div class="text-4xl mb-4">
            🎉
          </div>
          <p class="text-zinc-800 dark:text-zinc-200 font-medium mb-2">
            {{ $t('Artėjančių susitikimų nėra!') }}
          </p>
          <p class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ $t('Puikus laikas planuoti naują veiklą') }}
          </p>
        </div>
      </div>

      <!-- Prominent CTA within card -->
      <div class="mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-700">
        <div class="flex justify-between items-center">
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger as-child>
                <Button data-tour="all-meetings" size="sm" variant="outline" class="flex-1 mr-2" @click="$emit('show-all-meetings')">
                  <component :is="Icons.MEETING" class="mr-2 h-4 w-4" />
                  {{ $t('Visi susitikimai') }}
                </Button>
              </TooltipTrigger>
              <TooltipContent>{{ $t('Peržiūrėkite visus savo susitikimus lentelėje') }}</TooltipContent>
            </Tooltip>
          </TooltipProvider>

          <!-- Icon-only action buttons -->
          <div class="flex gap-1">
            <TooltipProvider>
            <Tooltip>
              <TooltipTrigger as-child>
                <Button data-tour="create-meeting-action" variant="ghost" size="sm" class="h-8 w-8" @click="$emit('create-meeting')">
                  <component :is="Icons.PLUS" class="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>{{ $t('Sukurti naują susitikimą') }}</TooltipContent>
            </Tooltip>
          </TooltipProvider>

            <TooltipProvider v-if="upcomingMeetings.length > 0">
              <Tooltip>
                <TooltipTrigger as-child>
                  <Button variant="ghost" size="sm" class="h-8 w-8" as-child>
                    <Link :href="route('meetings.show', upcomingMeetings[0].id)">
                    <ArrowRight class="h-4 w-4" />
                    </Link>
                  </Button>
                </TooltipTrigger>
                <TooltipContent>{{ $t('Eiti į kitą susitikimą') }}</TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </div>
        </div>
      </div>
    </CardContent>

    <CardFooter class="border-t border-zinc-200 dark:border-zinc-600 bg-zinc-50/60 dark:bg-zinc-800/60 p-4 relative z-10">
      <!-- Meeting insights to encourage registration -->
      <div class="text-xs text-center w-full space-y-1">
        <div v-if="institutionsInsights.withoutMeetings.length > 0" class="text-zinc-600 dark:text-zinc-400">
          <div class="font-medium">
            {{ $t('Reikia dėmesio') }}:
          </div>
          <div>
            {{institutionsInsights.withoutMeetings.slice(0, 2).map(i => i.name).join(', ')}} {{ $t('be susitikimų')
            }}
          </div>
        </div>
        <div v-else-if="overdueInstitution" class="text-orange-600 dark:text-orange-400/70">
          <div class="font-medium">
            {{ $t('Senokas susitikimas') }}:
          </div>
          <div>
            {{ overdueInstitution.name }}
            ({{ overdueInstitution.daysSinceLastMeeting }} {{ $t('d.') }} / {{ overdueInstitution.periodicity }} {{ $t('d. rekomenduojama') }})
          </div>
        </div>
        <div v-else-if="approachingInstitution" class="text-amber-600 dark:text-amber-400/70">
          <div class="font-medium">
            {{ $t('Artėja susitikimo laikas') }}:
          </div>
          <div>
            {{ approachingInstitution.name }}
            ({{ approachingInstitution.daysSinceLastMeeting }} {{ $t('d.') }} / {{ approachingInstitution.periodicity }} {{ $t('d.') }})
          </div>
        </div>
        <div v-else class="text-zinc-600 dark:text-zinc-400">
          <div class="font-medium">
            {{ $t('Viskas tvarkoje') }}
          </div>
          <div>{{ $t('Nepamirškite registruoti susitikimus') }}</div>
        </div>
      </div>
    </CardFooter>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { NNumberAnimation } from 'naive-ui';
import { ArrowRight, Globe } from "lucide-vue-next";

import type { AtstovavimosMeeting, InstitutionInsights } from '../types';

import { Card, CardHeader, CardTitle, CardContent, CardFooter } from "@/Components/ui/card";
import { Button } from "@/Components/ui/button";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/Components/ui/tooltip";
import Icons from "@/Types/Icons/filled";
import { formatStaticTime } from '@/Utils/IntlTime';


interface Props {
  upcomingMeetings: AtstovavimosMeeting[];
  institutionsInsights: InstitutionInsights;
}

const props = defineProps<Props>();

// Find the first overdue institution (where days since last meeting exceeds periodicity)
const overdueInstitution = computed(() => {
  return props.institutionsInsights.withOldMeetings.find(inst => inst.isOverdue) ?? null;
});

// Find the first approaching institution (80%+ but not yet overdue)
const approachingInstitution = computed(() => {
  return props.institutionsInsights.withOldMeetings.find(inst => inst.isApproaching) ?? null;
});

const emit = defineEmits<{
  'show-all-meetings': [];
  'create-meeting': [];
}>();
</script>
