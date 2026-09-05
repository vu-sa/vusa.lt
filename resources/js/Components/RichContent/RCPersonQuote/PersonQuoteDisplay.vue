<template>
  <RCSection
    :id="anchorId ? `rc-${anchorId}` : undefined" :title="element.options?.title" :subtitle="element.options?.subtitle"
    :eyebrow="element.options?.eyebrow"
    :band inner="content"
    :align :heading-level="element.options?.headingLevel"
    :show-separator="element.options?.showSeparator"
    :editable @update:header="updateOptions"
  >
    <figure ref="figureRef" :class="['flex flex-col gap-5', align === 'center' ? 'items-center text-center' : 'items-start text-left']">
      <span class="text-6xl font-serif leading-none text-brand/20" aria-hidden="true">&ldquo;</span>

      <blockquote class="rc-prose -mt-8 w-full text-xl font-medium leading-relaxed text-foreground md:text-2xl">
        <TiptapEditor v-if="isQuoteLive"
          :model-value="element.json_content?.quote" preset="minimal" prose-style
          :placeholder="$t('rich-content.person_quote_quote')" data-rc-interactive
          @focusout="releaseQuote" @update:model-value="updateQuote"
        />
        <button v-else-if="editable" type="button" class="block w-full cursor-text text-inherit" :class="align === 'center' ? 'text-center' : 'text-left'" data-rc-interactive @click="claimQuote">
          <RichContentTiptapHTML v-if="hasQuote" :json_content="element.json_content?.quote" />
          <span v-else class="text-muted-foreground/50">{{ $t('rich-content.person_quote_quote') }}</span>
        </button>
        <template v-else>
          <RichContentTiptapHTML v-if="!html" :json_content="element.json_content?.quote" />
          <div v-else v-html="element.html" />
        </template>
      </blockquote>

      <figcaption v-if="showAvatar && (snapshot?.name || editable)" class="flex items-center gap-3">
        <button v-if="editable" type="button" class="shrink-0 cursor-pointer rounded-full" data-rc-interactive :aria-label="$t('rich-content.person_quote_person')" @click="hotspots?.openPopover(personHotspotId)">
          <Avatar class="size-12 border border-border">
            <AvatarImage v-if="snapshot?.photoUrl" :src="snapshot.photoUrl" :alt="snapshot?.name" class="object-cover" />
            <AvatarFallback class="font-medium">
              {{ initials || '?' }}
            </AvatarFallback>
          </Avatar>
        </button>
        <Avatar v-else class="size-12 border border-border">
          <AvatarImage v-if="snapshot?.photoUrl" :src="snapshot.photoUrl" :alt="snapshot?.name" class="object-cover" />
          <AvatarFallback class="font-medium">
            {{ initials }}
          </AvatarFallback>
        </Avatar>
        <div :class="align === 'center' ? 'text-left' : ''">
          <button v-if="editable" type="button" class="block cursor-pointer text-left font-bold text-foreground" data-rc-interactive @click="hotspots?.openPopover(personHotspotId)">
            <span v-if="snapshot?.name">{{ snapshot.name }}</span>
            <span v-else class="text-muted-foreground/50">{{ $t('rich-content.select_person') }}</span>
          </button>
          <p v-else class="font-bold text-foreground">
            {{ snapshot?.name }}
          </p>

          <RCInlineText v-if="editable && snapshot?.name" as="p" class="text-sm text-muted-foreground" :model-value="snapshot?.attribution ?? ''" :editable :placeholder="$t('rich-content.enter_attribution')" @click.stop @update:model-value="updateAttribution" />
          <p v-else-if="snapshot?.attribution" class="text-sm text-muted-foreground">
            {{ snapshot.attribution }}
          </p>
        </div>
      </figcaption>
    </figure>

    <Popover v-if="editable" :open="!!hotspots?.isPopoverOpen(personHotspotId)" @update:open="onPersonPopoverOpenChange">
      <PopoverAnchor :reference="figureRef ?? undefined" />
      <PopoverContent v-if="hotspots?.isPopoverOpen(personHotspotId)" data-surface="public" class="w-80" @close-auto-focus.prevent>
        <Field>
          <FieldLabel>{{ $t('rich-content.person_quote_person') }}</FieldLabel>
          <CollectionSelectDialog
            v-model:open="pickerOpen"
            collection="users"
            :multiple="false"
            allow-empty
            :initial-hits
            :title="$t('rich-content.person_quote_person')"
            :confirm-label="$t('rich-content.confirm_selection')"
            @confirm="onPersonConfirm">
            <template #trigger>
              <Button type="button" variant="outline" class="w-full justify-between font-normal">
                <span class="truncate" :class="{ 'text-muted-foreground': !snapshot?.name }">
                  {{ snapshot?.name || $t('rich-content.select_person') }}
                </span>
                <IFluentChevronDown24Regular class="size-4 opacity-50" />
              </Button>
            </template>
          </CollectionSelectDialog>
        </Field>
      </PopoverContent>
    </Popover>
  </RCSection>
