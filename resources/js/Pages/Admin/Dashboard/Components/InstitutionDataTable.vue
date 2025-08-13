<template>
  <Dialog :open="isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-[95vw] w-full max-h-[85vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>{{ $t('Visos institucijos') }}</DialogTitle>
        <DialogDescription>
          {{ $t('Peržiūrėkite visas savo institucijas ir jų aktyvumą') }}
        </DialogDescription>
      </DialogHeader>
      
      <div class="space-y-4">
        <!-- Search -->
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="$t('Ieškoti institucijų...')"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <!-- Compact institution list -->
        <div class="space-y-2 max-h-[500px] overflow-y-auto">
          <div
            v-for="institution in filteredInstitutions"
            :key="institution.id"
            class="flex items-center justify-between p-4 border-2 rounded-xl hover:shadow-sm hover:bg-gray-50/30 transition-all duration-200"
            :class="getRowBackgroundClass(institution)"
          >
            <!-- Left section: Institution info -->
            <div class="flex-1 min-w-0 mr-4">
              <div class="flex items-center gap-3">
                <!-- Status dot -->
                <div class="w-3 h-3 rounded-full flex-shrink-0" :class="getStatusDotClass(institution)" />
                
                <!-- Institution name and status -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <Link 
                      :href="route('institutions.show', institution.id)"
                      class="font-medium text-sm hover:underline text-blue-600 truncate"
                    >
                      {{ institution.name }}
                    </Link>
                    
                    <!-- Status badge -->
                    <span
                      v-if="institution.active_check_in"
                      class="text-xs px-2 py-0.5 rounded-full flex-shrink-0"
                      :class="institution.active_check_in && isBlackout(institution.active_check_in?.mode) 
                        ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' 
                        : 'bg-sky-100 text-sky-700 border border-sky-200'"
                    >
                      {{ isBlackout(institution.active_check_in?.mode) ? $t('Padengta') : $t('Heads-up') }}
                      <span v-if="institution.active_check_in?.until_date" class="opacity-75">
                        {{ $t('iki') }} {{ formatDate(institution.active_check_in.until_date) }}
                      </span>
                    </span>
                    <span
                      v-else-if="!getLastMeetingDate(institution)"
                      class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 border border-gray-200 flex-shrink-0"
                    >
                      {{ $t('Reikia susitikimo') }}
                    </span>
                    <span
                      v-else-if="getDaysSinceLastMeeting(institution) !== undefined && getDaysSinceLastMeeting(institution)! > 60"
                      class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 border border-amber-200 flex-shrink-0"
                    >
                      {{ $t('Senokas susitikimas') }}
                    </span>
                  </div>
                  
                  <!-- Last meeting info -->
                  <div v-if="!institution.active_check_in && getLastMeetingDate(institution)" class="text-xs text-gray-500">
                    {{ $t('Paskutinis susitikimas') }}: {{ formatDate(getLastMeetingDate(institution)!) }}
                    <span v-if="getDaysSinceLastMeeting(institution) !== undefined && getDaysSinceLastMeeting(institution)! > 30" class="text-amber-600">
                      ({{ getDaysSinceLastMeeting(institution) }} {{ $t('d.') }})
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right section: Action buttons -->
            <div class="flex items-center gap-2 flex-shrink-0">
              <!-- Schedule Meeting button -->
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger as-child>
                    <Button 
                      variant="ghost" 
                      size="sm" 
                      class="h-8 px-3 text-xs hover:bg-blue-50 hover:text-blue-700 transition-colors"
                      @click="props.onScheduleMeeting(institution.id)"
                    >
                      <component :is="Icons.MEETING" class="h-3 w-3 mr-1" />
                      {{ $t('Susitikimas') }}
                    </Button>
                  </TooltipTrigger>
                  <TooltipContent>{{ $t('Suplanuoti naują susitikimą') }}</TooltipContent>
                </Tooltip>
              </TooltipProvider>

              <!-- Add Check-in button (only if no active check-in) -->
              <TooltipProvider v-if="!institution.active_check_in">
                <Tooltip>
                  <TooltipTrigger as-child>
                    <Button 
                      variant="ghost" 
                      size="sm" 
                      class="h-8 w-8 opacity-60 hover:opacity-100 hover:bg-orange-50 hover:text-orange-700 transition-all"
                      @click="props.onAddCheckIn(institution.id)"
                    >
                      <component :is="Icons.NOTIFICATION" class="h-3 w-3" />
                    </Button>
                  </TooltipTrigger>
                  <TooltipContent>{{ $t('Pridėti pažymą') }}</TooltipContent>
                </Tooltip>
              </TooltipProvider>

              <!-- View details button -->
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger as-child>
                    <Button 
                      variant="ghost" 
                      size="sm" 
                      class="h-8 w-8 opacity-60 hover:opacity-100 transition-opacity"
                      @click="router.visit(route('institutions.show', institution.id))"
                    >
                      <component :is="Icons.CHEVRON_DOWN" class="h-3 w-3 rotate-[-90deg]" />
                    </Button>
                  </TooltipTrigger>
                  <TooltipContent>{{ $t('Peržiūrėti instituciją') }}</TooltipContent>
                </Tooltip>
              </TooltipProvider>
            </div>
          </div>
        </div>

        <!-- Empty state -->
        <div v-if="filteredInstitutions.length === 0" class="text-center py-8 text-gray-500">
          <component :is="Icons.INSTITUTION" class="h-12 w-12 mx-auto mb-4 opacity-50" />
          <p>{{ searchQuery ? $t('Institucijų nerasta pagal paiešką') : $t('Institucijų nerasta') }}</p>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="tsx">
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/Components/ui/dialog";
import { Button } from "@/Components/ui/button";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/Components/ui/tooltip";
import Icons from "@/Types/Icons/filled";
import { formatStaticTime } from '@/Utils/IntlTime';

