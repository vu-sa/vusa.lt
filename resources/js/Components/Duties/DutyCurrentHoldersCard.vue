<template>
  <SectionCard
    :title="$t('Dabartiniai nariai')"
    :icon="Users"
    :count="placesToOccupy ? `${holders.length} / ${placesToOccupy}` : holders.length || undefined"
  >
    <template v-if="canAssign" #action>
      <Button variant="outline" size="sm" class="gap-2" @click="$emit('assign')">
        <UserPlus class="h-4 w-4" />
        {{ $t('Priskirti narį') }}
      </Button>
    </template>

    <!-- Vacant state -->
    <div
      v-if="holders.length === 0"
      class="flex flex-col items-center gap-3 rounded-lg border border-dashed border-border py-8 text-center"
    >
      <div class="flex h-12 w-12 items-center justify-center rounded-full bg-muted">
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
        {{ $t('Priskirti narį') }}
      </Button>
    </div>

    <!-- Holders grid -->
    <div v-else class="grid grid-cols-1 items-start gap-2 sm:grid-cols-2">
      <DutyHolderCard v-for="holder in holders" :key="holder.id" :member="holder" />

      <!-- Remaining open seats -->
      <button
        v-if="canAssign && openSeats > 0"
        type="button"
        :class="[
          'flex items-center justify-center gap-2 rounded-lg border border-dashed border-border px-2.5 py-2',
          'text-sm text-muted-foreground transition-colors hover:bg-accent/50 hover:text-foreground',
        ]"
        @click="$emit('assign')"
      >
        <UserPlus class="h-4 w-4" />
        {{ openSeats === 1 ? $t('Laisva vieta') : $t(':count laisvos vietos', { count: openSeats }) }}
      </button>
    </div>
  </SectionCard>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Users, UserPlus, UserX } from 'lucide-vue-next';

import DutyHolderCard from './DutyHolderCard.vue';

import { SectionCard } from '@/Components/ui/section-card';
import { Button } from '@/Components/ui/button';

const props = withDefaults(defineProps<{
  holders: App.Entities.User[];
  placesToOccupy?: number;
  canAssign?: boolean;
}>(), {
  placesToOccupy: 0,
  canAssign: false,
});

defineEmits<{
  assign: [];
}>();

const openSeats = computed(() => Math.max(0, (props.placesToOccupy ?? 0) - props.holders.length));
</script>
