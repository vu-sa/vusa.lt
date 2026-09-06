<template>
  <RCBlockToolbarShell
    :content :block-key :reference
    :can-move-up :can-move-down :can-delete
    @update:content="$emit('update:content', $event)"
    @move-up="$emit('move-up')"
    @move-down="$emit('move-down')"
    @delete="$emit('delete')"
    @open-form="$emit('open-form')"
  >
    <div class="flex flex-col gap-3">
      <!-- Social Media URL -->
      <Field>
        <FieldLabel>{{ $t('Facebook arba Instagram įrašo nuoroda') }}</FieldLabel>
        <Input
          :model-value="url"
          type="url"
          placeholder="https://www.facebook.com/... arba https://www.instagram.com/p/..."
          @update:model-value="updateUrl(String($event))"
        />
        <FieldDescription class="text-xs">
          {{ $t('Įklijuokite Facebook arba Instagram įrašo nuorodą') }}
        </FieldDescription>
      </Field>

      <!-- Platform detection badge -->
      <div v-if="detectedPlatform" class="flex items-center gap-2 text-xs">
        <div class="flex items-center gap-1.5 rounded-full px-2.5 py-0.5" :class="platformBadgeClass">
          <component :is="platformIcon" class="size-3.5" />
          <span class="font-medium">{{ platformLabel }}</span>
        </div>
        <span v-if="isValidUrl" class="text-emerald-600 dark:text-emerald-400">
          ✓ {{ $t('Nuoroda atpažinta') }}
        </span>
        <span v-else class="text-amber-600 dark:text-amber-400">
          {{ $t('Patikrinkite nuorodą') }}
        </span>
      </div>

      <!-- Show caption toggle -->
      <Field>
        <div class="flex items-center justify-between">
          <FieldLabel class="mb-0 text-xs">{{ $t('Rodyti įrašo aprašymą') }}</FieldLabel>
          <Switch
            :model-value="options.showCaption !== false"
            @update:model-value="updateOption('showCaption', $event)"
          />
        </div>
      </Field>

      <!-- Width Picker -->
      <div v-if="allowedWidths.length > 1" class="flex items-center justify-between gap-2">
        <FieldLabel>{{ $t('rich-content.width') }}</FieldLabel>
        <RCWidthPicker :model-value="currentWidth" :allowed-widths @update:model-value="setWidth" />
      </div>
    </div>
  </RCBlockToolbarShell>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCBlockToolbarShell from '../Editor/Fullscreen/RCBlockToolbarShell.vue';
import RCWidthPicker from '../Editor/RCWidthPicker.vue';
import { withWidth } from '../Editor/blockWidth';
import { getContentType, type BlockWidth, type ContentPart } from '../Types';
import { Field, FieldDescription, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import type { SocialEmbed } from '@/Types/contentParts';
import FacebookIcon from '~icons/simple-icons/facebook';
import InstagramIcon from '~icons/simple-icons/instagram';

const props = defineProps<{
  content: ContentPart;
  blockKey: string;
  reference?: Element | null;
  canMoveUp: boolean;
  canMoveDown: boolean;
  canDelete: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:content', value: ContentPart): void;
  (e: 'move-up'): void;
  (e: 'move-down'): void;
  (e: 'delete'): void;
  (e: 'open-form'): void;
}>();

const socialContent = computed<SocialEmbed['json_content']>(
  () => (props.content.json_content ?? { url: '', platform: null }) as SocialEmbed['json_content'],
);

const options = computed<NonNullable<SocialEmbed['options']>>(
  () => (props.content.options ?? {}) as NonNullable<SocialEmbed['options']>,
);

const url = computed(() => socialContent.value?.url ?? '');

function detectPlatformFromUrl(urlValue: string): 'facebook' | 'instagram' | null {
  if (!urlValue) return null;
  if (/facebook\.com|fb\.watch/i.test(urlValue)) return 'facebook';
  if (/instagram\.com|instagr\.am/i.test(urlValue)) return 'instagram';
  return null;
}

const detectedPlatform = computed(() => detectPlatformFromUrl(url.value));

const isValidUrl = computed(() => {
  if (!url.value) return false;
  try {
    new URL(url.value);
    return detectedPlatform.value !== null;
  }
  catch {
    return false;
  }
});

const platformBadgeClass = computed(() => {
  if (detectedPlatform.value === 'facebook') {
    return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
  }
  if (detectedPlatform.value === 'instagram') {
    return 'bg-gradient-to-r from-purple-100 to-pink-100 text-pink-700 dark:from-purple-900/30 dark:to-pink-900/30 dark:text-pink-400';
  }
  return 'bg-muted text-muted-foreground';
});

const platformIcon = computed(() => {
  if (detectedPlatform.value === 'facebook') return FacebookIcon;
  if (detectedPlatform.value === 'instagram') return InstagramIcon;
  return null;
});

const platformLabel = computed(() => {
  if (detectedPlatform.value === 'facebook') return 'Facebook';
  if (detectedPlatform.value === 'instagram') return 'Instagram';
  return '';
});

const contentType = computed(() => getContentType('social-embed'));
const allowedWidths = computed<BlockWidth[]>(() => contentType.value.allowedWidths ?? [contentType.value.defaultWidth]);
const currentWidth = computed<BlockWidth>(() => (options.value?.width as BlockWidth | undefined) ?? contentType.value.defaultWidth);

function updateUrl(newUrl: string): void {
  emit('update:content', {
    ...props.content,
    json_content: {
      ...socialContent.value,
      url: newUrl,
      platform: detectPlatformFromUrl(newUrl),
    },
  });
}

function updateOption<K extends keyof NonNullable<SocialEmbed['options']>>(key: K, value: NonNullable<SocialEmbed['options']>[K]): void {
  emit('update:content', {
    ...props.content,
    options: {
      ...options.value,
      [key]: value,
    },
  });
}

function setWidth(width: BlockWidth): void {
  emit('update:content', withWidth(props.content, width));
}
</script>