</template>

<script setup lang="ts">
/**
 * Displays a `person-quote` block. Static — reads only `element.json_content`, no
 * server resolution (see `ContentPartResolver`'s docblock for why): the snapshot is
 * an author-approved copy, not a live reference to the picked user.
 */
import { computed, defineAsyncComponent, inject, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCSection from '../RCSection.vue';
import RichContentTiptapHTML from '../RichContentTiptapHTML.vue';
import RCInlineText from '../Editor/Fullscreen/RCInlineText.vue';
import { ACTIVE_HOTSPOT_KEY } from '../Editor/Fullscreen/useActiveHotspot';
import { useUserAttributionLookup } from '../composables/useUserAttributionLookup';
import type { BandResolution } from '../bandLayout';

import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Popover, PopoverAnchor, PopoverContent } from '@/Components/ui/popover';
import CollectionSelectDialog from '@/Features/Admin/AdminSearch/Components/Select/CollectionSelectDialog.vue';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import IFluentChevronDown24Regular from '~icons/fluent/chevron-down24-regular';

// Lazy-loaded: only mounts while the quote field is claimed in the full-screen editor.
const TiptapEditor = defineAsyncComponent(() => import('@/Components/TipTap/TiptapEditor.vue'));

const props = defineProps<{
  element: models.ContentPart;
  html?: boolean;
  anchorId?: number | null;
  band?: BandResolution;
  /** Full-screen editor mode: the optional title/subtitle/eyebrow header, the quote
   *  itself, and the featured person all become click-to-edit. Undefined/false in
   *  every other context. Name/photo are still picked through the same
   *  `CollectionSelectDialog` the regular form uses (a popover here, like Hero's
   *  image hotspot) since they're not free text; attribution is a plain string, so
   *  it's inline-editable directly, once a person is picked. */
  editable?: boolean;
  /** This block's identity for the quote/person hotspot ids (`${blockKey}:quote`,
   *  `${blockKey}:person`). Only meaningful — and only ever set — when `editable`
   *  is true. */
  blockKey?: string;
  /** Declared (but unused) purely to intercept `BlockPreviewRenderer`'s generic
   *  `inlineEditable` fallthrough — this block has no per-field `activeInlineField`
   *  contract of its own (it uses the injected hotspot state directly, like Hero). */
  activeInlineField?: string | null;
}>();

const emit = defineEmits<(e: 'update:element', value: models.ContentPart) => void>();

// PersonQuoteDisplay renders both publicly and inside the full-screen editor, so —
// unlike a leaf hotspot that only ever mounts when editable — this injection must
// tolerate having no provider (public rendering) rather than throwing.
const hotspots = inject(ACTIVE_HOTSPOT_KEY, undefined);

const quoteHotspotId = computed(() => `${props.blockKey ?? ''}:quote`);
const isQuoteLive = computed(() => !!props.editable && !!hotspots?.isTextFieldLive(quoteHotspotId.value));

