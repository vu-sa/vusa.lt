<template>
  <div class="flex flex-col gap-4">
    <Field>
      <FieldLabel>Spotify / Mixcloud URL</FieldLabel>
      <Input
        :model-value="modelValue?.url"
        type="url"
        placeholder="https://open.spotify.com/playlist/..."
        @update:model-value="patch({ url: String($event) })"
      />
      <FieldDescription>{{ $t('rich-content.spotify_url_hint') }}</FieldDescription>
    </Field>

    <!-- Preview if URL is valid -->
    <div v-if="isValidSpotifyUrl" class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800/50">
      <p class="mb-2 text-xs font-medium text-zinc-500 dark:text-zinc-400">
        {{ $t('rich-content.preview') }}
      </p>
      <div class="flex items-center gap-2 text-sm text-emerald-600 dark:text-emerald-400">
        <IFluentCheckmark24Regular class="h-4 w-4" />
        {{ $t('rich-content.valid_url') }}
      </div>
    </div>

    <Field>
      <FieldLabel>Rodymo būdas</FieldLabel>
      <Select :model-value="options?.variant ?? 'inline'" @update:model-value="options = { ...options, variant: $event }">
        <SelectTrigger>
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="inline">Įprastas įterpimas</SelectItem>
          <SelectItem value="promo">Reklaminė sekcija</SelectItem>
        </SelectContent>
      </Select>
      <FieldDescription>
        Įprastas įterpimas — įrėmintas grotuvas teksto viduje. Reklaminė sekcija — tekstas ir
        mygtukai šalia grotuvo, atskira juosta per visą pločio.
      </FieldDescription>
    </Field>

    <template v-if="options?.variant === 'promo'">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Field>
          <FieldLabel>Ženkliukas</FieldLabel>
          <Input :model-value="modelValue.eyebrow" type="text" placeholder="START FM 94.2"
            @update:model-value="patch({ eyebrow: String($event) })" />
        </Field>
        <Field>
          <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
          <Input :model-value="modelValue.title" type="text" :placeholder="$t('rich-content.enter_title')"
            @update:model-value="patch({ title: String($event) })" />
        </Field>
      </div>

      <Field>
        <FieldLabel>Tekstas</FieldLabel>
        <TiptapEditor :model-value="modelValue.body ?? {}" preset="compact"
          @update:model-value="patch({ body: $event })" />
      </Field>

      <Field>
        <FieldLabel>Etiketė virš grotuvo</FieldLabel>
        <Input :model-value="modelValue.panelLabel" type="text" placeholder="Naujausias epizodas"
          @update:model-value="patch({ panelLabel: String($event) })" />
      </Field>

      <Field>
        <FieldLabel>Fono nuotrauka (neprivaloma)</FieldLabel>
        <div>
          <TiptapImageButton v-if="!modelValue.panelImage" @submit:object="img => patch({ panelImage: img.src })">
            {{ $t('rich-content.select_image') }}
          </TiptapImageButton>
          <div v-else class="relative">
            <img :src="modelValue.panelImage" alt="" class="aspect-video w-full max-w-sm rounded-lg object-cover">
            <Button size="icon-sm" variant="ghost" class="absolute right-1 top-1 rounded-full bg-white/80 dark:bg-zinc-900/80"
              @click="patch({ panelImage: '' })">
              <IFluentDismiss20Regular />
            </Button>
          </div>
        </div>
      </Field>

      <Field>
        <FieldLabel>{{ $t('rich-content.buttons') }}</FieldLabel>
        <DynamicListInput
          :model-value="modelValue.buttons ?? []"
          :create-item="createButton"
          :empty-text="$t('rich-content.no_buttons')"
          :add-first-text="$t('rich-content.add_first_button')"
          :add-text="$t('rich-content.add_button')"
          allow-empty
          @update:model-value="patch({ buttons: $event })">
          <template #item="{ item, update }">
            <div class="flex flex-col gap-3">
              <Field>
                <FieldLabel>{{ $t('rich-content.button_text') }}</FieldLabel>
                <Input :model-value="item.text" type="text" :placeholder="$t('rich-content.enter_button_text')"
                  @update:model-value="update({ ...item, text: $event })" />
              </Field>
              <Field>
                <FieldLabel>{{ $t('rich-content.button_link') }}</FieldLabel>
                <Input :model-value="item.link" type="text" placeholder="https://..."
                  @update:model-value="update({ ...item, link: $event })" />
              </Field>
              <div class="grid grid-cols-2 gap-4">
                <Field>
                  <FieldLabel>{{ $t('rich-content.button_variant') }}</FieldLabel>
                  <Select :model-value="item.variant || 'default'" @update:model-value="update({ ...item, variant: $event })">
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="default">{{ $t('rich-content.default') }}</SelectItem>
                      <SelectItem value="outline">{{ $t('rich-content.outline') }}</SelectItem>
                    </SelectContent>
                  </Select>
                </Field>
                <Field>
                  <FieldLabel>{{ $t('rich-content.icon') }}</FieldLabel>
                  <RCIconSelect :model-value="item.icon" allow-none @update:model-value="update({ ...item, icon: $event })" />
                </Field>
              </div>
            </div>
          </template>
        </DynamicListInput>
      </Field>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Field>
          <FieldLabel>{{ $t('rich-content.section_background') }}</FieldLabel>
          <Select :model-value="options?.background ?? 'muted'" @update:model-value="options = { ...options, background: $event }">
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="none">{{ $t('rich-content.section_background_none') }}</SelectItem>
              <SelectItem value="muted">{{ $t('rich-content.section_background_muted') }}</SelectItem>
              <SelectItem value="contrast">{{ $t('rich-content.section_background_contrast') }}</SelectItem>
              <SelectItem value="gradient">{{ $t('rich-content.section_background_gradient') }}</SelectItem>
            </SelectContent>
          </Select>
        </Field>
        <Field>
          <FieldLabel>{{ $t('rich-content.section_padding') }}</FieldLabel>
          <Select :model-value="options?.padding ?? 'lg'" @update:model-value="options = { ...options, padding: $event }">
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="none">{{ $t('rich-content.section_padding_none') }}</SelectItem>
              <SelectItem value="sm">{{ $t('rich-content.small') }}</SelectItem>
              <SelectItem value="md">{{ $t('rich-content.medium') }}</SelectItem>
              <SelectItem value="lg">{{ $t('rich-content.large') }}</SelectItem>
            </SelectContent>
          </Select>
        </Field>
      </div>

      <Field>
        <div class="flex items-center justify-between">
          <FieldLabel class="mb-0">Grotuvas dešinėje</FieldLabel>
          <Switch :model-value="options?.textLeft !== false" @update:model-value="options = { ...options, textLeft: $event }" />
        </div>
        <FieldDescription>Kai išjungta, tekstas ir grotuvas sukeičiami vietomis darbalaukyje.</FieldDescription>
      </Field>

      <Field>
        <div class="flex items-center justify-between">
          <FieldLabel class="mb-0">{{ $t('rich-content.section_bleed') }}</FieldLabel>
          <Switch :model-value="options?.bleed !== false" @update:model-value="options = { ...options, bleed: $event }" />
        </div>
        <FieldDescription>{{ $t('rich-content.section_bleed_help') }}</FieldDescription>
      </Field>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCIconSelect from '../RCIconSelect.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import TiptapImageButton from '@/Components/TipTap/TiptapImageButton.vue';
