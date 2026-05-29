<template>
  <SidebarGroup v-if="visibleActions.length > 0" class="group-data-[collapsible=icon]:hidden" data-tour="quick-actions">
    <SidebarGroupLabel class="flex items-center justify-between">
      <span class="flex items-center gap-2">
        <Sparkles class="h-3 w-3 text-muted-foreground" />
        {{ sectionTitle }}
      </span>
      <QuickActionSettingsPopover v-if="availableQuickActions.length > 1" />
    </SidebarGroupLabel>
    <SidebarGroupContent>
      <SidebarMenu>
        <SidebarMenuItem v-for="action in visibleActions" :key="action.key">
          <SidebarMenuButton
            :tooltip="action.title"
            class="transition-colors"
            @click="action.execute(emit)"
          >
            <div
              :class="[
                'flex items-center justify-center rounded-md p-1 bg-gradient-to-br transition-colors',
                'group-data-[density=compact]/density:p-0.5',
                action.gradient
              ]"
            >
              <component :is="action.icon" class="h-4 w-4" />
            </div>
            <span>{{ action.title }}</span>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarGroupContent>
  </SidebarGroup>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Sparkles } from 'lucide-vue-next';

import { useUIPreferences } from '@/Composables/useUIPreferences';
import { useAvailableQuickActions } from '@/Composables/useQuickActions';
import QuickActionSettingsPopover from '@/Components/Sidebar/QuickActionSettingsPopover.vue';
import {
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/Components/ui/sidebar';

const emit = defineEmits<{
  (e: 'newMeeting'): void;
  (e: 'newNews'): void;
  (e: 'newReservation'): void;
}>();

const { isQuickActionVisible } = useUIPreferences();
const { available: availableQuickActions } = useAvailableQuickActions();

const visibleActions = computed(() => {
  return availableQuickActions.value.filter(meta => isQuickActionVisible(meta.key as any));
});

const sectionTitle = $t('Greiti veiksmai');
</script>
