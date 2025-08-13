<template>
    <Card :class="[
    'flex flex-col relative overflow-hidden',
    upcomingMeetings.length > 0
      ? 'border-orange-200 bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-950/20 dark:to-amber-950/20'
      : 'border-gray-200 bg-gradient-to-br from-gray-50 to-zinc-50 dark:from-gray-900/20 dark:to-zinc-900/20'
  ]" role="region" :aria-label="$t('Tavo artėjantys susitikimai')">
    <!-- Status indicator -->
    <div :class="[
      'absolute top-0 right-0 w-16 h-16 -mr-8 -mt-8 rotate-45',
      upcomingMeetings.length > 0 ? 'bg-orange-200 dark:bg-orange-800' : 'bg-gray-200 dark:bg-gray-700'
    ]" aria-hidden="true" />

    <CardHeader class="pb-3 relative z-10">
      <CardTitle class="flex items-center gap-2">
        <component :is="Icons.MEETING" :class="[
          'h-5 w-5',
          upcomingMeetings.length > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-600 dark:text-gray-300'
        ]" aria-hidden="true" />
        {{ $t('Tavo artėjantys susitikimai') }}
      </CardTitle>
    </CardHeader>

    <CardContent class="flex-1 relative z-10 flex flex-col justify-center">
      <!-- Metrics section -->
      <div class="flex items-end gap-4 mb-6">
        <span :class="[
          'text-4xl font-bold',
          upcomingMeetings.length > 0 ? 'text-orange-700 dark:text-orange-300' : 'text-gray-700 dark:text-gray-300'
        ]" :aria-label="$t('Susitikimų skaičius') + ': ' + upcomingMeetings.length">
          <NNumberAnimation :from="0" :to="upcomingMeetings.length" />
        </span>
        <div :class="[
          'px-2 py-1 rounded-full text-xs font-medium mb-2',
          upcomingMeetings.length > 0 ? 'bg-orange-200 text-orange-800 dark:bg-orange-800/50 dark:text-orange-200' : 'bg-gray-200 text-gray-800 dark:bg-gray-700/50 dark:text-gray-200'
        ]" role="status" :aria-label="$t('Būsenos indikatorius')">
          {{ upcomingMeetings.length > 0 ? $t('Reikia dėmesio') : $t('Viskas tvarkoje') }}
        </div>
      </div>

      <!-- Content section - flex-1 to push actions down -->
      <div class="flex-1 flex flex-col justify-center min-h-[120px]">
        <div v-if="upcomingMeetings.length > 0" class="space-y-2">
          <p class="text-sm font-medium text-orange-800 dark:text-orange-200">
            {{ $t('Kitas susitikimas') }}:
          </p>
          <Link
            class="block p-3 bg-white/60 dark:bg-black/20 rounded-md border border-orange-200 dark:border-orange-700 hover:bg-white/80 dark:hover:bg-black/30 transition-colors"
            :href="route('meetings.show', upcomingMeetings[0].id)">
          <div class="font-semibold text-orange-900 dark:text-orange-100">
            {{ formatStaticTime(new Date(upcomingMeetings[0].start_time), {
              month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric'
            }) }}
          </div>
          <div class="text-sm text-orange-700 dark:text-orange-300 mt-1">
            {{ upcomingMeetings[0].institutions?.[0].name }}
          </div>
          </Link>
        </div>

        <div v-else class="text-center py-8">
          <div class="text-4xl mb-4">
            🎉
          </div>
          <p class="text-gray-800 dark:text-gray-200 font-medium mb-2">
            {{ $t('Artėjančių susitikimų nėra!') }}
          </p>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ $t('Puikus laikas planuoti naują veiklą') }}
          </p>
        </div>
      </div>

      <!-- Prominent CTA within card -->
      <div class="mt-6 pt-4" :class="[
        upcomingMeetings.length > 0 
          ? 'border-t border-orange-200 dark:border-orange-700' 
          : 'border-t border-gray-200 dark:border-gray-700'
      ]">
        <div class="flex justify-between items-center">
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger as-child>
                <Button size="sm" variant="outline" class="flex-1 mr-2" @click="$emit('show-all-meetings')">
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
                  <Button variant="ghost" size="sm" class="h-8 w-8" @click="$emit('create-meeting')">
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

    <CardFooter class="border-t bg-gray-50/40 dark:bg-gray-900/20 p-4 relative z-10">
      <!-- Meeting insights to encourage registration -->
      <div class="text-xs text-center w-full space-y-1">
        <div v-if="institutionsInsights.withoutMeetings.length > 0" class="text-gray-600 dark:text-gray-400">
          <div class="font-medium">
            {{ $t('Reikia dėmesio') }}:
          </div>
          <div>
            {{institutionsInsights.withoutMeetings.slice(0, 2).map(i => i.name).join(', ')}} {{ $t('be susitikimų')
            }}
          </div>
        </div>
        <div v-else-if="institutionsInsights.withOldMeetings.length > 0" class="text-amber-600 dark:text-amber-400">
          <div class="font-medium">
            {{ $t('Seniausi susitikimai') }}:
          </div>
          <div>
            {{ institutionsInsights.withOldMeetings[0].name }}
            ({{ institutionsInsights.withOldMeetings[0].daysSinceLastMeeting }} {{ $t('d.') }})
          </div>
        </div>
        <div v-else class="text-green-600 dark:text-green-400">
          <div class="font-medium">
            {{ $t('Puikus aktyvumas!') }}
          </div>
          <div>{{ $t('Visi susitikimai aktualūs') }}</div>
        </div>
      </div>
    </CardFooter>
  </Card>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { NNumberAnimation } from 'naive-ui';
import { ArrowRight } from "lucide-vue-next";

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

const emit = defineEmits<{
  'show-all-meetings': [];
  'create-meeting': [];
}>();
</script>
