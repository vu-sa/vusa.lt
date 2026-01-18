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
        <!-- Tabs for My Institutions vs Related Institutions -->
        <Tabs v-if="relatedInstitutions.length > 0" v-model="activeTab" class="w-full">
          <TabsList class="grid w-full grid-cols-2">
            <TabsTrigger value="my">
              {{ $t('Mano institucijos') }} ({{ institutions.length }})
            </TabsTrigger>
            <TabsTrigger value="related">
              {{ $t('Susijusios institucijos') }} ({{ relatedInstitutions.length }})
            </TabsTrigger>
          </TabsList>
          
          <TabsContent value="my" class="space-y-4 mt-4">
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
          </TabsContent>
          
          <TabsContent value="related" class="space-y-4 mt-4">
            <!-- Search -->
            <div class="relative">
              <input v-model="relatedSearchQuery" type="text" :placeholder="$t('Ieškoti institucijų...')"
                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
            </div>
            
            <!-- Info about related institutions -->
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
              {{ $t('visak.related_institutions_info') }}
            </p>

            <!-- Related institution list with subscription actions -->
            <div class="space-y-2 max-h-[500px] overflow-y-auto">
              <RelatedInstitutionCard
                v-for="institution in filteredRelatedInstitutions"
                :key="institution.id"
                :institution="institution"
              />
            </div>

            <!-- Empty state -->
            <div v-if="filteredRelatedInstitutions.length === 0" class="text-center py-8 text-zinc-500 dark:text-zinc-400">
              <component :is="Icons.INSTITUTION" class="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>{{ relatedSearchQuery ? $t('Institucijų nerasta pagal paiešką') : $t('Susijusių institucijų nerasta') }}</p>
            </div>
          </TabsContent>
        </Tabs>
        
        <!-- No tabs if no related institutions -->
        <template v-else>
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
        </template>
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
import RelatedInstitutionCard from './RelatedInstitutionCard.vue';

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/Components/ui/dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import Icons from "@/Types/Icons/filled";


interface Props {
  institutions: AtstovavimosInstitution[];
  relatedInstitutions?: AtstovavimosInstitution[];
  isOpen: boolean;
  onScheduleMeeting: (institutionId: string) => void;
  onAddCheckIn: (institutionId: string) => void;
}

const props = withDefaults(defineProps<Props>(), {
  relatedInstitutions: () => [],
});

const emit = defineEmits<{
  'update:isOpen': [value: boolean];
}>();

const activeTab = ref('my');
const searchQuery = ref('');
const relatedSearchQuery = ref('');

// Filter institutions based on search
const filteredInstitutions = computed(() => {
  if (!searchQuery.value) return props.institutions;

  const query = searchQuery.value.toLowerCase();
  return props.institutions.filter(institution =>
    institution.name.toLowerCase().includes(query)
  );
});

// Filter related institutions based on search
const filteredRelatedInstitutions = computed(() => {
  if (!relatedSearchQuery.value) return props.relatedInstitutions;

  const query = relatedSearchQuery.value.toLowerCase();
  return props.relatedInstitutions.filter(institution =>
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
