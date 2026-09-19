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
          class="h-9 gap-2 border border-border px-3 text-xs font-bold uppercase tracking-wide hover:border-brand hover:bg-transparent hover:text-brand"
          :aria-label="$t('shell.chrome.workspaces')"
        >
          <component :is="workspaceIcon(activeWorkspace?.key ?? 'pradzia')" class="size-4 text-brand" />
          <span>{{ activeWorkspace ? $t(activeWorkspace.label) : $t('shell.chrome.workspaces') }}</span>
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
              v-bind="ariaCurrent(workspace.key === activeWorkspace?.key, 'true')"
              :class="[
                'flex items-start gap-3 border-l-2 px-4 py-3 transition-colors hover:bg-secondary',
                workspace.key === activeWorkspace?.key ? 'border-brand-fill' : 'border-transparent',
              ]"
              @click="close"
            >
              <component :is="workspaceIcon(workspace.key)" class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
              <span class="min-w-0">
                <span class="block text-xs font-bold uppercase tracking-wide">{{ $t(workspace.label) }}</span>
                <span class="block text-sm text-muted-foreground">{{ $t(workspace.description) }}</span>
              </span>
            </Link>

            <ul
              v-if="workspace.key === activeWorkspace?.key"
              class="flex flex-wrap gap-x-4 gap-y-1 border-l-2 border-brand-fill pb-3 pl-11 pr-4"
            >
              <li v-for="section in workspace.sections" :key="section.key">
                <Link
                  :href="sectionHref(section)"
                  prefetch
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
            class="flex items-center justify-between px-4 py-3 text-sm text-muted-foreground transition-colors hover:bg-secondary hover:text-foreground"
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

import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { workspaceIcon } from '@/Constants/adminWorkspaces';
import {
  sectionHref,
  workspaceHref,
  type AdminSection,
  type AdminWorkspace,
} from '@/Composables/useAdminNavigation';
import { useHoverPopover } from '@/Composables/useHoverPopover';
import { ariaCurrent } from '@/Utils/ariaCurrent';

defineProps<{
  workspaces: AdminWorkspace[];
  activeWorkspace?: AdminWorkspace;
  activeSection?: AdminSection;
  /** Whether the user may open `/mano/administration` (Visi skyriai). */
  showAllSections?: boolean;
}>();

const { open, openedByHover, openNow, cancelClose, close, scheduleClose, onOpenChange } = useHoverPopover();

function onOpenAutoFocus(event: Event): void {
  if (openedByHover.value) {
    event.preventDefault();
  }
}
</script>
