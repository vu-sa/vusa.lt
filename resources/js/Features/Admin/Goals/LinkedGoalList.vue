<template>
  <ul v-if="goals.length" class="divide-y divide-border border-y border-border" data-testid="linked-goal-list">
    <li v-for="goal in goals" :key="goal.id" class="flex min-h-11 items-center gap-3 px-2 py-2 sm:px-3">
      <Link :href="route('goals.show', goal.id)" class="flex min-w-0 flex-1 items-center gap-3 hover:text-brand">
        <GoalIcon class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
        <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ goal.title }}</span>
        <span class="text-xs text-muted-foreground">{{ goal.tenant }}</span>
      </Link>
      <StatusBadge :status="goalStatuses[goal.status]" />
      <Button
        v-if="removable && goal.can_update !== false"
        variant="ghost"
        size="sm"
        voice="sentence"
        class="pointer-coarse:min-h-11"
        @click="emit('remove', goal)"
      >
        {{ $t('goals.problems.unlink') }}
      </Button>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import type { LinkedGoal } from './types';

import { GoalIcon } from '@/Components/icons';
import { StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { goalStatuses } from '@/Constants/statuses';

defineProps<{ goals: LinkedGoal[]; removable?: boolean }>();

const emit = defineEmits<{ remove: [goal: LinkedGoal] }>();
</script>