import type { SpotifyEmbed } from '@/Types/contentParts';
import { Button } from '@/Components/ui/button';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import { Field, FieldDescription, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import IFluentCheckmark24Regular from '~icons/fluent/checkmark24-regular';
import IFluentDismiss20Regular from '~icons/fluent/dismiss20-regular';

const modelValue = defineModel<SpotifyEmbed['json_content']>();
const options = defineModel<SpotifyEmbed['options']>('options');

const isValidSpotifyUrl = computed(() => {
  const url = modelValue.value?.url;
  if (!url) return false;
  const spotify = /^https?:\/\/open\.spotify\.com\/(playlist|album|track|episode|show)\/[\w]+/.test(url);
  const mixcloud = /^https?:\/\/(www\.)?mixcloud\.com\/[\w-]+\/[\w-]+/.test(url);
  return spotify || mixcloud;
});

// The promo-only fields (everything besides `url`) are one object rather than a list, so every
// field writes a fresh object — mutating `modelValue.value` in place would not trip the editor's
// dirty tracking.
function patch(fields: Partial<SpotifyEmbed['json_content']>): void {
  modelValue.value = { ...modelValue.value, url: modelValue.value?.url ?? '', ...fields };
}

function createButton(): NonNullable<SpotifyEmbed['json_content']['buttons']>[number] {
  return { text: '', link: '', variant: 'default' };
}
</script>
