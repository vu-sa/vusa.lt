<template>
  <OverviewSection
    v-if="shortcuts.length > 0"
    :title="$t('home.create_title')"
    :icon="Plus"
    variant="home"
    data-slot="create-shortcuts"
    data-tour="quick-actions"
  >
    <!-- Rows, not buttons: the header's Veiksmai is the region's one brand action. -->
    <CollectionRowList :items="shortcuts" frame="attached" :item-key="shortcut => shortcut.id">
      <template #default="{ item: shortcut }">
        <button type="button" :class="cn(ROW_LINK_CLASS, 'group items-center')" :data-shortcut="shortcut.id" @click="shortcut.action">
          <component :is="shortcut.icon" class="size-4 shrink-0 text-muted-foreground transition-colors group-hover:text-brand" aria-hidden="true" />
          <span class="min-w-0 flex-1 font-semibold text-foreground">{{ shortcut.label }}</span>
          <ChevronRight class="size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
        </button>
      </template>
    </CollectionRowList>
  </OverviewSection>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight, Plus } from '@lucide/vue';
import { computed } from 'vue';

import { useCommandActions } from '@/Components/CommandPalette/useCommandActions';
import CollectionRowList from '@/Components/Collection/CollectionRowList.vue';
import { ROW_LINK_CLASS } from '@/Components/Collection/rowList';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(defineProps<{
  limit?: number;
}>(), {
  limit: 4,
});

const { actions } = useCommandActions();

/** What we want people to do more often comes first; "no meeting" sits beside "record a meeting" as its equal. */
const PRIORITY = ['create-new_meeting', 'create-no_meeting', 'create-new_problem'];

const shortcuts = computed(() => {
  const permitted = actions.value.filter(action => action.category === 'create');
  const prioritized = PRIORITY.flatMap(id => permitted.filter(action => action.id === id));
  const rest = permitted.filter(action => !PRIORITY.includes(action.id));
  const coveredWorkspaces = new Set(prioritized.map(action => action.workspaceKey));
  const firstPerWorkspace = rest.filter((action, index) =>
    action.workspaceKey && !coveredWorkspaces.has(action.workspaceKey)
    && rest.findIndex(candidate => candidate.workspaceKey === action.workspaceKey) === index);
  const selectedIds = new Set([...prioritized, ...firstPerWorkspace].map(action => action.id));

  return [
    ...prioritized,
    ...firstPerWorkspace,
    ...rest.filter(action => !selectedIds.has(action.id)),
  ].slice(0, props.limit);
});
</script>
