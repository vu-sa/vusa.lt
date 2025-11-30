<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
          {{ title }}
        </h3>
        <p v-if="subtitle" class="text-sm text-zinc-500 dark:text-zinc-400">
          {{ subtitle }}
        </p>
      </div>
      
      <div class="flex items-center gap-2">
        <Button
          v-if="canAddMember"
          variant="outline"
          size="sm"
          @click="$emit('add-member')"
          class="gap-2"
        >
          <UserPlus class="h-4 w-4" />
          {{ $t('Pridėti narį') }}
        </Button>
        <slot name="actions" />
      </div>
    </div>

    <!-- Members Grid -->
    <div v-if="members.length > 0" 
         class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <MemberCard
        v-for="member in members"
        :key="member.id"
        :member="member"
        :institution="institution"
        :max-positions="maxPositions"
        :show-contact="showContact"
        :show-actions="showActions"
        :can-edit="canEdit"
        @view-profile="$emit('view-profile', $event)"
        @edit-member="$emit('edit-member', $event)"
      />
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <div class="mx-auto h-16 w-16 bg-zinc-100 dark:bg-zinc-800 rounded-full flex items-center justify-center mb-4">
        <Users class="h-8 w-8 text-zinc-400" />
      </div>
      <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">
        {{ emptyTitle || $t('Nėra narių') }}
      </h3>
      <p class="text-zinc-500 dark:text-zinc-400 mb-4">
        {{ emptyDescription || $t('Šioje institucijoje dar nėra priskirta narių.') }}
      </p>
      <Button
        v-if="canAddMember"
        variant="default"
        @click="$emit('add-member')"
        class="gap-2"
      >
        <UserPlus class="h-4 w-4" />
        {{ $t('Pridėti pirmą narį') }}
      </Button>
    </div>

    <!-- Capacity Warning -->
    <div v-if="showCapacityWarning" 
         class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-900/50 rounded-lg p-4">
      <div class="flex items-start gap-3">
        <AlertTriangle class="h-5 w-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" />
        <div>
          <h4 class="font-medium text-amber-800 dark:text-amber-200">
            {{ $t('Viršytas narių limitas') }}
          </h4>
          <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
            {{ $t('Šioje institucijoje yra') }} {{ members.length }} {{ $t('nariai, bet numatyta tik') }} {{ maxPositions }} {{ $t('pozicijų') }}.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Button } from '@/Components/ui/button';
import { Users, UserPlus, AlertTriangle } from 'lucide-vue-next';
import MemberCard from './MemberCard.vue';

const props = defineProps<{
  title: string;
  subtitle?: string;
  members: App.Entities.User[];
  institution?: App.Entities.Institution;
  maxPositions?: number;
  showContact?: boolean;
  showActions?: boolean;
  canEdit?: boolean;
  canAddMember?: boolean;
  emptyTitle?: string;
  emptyDescription?: string;
}>();

const emit = defineEmits<{
  'add-member': [];
  'view-profile': [member: App.Entities.User];
  'edit-member': [member: App.Entities.User];
}>();

const showCapacityWarning = computed(() => {
  return props.maxPositions && props.members.length > props.maxPositions;
});
</script>