<template>
  <Dialog :open="isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="max-w-[95vw] sm:max-w-[90vw] w-full max-h-[90vh] sm:max-h-[85vh] overflow-y-auto p-4 sm:p-6">
      <DialogHeader class="pb-3">
        <DialogTitle class="text-lg sm:text-xl">
          {{ $t('Visos institucijos') }}
        </DialogTitle>
        <DialogDescription class="text-sm">
          {{ $t('Peržiūrėkite visas savo institucijas ir jų aktyvumą') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4">
        <!-- Tabs for My Institutions vs Related Institutions -->
        <Tabs v-if="relatedInstitutions.length > 0" v-model="activeTab" class="w-full">
          <TabsList class="w-full overflow-x-auto scrollbar-none">
            <TabsTrigger value="my" class="flex-1 whitespace-nowrap text-xs sm:text-sm">
              {{ $t('Mano institucijos') }} ({{ institutions.length }})
            </TabsTrigger>
            <TabsTrigger value="related" class="flex-1 whitespace-nowrap text-xs sm:text-sm">
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
            <div class="space-y-2 max-h-[60vh] sm:max-h-[500px] overflow-y-auto">
              <InstitutionCompactCard
                v-for="institution in filteredInstitutions"
                :key="institution.id"
                :institution
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
              <component :is="InstitutionIconFilled" class="h-12 w-12 mx-auto mb-4 opacity-50" />
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
            <div class="space-y-2 max-h-[60vh] sm:max-h-[500px] overflow-y-auto">
              <RelatedInstitutionCard
                v-for="institution in filteredRelatedInstitutions"
                :key="institution.id"
                :institution
                :followed="subscriptionOf(institution).is_followed"
                :muted="subscriptionOf(institution).is_muted"
                :duty-based="subscriptionOf(institution).is_duty_based"
                :follow-loading="followLoading[institution.id] ?? false"
                :mute-loading="muteLoading[institution.id] ?? false"
                @toggle-follow="handleToggleFollow"
                @toggle-mute="handleToggleMute"
              />
            </div>

            <!-- Empty state -->
            <div v-if="filteredRelatedInstitutions.length === 0" class="text-center py-8 text-zinc-500 dark:text-zinc-400">
              <component :is="InstitutionIconFilled" class="h-12 w-12 mx-auto mb-4 opacity-50" />
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
          <div class="space-y-2 max-h-[60vh] sm:max-h-[500px] overflow-y-auto">
            <InstitutionCompactCard
              v-for="institution in filteredInstitutions"
              :key="institution.id"
              :institution
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
            <component :is="InstitutionIconFilled" class="h-12 w-12 mx-auto mb-4 opacity-50" />
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

import type { AtstovavimasInstitution } from '../types';

import { useInstitutionSubscription } from '../Composables/useInstitutionSubscription';

import RelatedInstitutionCard from '@/Components/Institutions/RelatedInstitutionCard.vue';
import InstitutionCompactCard from '@/Components/Institutions/InstitutionCompactCard.vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { InstitutionIconFilled } from '@/Components/icons';

interface Props {
  institutions: AtstovavimasInstitution[];
  relatedInstitutions?: AtstovavimasInstitution[];
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
    institution.name.toLowerCase().includes(query),
  );
});

// Filter related institutions based on search
const filteredRelatedInstitutions = computed(() => {
  if (!relatedSearchQuery.value) return props.relatedInstitutions;

  const query = relatedSearchQuery.value.toLowerCase();
  return props.relatedInstitutions.filter(institution =>
    institution.name.toLowerCase().includes(query),
  );
});

/**
 * Subscription state lives here rather than in each card, so the two card types
 * rendered by this dialog share one composable instance and one set of pending
 * flags instead of drifting apart.
 */
const { toggleFollow, toggleMute, followLoading, muteLoading } = useInstitutionSubscription();

const localSubscriptions = ref<Record<string, { is_followed: boolean; is_muted: boolean; is_duty_based: boolean }>>({});

const subscriptionOf = (institution: AtstovavimasInstitution) =>
  localSubscriptions.value[institution.id] ?? {
    is_followed: institution.subscription?.is_followed ?? false,
    is_muted: institution.subscription?.is_muted ?? false,
    is_duty_based: institution.subscription?.is_duty_based ?? false,
  };

const findRelated = (institutionId: string) =>
  props.relatedInstitutions.find(i => i.id === institutionId);

const handleToggleFollow = async (institutionId: string) => {
  const institution = findRelated(institutionId);
  if (!institution) return;

  const current = subscriptionOf(institution);
  const isFollowed = await toggleFollow(institutionId, current);

  localSubscriptions.value[institutionId] = {
    ...current,
    is_followed: isFollowed,
    // Unfollowing drops the mute with it, so the UI must not keep showing it.
    is_muted: isFollowed ? current.is_muted : false,
  };
};

const handleToggleMute = async (institutionId: string) => {
  const institution = findRelated(institutionId);
  if (!institution) return;

  const current = subscriptionOf(institution);
  localSubscriptions.value[institutionId] = {
    ...current,
    is_muted: await toggleMute(institutionId, current),
  };
};

const handleRemoveActiveCheckIn = (institutionId: string) => {
  router.delete(route('institutions.check-ins.destroyActive', institutionId), {
    preserveScroll: true,
    onSuccess: () => {
      // Refresh data to update UI after check-in deletion
      router.reload({ only: ['user', 'userInstitutions', 'tenantInstitutions'], preserveScroll: true });
    },
  });
};
</script>
