<template>
  <div class="space-y-6">
    <!-- Active Members Section -->
    <section v-if="activeMembers.length > 0">
      <div class="flex items-center gap-2 mb-4">
        <div class="h-2 w-2 rounded-full bg-green-500"></div>
        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
          {{ $t('Aktyvūs nariai') }}
        </h3>
        <Badge variant="secondary" class="text-xs">
          {{ activeMembers.length }}
        </Badge>
      </div>
      
      <div class="space-y-3">
        <MemberHistoryCard
          v-for="member in activeMembers"
          :key="`active-${member.id}-${member.pivot?.id}`"
          :member="member"
          :is-active="true"
          :show-contact="showContact"
          :can-edit="canEdit"
          @edit-period="handleEditPeriod"
        />
      </div>
    </section>

    <!-- Historical Members Section -->
    <section v-if="historicalMembers.length > 0">
      <Collapsible v-model:open="isHistoryOpen" class="space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="h-2 w-2 rounded-full bg-zinc-400"></div>
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
              {{ $t('Istoriniai nariai') }}
            </h3>
            <Badge variant="outline" class="text-xs">
              {{ historicalMembers.length }}
            </Badge>
          </div>
          
          <CollapsibleTrigger as-child>
            <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
              <ChevronDown 
                class="h-4 w-4 transition-transform duration-200" 
                :class="{ 'rotate-180': isHistoryOpen }"
              />
              <span class="sr-only">{{ $t('Rodyti istorinius narius') }}</span>
            </Button>
          </CollapsibleTrigger>
        </div>

        <CollapsibleContent class="space-y-3">
          <MemberHistoryCard
            v-for="member in historicalMembers"
            :key="`historical-${member.id}-${member.pivot?.id}`"
            :member="member"
            :is-active="false"
            :show-contact="showContact"
            :can-edit="canEdit"
            @edit-period="handleEditPeriod"
          />
        </CollapsibleContent>
      </Collapsible>
    </section>

    <!-- Empty State -->
    <div v-if="members.length === 0" class="text-center py-12">
      <div class="mx-auto h-16 w-16 bg-zinc-100 dark:bg-zinc-800 rounded-full flex items-center justify-center mb-4">
        <History class="h-8 w-8 text-zinc-400" />
      </div>
      <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">
        {{ $t('Nėra istorinių duomenų') }}
      </h3>
      <p class="text-zinc-500 dark:text-zinc-400">
        {{ $t('Šiose pareigose dar niekas nėra ėjęs anksčiau.') }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { router } from '@inertiajs/vue3';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import { ChevronDown, History } from 'lucide-vue-next';
import MemberHistoryCard from './MemberHistoryCard.vue';

const props = withDefaults(defineProps<{
  members: App.Entities.User[];
  showContact?: boolean;
  canEdit?: boolean;
}>(), {
  showContact: false,
  canEdit: false,
});

const emit = defineEmits<{
  'edit-period': [member: App.Entities.User];
}>();

// Collapsed by default if there are more than 3 historical members
const isHistoryOpen = ref(false);

// Split members into active and historical based on current date
const activeMembers = computed(() => {
  const now = new Date();
  return props.members
    .filter(member => {
      if (!member.pivot) return false;
      // No end_date means currently active
      if (!member.pivot.end_date) return true;
      // Has end_date but it's in the future
      return new Date(member.pivot.end_date) >= now;
    })
    .sort((a, b) => {
      // Sort by start_date (most recent first)
      return new Date(b.pivot?.start_date || 0).getTime() - new Date(a.pivot?.start_date || 0).getTime();
    });
});

const historicalMembers = computed(() => {
  const now = new Date();
  return props.members
    .filter(member => {
      if (!member.pivot) return false;
      // Must have an end_date that's in the past
      if (!member.pivot.end_date) return false;
      return new Date(member.pivot.end_date) < now;
    })
    .sort((a, b) => {
      // Sort by end_date (most recent first)
      return new Date(b.pivot?.end_date || 0).getTime() - new Date(a.pivot?.end_date || 0).getTime();
    });
});

const handleEditPeriod = (member: App.Entities.User) => {
  if (member.pivot?.id) {
    router.visit(route('dutiables.edit', member.pivot.id));
  }
  emit('edit-period', member);
};
</script>
