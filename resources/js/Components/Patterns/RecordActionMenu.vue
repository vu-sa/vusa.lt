<template>
  <component
    :is="spotlight ? SpotlightPopover : 'span'"
    v-if="actions.length"
    v-bind="spotlight ? { title: menuLabel, description: $t('Kiti eilutės veiksmai yra šiame meniu.'), isDismissed: featureSpotlight.isDismissed.value } : {}"
    class="inline-flex shrink-0"
    data-slot="record-action-menu"
    @dismiss="dismissSpotlight"
  >
    <DropdownMenu @update:open="open => open && dismissSpotlight()">
      <DropdownMenuTrigger as-child>
        <Button
          variant="outline" size="icon" voice="sentence"
          :class="['hidden pointer-coarse:size-11 md:inline-flex', triggerSize === 'default' && 'size-11']"
          :aria-label="menuLabel" :title="menuLabel" data-testid="record-overflow-trigger"
        >
          <MoreHorizontal class="size-4" aria-hidden="true" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <template v-for="(action, index) in sortedActions" :key="action.key">
          <DropdownMenuSeparator v-if="startsDestructiveGroup(index)" />
          <DropdownMenuItem
            :data-action="action.key"
            :disabled="action.disabled || action.loading"
            :aria-busy="action.loading || undefined"
            :class="['pointer-coarse:min-h-11', action.destructive && 'text-destructive focus:text-destructive']"
            :as-child="Boolean(action.href)"
            @select="!action.href && select(action)"
          >
            <component
              :is="action.href ? (action.external ? 'a' : Link) : 'span'"
              :href="action.href"
              :target="action.external ? '_blank' : undefined"
              :rel="action.external ? 'noopener noreferrer' : undefined"
              class="flex items-center gap-2"
              @click="event => guardLink(action, event)"
            >
              <component :is="action.icon" v-if="action.icon" :class="['size-4', action.loading && 'animate-spin']" aria-hidden="true" />
              {{ action.label }}
            </component>
          </DropdownMenuItem>
        </template>
      </DropdownMenuContent>
    </DropdownMenu>
    <Sheet v-model:open="sheetOpen" @update:open="open => open && dismissSpotlight()">
      <SheetTrigger as-child>
        <Button
          variant="outline" size="icon" voice="sentence"
          :class="['pointer-coarse:size-11 md:hidden', triggerSize === 'default' && 'size-11']"
          :aria-label="menuLabel" :title="menuLabel"
        >
          <MoreHorizontal class="size-4" aria-hidden="true" />
        </Button>
      </SheetTrigger>
      <SheetContent side="bottom" class="max-h-[85vh] overflow-y-auto">
        <SheetHeader><SheetTitle>{{ menuLabel }}</SheetTitle></SheetHeader>
        <div class="grid px-4 pb-6">
          <template v-for="(action, index) in sortedActions" :key="action.key">
            <div v-if="startsDestructiveGroup(index)" class="my-2 border-t border-border" role="separator" />
            <Button
              :data-action="action.key" variant="ghost" voice="sentence"
              :as-child="Boolean(action.href)"
              :disabled="action.disabled || action.loading"
              :aria-busy="action.loading || undefined"
              :class="['min-h-11 justify-start', action.destructive && 'text-destructive']"
              @click="select(action)"
            >
              <component
                :is="action.href ? (action.external ? 'a' : Link) : 'span'"
                :href="action.href"
                :target="action.external ? '_blank' : undefined"
                :rel="action.external ? 'noopener noreferrer' : undefined"
                class="flex items-center gap-2"
                @click="event => guardLink(action, event)"
              >
                <component :is="action.icon" v-if="action.icon" :class="['size-4', action.loading && 'animate-spin']" aria-hidden="true" />
                {{ action.label }}
              </component>
            </Button>
          </template>
        </div>
      </SheetContent>
    </Sheet>
  </component>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { MoreHorizontal } from '@lucide/vue';
import { trans as $t } from 'laravel-vue-i18n';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { Button } from '@/Components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/Components/ui/sheet';
import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';

const props = defineProps<{ actions: AdminRecordAction[]; triggerSize?: 'compact' | 'default'; label?: string; spotlight?: boolean }>();
const emit = defineEmits<{ select: [key: string]; navigate: [key: string] }>();
const sheetOpen = ref(false);
const featureSpotlight = useFeatureSpotlight('collection-row-actions-v1');
function dismissSpotlight(): void {
  if (props.spotlight) void featureSpotlight.dismiss();
}
const menuLabel = computed(() => props.label ?? $t('Daugiau veiksmų'));
const sortedActions = computed(() => [...props.actions.filter(action => !action.destructive), ...props.actions.filter(action => action.destructive)]);
const startsDestructiveGroup = (index: number) => index > 0
  && sortedActions.value[index]?.destructive
  && !sortedActions.value[index - 1]?.destructive;

function guardLink(action: AdminRecordAction, event: MouseEvent): void {
  if (action.disabled || action.loading) {
    event.preventDefault();
    event.stopPropagation();
  }
  else if (action.href) emit('navigate', action.key);
}

function select(action: AdminRecordAction): void {
  if (action.disabled || action.loading) return;
  sheetOpen.value = false;
  if (!action.href) emit('select', action.key);
}
</script>
