<template>
  <SectionCard
    :title="$t('Dabartiniai nariai')"
    :icon="Users"
    :count="holders.length || undefined"
  >
    <template v-if="canAssign" #action>
      <Button variant="outline" size="sm" class="gap-2" @click="$emit('assign')">
        <UserPlus class="h-4 w-4" />
        {{ $t('Priskirti') }}
      </Button>
    </template>

    <!-- Vacant state -->
    <div
      v-if="holders.length === 0"
      class="flex flex-col items-center gap-3 border border-dashed border-border py-8 text-center"
    >
      <div class="flex h-12 w-12 items-center justify-center bg-muted">
        <UserX class="h-6 w-6 text-muted-foreground" />
      </div>
      <div>
        <p class="text-sm font-medium text-foreground">
          {{ $t('Pareigos neužimtos') }}
        </p>
        <p class="text-xs text-muted-foreground">
          {{ $t('Šiuo metu niekas neeina šių pareigų.') }}
        </p>
      </div>
      <Button v-if="canAssign" size="sm" class="gap-2" @click="$emit('assign')">
        <UserPlus class="h-4 w-4" />
        {{ $t('Priskirti') }}
      </Button>
    </div>

    <!-- Holders grid -->
    <div v-else class="grid grid-cols-1 items-start gap-2 sm:grid-cols-2">
      <DutyHolderCard v-for="holder in holders" :key="holder.id" :member="holder" />
    </div>
  </SectionCard>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Users, UserPlus, UserX } from '@lucide/vue';

import DutyHolderCard from './DutyHolderCard.vue';

import { SectionCard } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';

withDefaults(defineProps<{
  holders: App.Entities.User[];
  canAssign?: boolean;
}>(), {
  canAssign: false,
});

defineEmits<{
  assign: [];
}>();
</script>
