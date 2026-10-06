<template>
  <!-- Phones reach the same actions through the bottom bar's +, so tasks come first there. -->
  <OverviewSection
    v-if="shortcuts.length > 0"
    :title="$t('home.create_title')"
    :icon="Plus"
    variant="home"
    class="max-md:hidden"
    data-slot="create-shortcuts"
    data-tour="quick-actions"
  >
    <div class="flex flex-wrap gap-3">
      <Button
        v-for="(shortcut, index) in shortcuts"
        :key="shortcut.id"
        :variant="index === 0 ? 'brand' : 'outline'"
        voice="sentence"
        class="whitespace-normal text-left"
        @click="shortcut.action"
      >
        <component :is="shortcut.icon" class="size-4" aria-hidden="true" />
        {{ shortcut.label }}
      </Button>
    </div>
  </OverviewSection>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Plus } from 'lucide-vue-next';
import { computed } from 'vue';

import { useCommandActions } from '@/Components/CommandPalette/useCommandActions';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import { Button } from '@/Components/ui/button';

const props = withDefaults(defineProps<{
  limit?: number;
}>(), {
  limit: 4,
});

const { actions } = useCommandActions();

const shortcuts = computed(() => {
  const permitted = actions.value.filter(action => action.category === 'create');
  const selected = permitted.filter((action, index) =>
    action.workspaceKey && permitted.findIndex(candidate => candidate.workspaceKey === action.workspaceKey) === index);
  const selectedIds = new Set(selected.map(action => action.id));

  return [...selected, ...permitted.filter(action => !selectedIds.has(action.id))].slice(0, props.limit);
});
</script>
