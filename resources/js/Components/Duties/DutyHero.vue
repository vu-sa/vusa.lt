<template>
  <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg p-6 mb-6">
    <div class="flex items-start justify-between mb-4">
      <div class="flex items-center gap-4">
        <div class="h-16 w-16 bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-700 dark:to-zinc-800 rounded-lg flex items-center justify-center text-zinc-600 dark:text-zinc-300 text-lg font-medium border border-zinc-200 dark:border-zinc-600">
          {{ getInitials(duty.name) }}
        </div>
        <div>
          <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
            {{ duty.name }}
          </h1>
          <div class="mt-1 inline-flex items-center gap-4">
          <span v-if="duty.institution?.name" class="text-sm text-zinc-500 dark:text-zinc-400 flex items-center gap-1">
            <Building class="h-3 w-3" />
            {{ duty.institution.name }}
          </span>
          <!-- Duty email if available -->
          <span v-if="duty.email" class="text-sm text-zinc-500 dark:text-zinc-400 flex items-center gap-1">
            <IFluentMail20Regular class="h-3 w-3" />
            {{ duty.email }}
          </span>
          </div>
        </div>
      </div>
      
      <div class="flex items-center gap-2">
        <Button
          v-if="canAssignMembers"
          variant="default"
          size="sm"
          @click="$emit('assign-member')"
          class="gap-2"
        >
          <UserPlus class="h-4 w-4" />
          {{ $t('Priskirti narį') }}
        </Button>
        
        <Button
          v-if="canManageDuty"
          variant="outline"
          size="sm"
          @click="$emit('manage-duty')"
          class="gap-2"
        >
          <Settings class="h-4 w-4" />
          {{ $t('Valdyti pareigas') }}
        </Button>
        
        <slot name="actions" />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <!-- Occupancy Status -->
      <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-4">
        <div class="flex items-center gap-2 mb-2">
          <Users class="h-4 w-4 text-blue-500" />
          <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $t('Užimtumas') }}
          </span>
        </div>
        <div class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
          {{ currentMembersCount }} / {{ maxPositions }}
        </div>
        <div class="text-xs" :class="occupancyStatusColor">
          {{ occupancyStatus }}
        </div>
      </div>

      <!-- Average Tenure -->
      <!-- <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-4"> -->
      <!--   <div class="flex items-center gap-2 mb-2"> -->
      <!--     <Clock class="h-4 w-4 text-green-500" /> -->
      <!--     <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"> -->
      <!--       {{ $t('Vidutinis stažas') }} -->
      <!--     </span> -->
      <!--   </div> -->
      <!--   <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-100"> -->
      <!--     {{ averageTenure }} -->
      <!--   </div> -->
      <!--   <div class="text-xs text-zinc-500 dark:text-zinc-400"> -->
      <!--     {{ $t('dabartinių narių') }} -->
      <!--   </div> -->
      <!-- </div> -->

      <!-- Total Historical Members -->
      <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-4">
        <div class="flex items-center gap-2 mb-2">
          <History class="h-4 w-4 text-amber-500" />
          <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $t('Istoriniai nariai') }}
          </span>
        </div>
        <div class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
          {{ totalHistoricalMembers }}
        </div>
        <div class="text-xs text-zinc-500 dark:text-zinc-400">
          {{ $t('per visą laiką') }}
        </div>
      </div>

      <!-- Duty Status -->
      <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-4">
        <div class="flex items-center gap-2 mb-2">
          <Badge class="h-4 w-4 text-purple-500" />
          <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $t('Statusas') }}
          </span>
        </div>
        <div class="flex items-center gap-2">
          <div 
            class="w-2 h-2 rounded-full" 
            :class="isActive ? 'bg-green-500' : 'bg-red-500'"
          ></div>
          <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
            {{ isActive ? $t('Aktyvios') : $t('Neaktyvios') }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Button } from '@/Components/ui/button';
import { Building, UserPlus, Settings, Users, Clock, History, Badge } from 'lucide-vue-next';

const props = defineProps<{
  duty: App.Entities.Duty;
  currentMembers: App.Entities.User[];
  historicalMembers: App.Entities.User[];
  canAssignMembers?: boolean;
  canManageDuty?: boolean;
}>();

const emit = defineEmits<{
  'assign-member': [];
  'manage-duty': [];
}>();

const maxPositions = computed(() => {
  return props.duty.places_to_occupy || 0;
});

const currentMembersCount = computed(() => {
  return props.currentMembers.length;
});

const totalHistoricalMembers = computed(() => {
  return props.historicalMembers.length;
});

const isActive = computed(() => {
  return currentMembersCount.value > 0;
});

const occupancyStatus = computed(() => {
  const current = currentMembersCount.value;
  const max = maxPositions.value;
  
  if (current === 0) return $t('Neužimta');
  if (current < max) return $t('Dalinai užimta');
  if (current === max) return $t('Pilnai užimta');
  return $t('Viršija limitą');
});

const occupancyStatusColor = computed(() => {
  const current = currentMembersCount.value;
  const max = maxPositions.value;
  
  if (current === 0) return 'text-zinc-500 dark:text-zinc-400';
  if (current < max) return 'text-amber-600 dark:text-amber-400';
  if (current === max) return 'text-green-600 dark:text-green-400';
  return 'text-red-600 dark:text-red-400';
});

const averageTenure = computed(() => {
  if (!props.currentMembers.length) return $t('N/A');
  
  const now = new Date();
  const totalMonths = props.currentMembers.reduce((sum, member) => {
    const startDate = member.pivot?.start_date ? new Date(member.pivot.start_date) : now;
    const monthsDiff = (now.getFullYear() - startDate.getFullYear()) * 12 + 
                       (now.getMonth() - startDate.getMonth());
    return sum + Math.max(0, monthsDiff);
  }, 0);
  
  const avgMonths = Math.round(totalMonths / props.currentMembers.length);
  
  if (avgMonths < 1) return $t('< 1 mėn.');
  if (avgMonths < 12) return avgMonths + ' ' + $t('mėn.');
  
  const years = Math.floor(avgMonths / 12);
  const months = avgMonths % 12;
  
  let result = years + ' ' + (years === 1 ? $t('metai') : $t('metai'));
  if (months > 0) {
    result += ' ' + months + ' ' + $t('mėn.');
  }
  return result;
});

const getInitials = (name?: string) => {
  if (!name) return 'PA';
  
  const words = name.split(' ').filter(word => word.length > 0);
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase();
  }
  if (words.length === 1) {
    return words[0].substring(0, 2).toUpperCase();
  }
  return 'PA';
};
</script>
