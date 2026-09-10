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
      <!-- Spotify / Mixcloud URL -->
      <Field>
        <FieldLabel>Spotify / Mixcloud URL</FieldLabel>
        <Input
          :model-value="url"
          type="url"
          placeholder="https://open.spotify.com/..."
          @update:model-value="updateUrl(String($event))"
        />
        <FieldDescription class="text-xs">
          {{ $t('rich-content.spotify_url_hint') }}
        </FieldDescription>
      </Field>

      <!-- URL validity badge -->
      <div v-if="isValidUrl" class="flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400">
        <IFluentCheckmark12Regular class="size-3.5" />
        <span>{{ $t('rich-content.valid_url') }}</span>
      </div>

      <!-- Variant -->
      <Field>
        <FieldLabel>{{ $t('rich-content.display_variant') }}</FieldLabel>
        <Select :model-value="variant" @update:model-value="setVariant($event as string)">
          <SelectTrigger size="sm">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="inline">
              {{ $t('rich-content.variant_inline') }}
            </SelectItem>
            <SelectItem value="promo">
              {{ $t('rich-content.variant_promo') }}
            </SelectItem>
          </SelectContent>
        </Select>
      </Field>

      <!-- Width Picker -->
      <div v-if="allowedWidths.length > 1" class="flex items-center justify-between gap-2">
        <FieldLabel>{{ $t('rich-content.width') }}</FieldLabel>
        <RCWidthPicker :model-value="currentWidth" :allowed-widths @update:model-value="setWidth" />
      </div>

      <!-- Promo-only controls -->
      <template v-if="variant === 'promo'">
        <!-- Text Left/Right layout switch -->
        <Field>
          <div class="flex items-center justify-between">
            <FieldLabel class="mb-0 text-xs">
              {{ $t('rich-content.player_on_right') }}
            </FieldLabel>
            <Switch
              :model-value="options.textLeft !== false"
              @update:model-value="updateOption('textLeft', $event)"
            />
          </div>
        </Field>

        <!-- Panel background image -->
        <Field>
          <FieldLabel class="text-xs">
            {{ $t('rich-content.panel_image') }}
          </FieldLabel>
          <div class="flex items-center gap-2">
            <TiptapImageButton
              v-if="!spotifyContent.panelImage"
              @submit:object="img => updateField('panelImage', img.src)"
            >
              {{ $t('rich-content.select_image') }}
            </TiptapImageButton>
            <div v-else class="flex items-center gap-2">
              <img :src="spotifyContent.panelImage" alt="" class="size-8 rounded object-cover border border-border">
              <Button size="sm" variant="outline" @click="updateField('panelImage', '')">
                {{ $t('rich-content.remove_image') }}
              </Button>
            </div>
          </div>
        </Field>

        <!-- Presentation picker -->
        <RCPresentationPicker
          :model-value="options.presentation"
          :plain-padding="options.plainPadding"
          :disabled="presentationDisabled"
          @update:model-value="updateOption('presentation', $event)"
          @update:plain-padding="updateOption('plainPadding', $event)"
        />
      </template>
    </div>
  </RCBlockToolbarShell>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCBlockToolbarShell from '../Editor/Fullscreen/RCBlockToolbarShell.vue';
import RCPresentationPicker from '../Editor/RCPresentationPicker.vue';
import RCWidthPicker from '../Editor/RCWidthPicker.vue';
import { withWidth } from '../Editor/blockWidth';
import { getContentType, type BlockWidth, type ContentPart } from '../Types';

import TiptapImageButton from '@/Components/TipTap/TiptapImageButton.vue';
import { Button } from '@/Components/ui/button';
import { Field, FieldDescription, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import type { SpotifyEmbed } from '@/Types/contentParts';
import IFluentCheckmark12Regular from '~icons/fluent/checkmark12-regular';

const props = defineProps<{
  content: ContentPart;
  blockKey: string;
  reference?: Element | null;
  canMoveUp: boolean;
  canMoveDown: boolean;
  canDelete: boolean;
  presentationDisabled?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:content', value: ContentPart): void;
  (e: 'move-up'): void;
  (e: 'move-down'): void;
  (e: 'delete'): void;
  (e: 'open-form'): void;
}>();

const spotifyContent = computed<SpotifyEmbed['json_content']>(
  () => (props.content.json_content ?? { url: '' }) as SpotifyEmbed['json_content'],
);

const options = computed<NonNullable<SpotifyEmbed['options']>>(
  () => (props.content.options ?? {}) as NonNullable<SpotifyEmbed['options']>,
);

const url = computed(() => spotifyContent.value?.url ?? '');
const variant = computed(() => options.value?.variant ?? 'inline');

const isValidUrl = computed(() => {
  if (!url.value) return false;
  const spotify = /^https?:\/\/open\.spotify\.com\/(playlist|album|track|episode|show)\/[\w]+/.test(url.value);
  const mixcloud = /^https?:\/\/(www\.)?mixcloud\.com\/[\w-]+\/[\w-]+/.test(url.value);
  return spotify || mixcloud;
});

const contentType = computed(() => getContentType('spotify-embed'));
const allowedWidths = computed<BlockWidth[]>(() => contentType.value.allowedWidths ?? [contentType.value.defaultWidth]);
const currentWidth = computed<BlockWidth>(() => (options.value?.width as BlockWidth | undefined) ?? contentType.value.defaultWidth);

function updateUrl(newUrl: string): void {
  emit('update:content', {
    ...props.content,
    json_content: {
      ...spotifyContent.value,
      url: newUrl,
    },
  });
}

function updateField<K extends keyof SpotifyEmbed['json_content']>(key: K, value: SpotifyEmbed['json_content'][K]): void {
  emit('update:content', {
    ...props.content,
    json_content: {
      ...spotifyContent.value,
      [key]: value,
    },
  });
}

function setVariant(newVariant: string): void {
  emit('update:content', {
    ...props.content,
    options: {
      ...options.value,
      variant: newVariant as 'inline' | 'promo',
    },
  });
}

function updateOption<K extends keyof NonNullable<SpotifyEmbed['options']>>(key: K, value: NonNullable<SpotifyEmbed['options']>[K]): void {
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
