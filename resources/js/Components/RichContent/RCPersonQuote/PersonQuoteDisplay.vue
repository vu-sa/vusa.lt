<template>
  <RCSection
    :title="element.options?.title" :subtitle="element.options?.subtitle"
    :background="element.options?.background ?? 'none'" :padding="element.options?.padding ?? 'md'"
    :rounded="element.options?.rounded ?? 'none'"
    inner="content" :align="align"
    :heading-level="element.options?.headingLevel" :show-separator="element.options?.showSeparator"
    :id="anchorId ? `rc-${anchorId}` : undefined"
  >
    <figure :class="['flex flex-col gap-5', align === 'center' ? 'items-center text-center' : 'items-start text-left']">
      <span class="text-6xl font-serif leading-none text-brand/20" aria-hidden="true">&ldquo;</span>

      <blockquote class="rc-prose -mt-8 text-xl font-medium leading-relaxed text-foreground md:text-2xl">
        <RichContentTiptapHTML v-if="!html" :json_content="element.json_content?.quote" />
        <div v-else v-html="element.html" />
      </blockquote>

      <figcaption v-if="showAvatar && snapshot?.name" class="flex items-center gap-3">
        <Avatar class="size-12 border border-border">
          <AvatarImage v-if="snapshot.photoUrl" :src="snapshot.photoUrl" :alt="snapshot.name" class="object-cover" />
          <AvatarFallback class="font-medium">{{ initials }}</AvatarFallback>
        </Avatar>
        <div :class="align === 'center' ? 'text-left' : ''">
          <p class="font-bold text-foreground">{{ snapshot.name }}</p>
          <p v-if="snapshot.attribution" class="text-sm text-muted-foreground">{{ snapshot.attribution }}</p>
        </div>
      </figcaption>
    </figure>
  </RCSection>
</template>

<script setup lang="ts">
/**
 * Displays a `person-quote` block. Static — reads only `element.json_content`, no
 * server resolution (see `ContentPartResolver`'s docblock for why): the snapshot is
 * an author-approved copy, not a live reference to the picked user.
 */
import { computed } from 'vue';

import RCSection from '../RCSection.vue';
import RichContentTiptapHTML from '../RichContentTiptapHTML.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';

const props = defineProps<{
  element: models.ContentPart;
  html?: boolean;
  anchorId?: number | null;
}>();

// `options.align` is authored once, via the shared RCSectionOptions header control
// (PersonQuoteEditor has no align control of its own) — it drives both the section
// header's alignment above and this figure's own alignment below, which is coherent
// (a centered header sits over a centered quote) but worth flagging as one key doing
// two jobs.
const align = computed<'start' | 'center'>(() => (props.element.options?.align === 'start' ? 'start' : 'center'));
const showAvatar = computed(() => props.element.options?.showAvatar !== false);
const snapshot = computed(() => props.element.json_content?.snapshot as { name?: string; photoUrl?: string; attribution?: string } | undefined);

const initials = computed(() => {
  const name = snapshot.value?.name;
  if (!name) return '';
  const words = name.split(' ').filter(Boolean);
  if (words.length === 1) return words[0]!.substring(0, 2).toUpperCase();
  return (words[0]!.charAt(0) + words[words.length - 1]!.charAt(0)).toUpperCase();
});
</script>