import type { AtstovavimosInstitution } from '../types';

interface Props {
  institutions: AtstovavimosInstitution[];
  isOpen: boolean;
  onScheduleMeeting: (institutionId: string) => void;
  onAddCheckIn: (institutionId: string) => void;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:isOpen': [value: boolean];
}>();

const searchQuery = ref('');

// Filter institutions based on search
const filteredInstitutions = computed(() => {
  if (!searchQuery.value) return props.institutions;
  
  const query = searchQuery.value.toLowerCase();
  return props.institutions.filter(institution => 
    institution.name.toLowerCase().includes(query)
  );
});

// Helper functions (same logic as in InstitutionCheckInCard)
function isBlackout(mode: string | undefined): boolean {
  return mode === 'blackout';
}

function getLastMeetingDate(institution: AtstovavimosInstitution): Date | null {
  if (!Array.isArray(institution.meetings) || institution.meetings.length === 0) return null;
  
  const sortedMeetings = [...institution.meetings].sort((a, b) => 
    new Date(b.start_time).getTime() - new Date(a.start_time).getTime()
  );
  
  const lastMeeting = sortedMeetings[0];
  return lastMeeting ? new Date(lastMeeting.start_time) : null;
}

function getDaysSinceLastMeeting(institution: AtstovavimosInstitution): number | undefined {
  const lastMeetingDate = getLastMeetingDate(institution);
  if (!lastMeetingDate) return undefined;
  
  const now = new Date();
  const diffTime = Math.abs(now.getTime() - lastMeetingDate.getTime());
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

function getStatusDotClass(institution: AtstovavimosInstitution): string {
  if (institution.active_check_in && isBlackout(institution.active_check_in.mode)) {
    return 'bg-emerald-500 ring-2 ring-white';
  }
  if (institution.active_check_in) {
    return 'bg-sky-500 ring-2 ring-white';
  }
  if (!getLastMeetingDate(institution)) {
    return 'bg-gray-500 ring-2 ring-white';
  }
  const days = getDaysSinceLastMeeting(institution);
  if (days !== undefined && days > 60) {
    return 'bg-amber-500 ring-2 ring-white';
  }
  return 'bg-gray-400 ring-2 ring-white';
}

function getRowBackgroundClass(institution: AtstovavimosInstitution): string {
  if (institution.active_check_in && isBlackout(institution.active_check_in.mode)) {
    return 'border-emerald-200';
  }
  if (institution.active_check_in) {
    return 'border-sky-200';
  }
  if (!getLastMeetingDate(institution)) {
    return 'border-gray-300';
  }
  const days = getDaysSinceLastMeeting(institution);
  if (days !== undefined && days > 60) {
    return 'border-amber-200';
  }
  return 'border-gray-200';
}

function formatDate(date: Date | string): string {
  const dateObj = typeof date === 'string' ? new Date(date) : date;
  return formatStaticTime(dateObj, { month: 'short', day: 'numeric' });
}
</script>
