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
          <input v-model="searchQuery" type="text" :placeholder="$t('Ieškoti institucijų...')"
            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
        </div>

        <!-- Compact institution list -->
        <div class="space-y-2 max-h-[500px] overflow-y-auto">
          <InstitutionCompactCard
            v-for="institution in filteredInstitutions"
            :key="institution.id"
            :institution="institution"
            :show-actions="true"
            :can-schedule-meeting="true"
            :can-add-check-in="true"
            @schedule-meeting="props.onScheduleMeeting"
            @add-check-in="props.onAddCheckIn"
            @remove-active-check-in="handleRemoveActiveCheckIn(institution.id)"
          />
        </div>

        <!-- Empty state -->
        <div v-if="filteredInstitutions.length === 0" class="text-center py-8 text-zinc-500 dark:text-zinc-400">
          <component :is="Icons.INSTITUTION" class="h-12 w-12 mx-auto mb-4 opacity-50" />
          <p>{{ searchQuery ? $t('Institucijų nerasta pagal paiešką') : $t('Institucijų nerasta') }}</p>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="tsx">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { router } from '@inertiajs/vue3';

import type { AtstovavimosInstitution } from '../types';

import InstitutionCompactCard from '@/Components/Institutions/InstitutionCompactCard.vue';

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/Components/ui/dialog";
import Icons from "@/Types/Icons/filled";


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

const handleRemoveActiveCheckIn = (institutionId: string) => {
  router.delete(route('institutions.check-ins.destroyActive', institutionId), {
    preserveScroll: true,
    onSuccess: () => {
      // Refresh data to update UI after check-in deletion
      router.reload({ only: ['user', 'userInstitutions', 'tenantInstitutions'], preserveScroll: true })
    }
  })
}
</script>
