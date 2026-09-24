<template>
  <!-- Wrapped in a plain element: reka's Popover root is renderless and swallows fallthrough
       classes, so `class="hidden md:block"` from the caller would otherwise vanish. -->
  <div data-slot="workspace-picker">
    <Popover :open @update:open="onOpenChange">
      <PopoverTrigger
        as-child
        @mouseenter="openNow"
        @mouseleave="scheduleClose"
        @focusin="cancelClose"
        @blur="scheduleClose"
      >
        <Button
          variant="ghost"
          class="h-8 gap-2 border border-border px-3 text-xs font-bold uppercase tracking-wide pointer-coarse:h-11 hover:border-brand hover:bg-transparent hover:text-brand"
          :aria-label="triggerLabel"
        >
          <component :is="workspaceIcon(activeWorkspace?.key ?? 'pradzia')" class="size-4 text-brand" />
          <span>{{ activeWorkspace ? $t(activeWorkspace.label) : $t('shell.chrome.workspaces') }}</span>
          <TaskCountBadge v-if="onPradzia" />
          <ChevronDown class="size-4 opacity-50 transition-transform" :class="{ 'rotate-180': open }" />
        </Button>
      </PopoverTrigger>

      <!-- `@close-auto-focus.prevent`: reka returns focus to the trigger on close, and on hover
           that would only be noise. `@open-auto-focus` is prevented for hover opens alone, so a
           keyboard open still lands focus in the panel. -->
      <PopoverContent
        align="start"
        class="w-[26rem] max-w-[calc(100vw-2rem)] p-0 shadow-none"
        @mouseenter="openNow"
        @mouseleave="scheduleClose"
        @focusin="openNow"
        @focusout="scheduleClose"
        @open-auto-focus="onOpenAutoFocus"
        @close-auto-focus.prevent
      >
        <ul>
          <li v-for="workspace in workspaces" :key="workspace.key">
            <Link
              :href="workspaceHref(workspace) ?? '#'"
              prefetch
              :cache-for="SHELL_PREFETCH_CACHE_FOR"
              v-bind="ariaCurrent(workspace.key === activeWorkspace?.key, 'true')"
              :class="[
                'group flex items-start gap-3 border-l-2 px-4 py-3 transition-colors hover:bg-brand/8 focus-visible:bg-brand/8 focus-visible:outline-none',
                workspace.key === activeWorkspace?.key ? 'border-brand-fill' : 'border-transparent',
              ]"
              @click="close"
            >
              <component :is="workspaceIcon(workspace.key)" class="mt-0.5 size-4 shrink-0 text-muted-foreground transition-colors group-hover:text-brand group-focus-visible:text-brand" />
              <span class="min-w-0">
                <span class="block text-xs font-bold uppercase tracking-wide transition-colors group-hover:text-brand group-focus-visible:text-brand">{{ $t(workspace.label) }}</span>
                <span class="block text-sm text-muted-foreground">{{ $t(workspace.description) }}</span>
              </span>
              <TaskCountBadge v-if="workspace.key === 'pradzia'" class="ml-auto mt-0.5" />
            </Link>

            <ul
              v-if="workspace.key === activeWorkspace?.key"
              class="flex flex-wrap gap-x-4 gap-y-1 border-l-2 border-brand-fill pb-3 pl-11 pr-4"
            >
              <li
                v-for="(section, index) in workspace.sections"
                :key="section.key"
                :class="index > 0 && section.startsGroup && 'border-l border-border pl-4'"
              >
                <Link
                  :href="sectionHref(section)"
                  prefetch
                  :cache-for="SHELL_PREFETCH_CACHE_FOR"
                  v-bind="ariaCurrent(section.key === activeSection?.key)"
                  :class="[
                    'text-sm underline-offset-4 hover:underline',
                    section.key === activeSection?.key ? 'font-semibold text-brand' : 'text-muted-foreground hover:text-foreground',
                  ]"
                  @click="close"
                >
                  {{ $t(section.label) }}
                </Link>
              </li>
            </ul>
          </li>
        </ul>

        <div v-if="showAllSections" class="border-t border-border">
          <Link
            :href="route('administration')"
            prefetch
            :cache-for="SHELL_PREFETCH_CACHE_FOR"
            class="flex items-center justify-between px-4 py-3 text-sm text-muted-foreground transition-colors hover:bg-brand/8 hover:text-brand focus-visible:bg-brand/8 focus-visible:text-brand focus-visible:outline-none"
            @click="close"
          >
            {{ $t('shell.chrome.all_sections') }}
            <ArrowRight class="size-4" />
          </Link>
        </div>
      </PopoverContent>
    </Popover>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowRight, ChevronDown } from 'lucide-vue-next';
import { computed } from 'vue';

import TaskCountBadge from './TaskCountBadge.vue';

import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { workspaceIcon } from '@/Constants/adminWorkspaces';
import {
  sectionHref,
  SHELL_PREFETCH_CACHE_FOR,
  workspaceHref,
  type AdminSection,
  type AdminWorkspace,
} from '@/Composables/useAdminNavigation';
import { useHoverPopover } from '@/Composables/useHoverPopover';
import { useTaskBadge } from '@/Composables/useTaskBadge';
import { ariaCurrent } from '@/Utils/ariaCurrent';

const props = defineProps<{
  workspaces: AdminWorkspace[];
  activeWorkspace?: AdminWorkspace;
  activeSection?: AdminSection;
  /** Whether the user may open `/mano/administration` (Visi skyriai). */
  showAllSections?: boolean;
}>();

const taskBadge = useTaskBadge();

// The count belongs to Mano alone, so the trigger carries it only there; an `aria-label`
// replaces the content, so the badge's text has to be folded in.
const onPradzia = computed(() => props.activeWorkspace?.key === 'pradzia');

const triggerLabel = computed(() => onPradzia.value && taskBadge.value
  ? `${$t('shell.chrome.workspaces')}, ${taskBadge.value.label}`
  : $t('shell.chrome.workspaces'));

const { open, openedByHover, openNow, cancelClose, close, scheduleClose, onOpenChange } = useHoverPopover();

function onOpenAutoFocus(event: Event): void {
  if (openedByHover.value) {
    event.preventDefault();
  }
}
</script>