const personHotspotId = computed(() => `${props.blockKey ?? ''}:person`);
const figureRef = ref<HTMLElement | null>(null);
const pickerOpen = ref(false);

const initialHits = computed<NormalizedSearchHit[]>(() => {
  const userId = snapshot.value?.userId;
  if (!userId) return [];
  return [normalizeHit('users', { id: userId, name: snapshot.value?.name })];
});

function onPersonPopoverOpenChange(open: boolean): void {
  if (open) hotspots?.openPopover(personHotspotId.value);
  else hotspots?.close(personHotspotId.value);
}

function updateSnapshot(patch: Partial<{ userId: number; name: string; photoUrl?: string; attribution?: string }>): void {
  emit('update:element', {
    ...props.element,
    json_content: { ...props.element.json_content, snapshot: { ...snapshot.value, ...patch } },
  });
}

const { userId: attributionUserId, data: attributionData } = useUserAttributionLookup();

function onPersonConfirm(hits: NormalizedSearchHit[]): void {
  const hit = hits[0];
  if (!hit) {
    emit('update:element', { ...props.element, json_content: { ...props.element.json_content, snapshot: { name: '' } } });
    return;
  }

  const userId = Number(hit.recordId);
  updateSnapshot({ userId, name: hit.title });
  attributionUserId.value = userId;
}

watch(attributionData, (data) => {
  if (!data) return;
  const patch: Partial<{ photoUrl?: string; attribution?: string }> = { photoUrl: data.photoUrl ?? undefined };
  // Only seed the attribution if the author hasn't already typed one — this fires
  // every time a new person is picked, and must not clobber a manual edit.
  if (!snapshot.value?.attribution && data.attributions[0]) {
    patch.attribution = data.attributions[0];
  }
  updateSnapshot(patch);
});

function updateAttribution(value: string): void {
  updateSnapshot({ attribution: value });
}

/** Cheap structural emptiness check — a bare `doc`/`paragraph` shell with no text
 *  anywhere in the tree counts as empty (same check RCAccordion.vue uses). */
function hasContent(node: unknown): boolean {
  if (!node || typeof node !== 'object') return false;
  const { type, text, content } = node as { type?: string; text?: string; content?: unknown[] };
  if (typeof text === 'string' && text.trim()) return true;
  if (type && type !== 'doc' && type !== 'paragraph') return true;
  return (content ?? []).some(hasContent);
}

const hasQuote = computed(() => hasContent(props.element.json_content?.quote));

function claimQuote(): void {
  if (props.editable) hotspots?.openTextField(quoteHotspotId.value);
}

function releaseQuote(): void {
  hotspots?.close(quoteHotspotId.value);
}

function updateQuote(value: string | Record<string, unknown> | null): void {
  emit('update:element', { ...props.element, json_content: { ...props.element.json_content, quote: value } });
}

function updateOptions(patch: { title?: string; subtitle?: string; eyebrow?: string }): void {
  emit('update:element', { ...props.element, options: { ...props.element.options, ...patch } });
}

// `options.align` is authored once, via the shared RCSectionOptions header control
// (PersonQuoteEditor has no align control of its own) — it drives both the section
// header's alignment above and this figure's own alignment below, which is coherent
// (a centered header sits over a centered quote) but worth flagging as one key doing
// two jobs.
const align = computed<'start' | 'center'>(() => (props.element.options?.align === 'start' ? 'start' : 'center'));
const showAvatar = computed(() => props.element.options?.showAvatar !== false);
const snapshot = computed(() => props.element.json_content?.snapshot as { userId?: number; name?: string; photoUrl?: string; attribution?: string } | undefined);

const initials = computed(() => {
  const name = snapshot.value?.name;
  if (!name) return '';
  const words = name.split(' ').filter(Boolean);
  if (words.length === 1) return words[0]!.substring(0, 2).toUpperCase();
  return (words[0]!.charAt(0) + words[words.length - 1]!.charAt(0)).toUpperCase();
});
</script>
