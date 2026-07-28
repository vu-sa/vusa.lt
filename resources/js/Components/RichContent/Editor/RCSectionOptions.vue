<template>
  <!-- Standalone `section` block (collapsible=false): title/subtitle IS the content,
       so keep every field flat and always visible rather than tucked behind a toggle. -->
  <Field v-if="!collapsible">
    <FieldLabel>{{ $t('rich-content.section_options') }}</FieldLabel>
    <RCSectionOptionsFields v-model="options" />
  </Field>

  <!-- Every other section block: title/subtitle/background/padding/rounded are
        secondary chrome around the block's own content, so collapse them behind a
        trigger (closed by default) to keep the editor focused on the primary content.
        Wrapped in a bordered panel so the collapsed state is clearly discoverable — a
        bare text label here is easy to miss among the block's own editing controls. -->
  <Collapsible v-else v-model:open="open"
    class="overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50/60 dark:border-zinc-700/60 dark:bg-zinc-800/30">
    <CollapsibleTrigger as-child>
      <button type="button"
        class="flex w-full items-center gap-2 px-3 py-2.5 text-left transition-colors hover:bg-zinc-100/80 dark:hover:bg-zinc-800/50">
        <SlidersHorizontal class="size-4 shrink-0 text-muted-foreground" />
        <span class="flex-1 text-sm font-medium leading-none select-none">
          {{ $t('rich-content.section_options') }}
        </span>
        <ChevronDown
          class="size-4 shrink-0 text-muted-foreground transition-transform duration-200"
          :class="{ 'rotate-180': open }" />
      </button>
    </CollapsibleTrigger>
    <CollapsibleContent>
      <div class="border-t border-zinc-200 p-3 dark:border-zinc-700/60">
        <RCSectionOptionsFields v-model="options" />
      </div>
    </CollapsibleContent>
  </Collapsible>
</template>

<script setup lang="ts">
/**
 * Shared title/subtitle/background/padding/rounded fields for every content type that
 * renders through RCSection.vue — one implementation instead of six copy-pasted field
 * blocks, so the authoring UI can't drift between e.g. card-stack and photo-gallery.
 *
 * The field grid itself lives in RCSectionOptionsFields.vue so both render modes (flat
 * and collapsible) share a single source for every field — including the newer
 * heading-level / alignment / separator controls.
 *
 * Collapsed behind a trigger by default (`collapsible` prop): for most blocks these
 * are secondary looks, not content. The standalone `section` block opts out by passing
 * `:collapsible="false"` — there the title/subtitle *is* the block's content, so
 * hiding it behind a toggle would bury the one thing the author came to edit.
 */
import { ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown, SlidersHorizontal } from 'lucide-vue-next';

import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import { Field, FieldLabel } from '@/Components/ui/field';

import RCSectionOptionsFields from './RCSectionOptionsFields.vue';

import type { SectionOptions } from '@/Types/contentParts';

// Default-true is intentional: every editor using this wants collapse-by-default,
// and only SectionEditor opts out via `:collapsible="false"`. Vue coerces an absent
// boolean prop to false, so the default must be explicit — hence the lint exception.
// eslint-disable-next-line vue/no-boolean-default
withDefaults(defineProps<{ collapsible?: boolean }>(), { collapsible: true });

const options = defineModel<SectionOptions>({ required: true });

// Closed by default — matches the "advanced settings" collapsibles in NewsForm/PageForm.
const open = ref(false);
</script>
