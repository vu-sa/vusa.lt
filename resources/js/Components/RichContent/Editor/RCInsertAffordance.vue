<template>
  <div :class="['pointer-events-none absolute inset-x-0 z-20 flex items-center justify-center', compact ? '-top-2.5 h-5' : '-top-6 h-12']">
    <div :class="['group/insert pointer-events-auto relative flex items-center justify-center', compact ? 'h-5 w-28' : 'h-12 w-32']">
      <div class="pointer-events-none absolute inset-x-0 top-1/2 h-px -translate-y-1/2 bg-transparent transition-colors group-hover/insert:bg-zinc-300 dark:group-hover/insert:bg-zinc-600" />
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <button :class="['relative z-10 flex items-center justify-center rounded-full border border-zinc-300 bg-white text-zinc-500 shadow-sm transition-opacity hover:border-zinc-400 hover:text-zinc-700 dark:border-zinc-600 dark:bg-zinc-800 dark:hover:border-zinc-500', alwaysVisible ? 'opacity-100' : 'opacity-0 group-hover/insert:opacity-100', compact ? 'h-4 w-4' : 'h-5 w-5']">
            <IFluentAdd24Regular :class="compact ? 'h-2.5 w-2.5' : 'h-3 w-3'" />
          </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="center" class="w-56">
          <DropdownMenuItem v-for="type in quickAddTypes" :key="type.value" @click="$emit('insert', type.value)">
            <component :is="type.icon" class="mr-2 h-4 w-4" />
            {{ type.label }}
            <Badge v-if="type.isNew" variant="success" size="tiny" class="ml-auto">
              {{ $t('rich-content.new_badge') }}
            </Badge>
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem @click="$emit('more')">
            <IFluentMoreHorizontal24Regular class="mr-2 h-4 w-4" />
            {{ $t('rich-content.more_content_types') }}
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * A thin hover-revealed line between two blocks, with a quick-add menu — extracted out
 * of RichContentEditor.vue so the full-screen editor (RCFullscreenEditor.vue) can insert between blocks
 * without duplicating the markup. The caller decides visibility (e.g. `v-if="index > 0"`)
 * and where the new block lands; this component only asks "what type" and "at this spot".
 *
 * The full-width band is `pointer-events-none` — only a narrow zone centered on the button is
 * `pointer-events-auto` — so it never swallows clicks meant for the block content it overlaps
 * elsewhere along its width. Hovering has to land within that zone (roughly the button's own
 * horizontal footprint) to reveal it, rather than anywhere along the seam between blocks.
 */
import { trans as $t } from 'laravel-vue-i18n';

import type { ContentType } from '../Types';

import { Badge } from '@/Components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import IFluentAdd24Regular from '~icons/fluent/add24-regular';
import IFluentMoreHorizontal24Regular from '~icons/fluent/more-horizontal24-regular';

defineProps<{
  quickAddTypes: ContentType[];
  /** RichContentEditor.vue's regular (non-full-screen) form: blocks are compact
   *  `RCBlockCard`s with their own collapse toggle right at the top edge of the header,
   *  a few px below the block boundary. The full-screen editor's default h-12 hover zone
   *  overlapped that toggle and ate its clicks — this shrinks the hover/hit target so it
   *  no longer reaches into neighbouring block chrome. The full-screen editor's own bands
   *  have room to spare, so it keeps the larger default. */
  compact?: boolean;
  /** Shows the button without needing hover. Used for the trailing affordance below the
   *  last block (and doubling as "add the first block" on an empty document) — there's no
   *  block content below it whose hover a user could stumble onto to discover the control. */
  alwaysVisible?: boolean;
}>();

defineEmits<{
  (e: 'insert', type: string): void;
  (e: 'more'): void;
}>();
</script>
