<template>
  <section :id="anchorElementId" :class="band?.classes ?? []">
    <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
      <div class="grid items-stretch gap-8 lg:grid-cols-2 lg:gap-14">
        <!-- Text side -->
        <div :class="['flex flex-col justify-center', textLeft ? 'order-first' : 'order-last lg:order-first']">
          <EyebrowLabel v-if="content.eyebrow || editable" class="inline-flex w-fit items-center gap-2 border border-brand px-3 py-1.5">
            <IconHeadphones class="size-3.5" />
            <RCInlineText
              v-if="editable"
              as="span"
              :model-value="content.eyebrow ?? ''"
              :editable
              placeholder="START FM 94.2"
              @click.stop
              @update:model-value="updateField('eyebrow', $event)"
            />
            <template v-else>
              {{ content.eyebrow }}
            </template>
          </EyebrowLabel>

          <h2 v-if="content.title || editable" class="u-display mt-5 text-pretty text-3xl leading-[0.95] text-foreground sm:text-4xl">
            <RCInlineText
              v-if="editable"
              as="span"
              :model-value="content.title ?? ''"
              :editable
              :placeholder="$t('rich-content.enter_title')"
              @click.stop
              @update:model-value="updateField('title', $event)"
            />
            <template v-else>
              {{ content.title }}
            </template>
          </h2>

          <div class="mt-4 max-w-lg">
            <template v-if="editable">
              <TiptapEditor
                v-if="isBodyActive"
                :model-value="content.body ?? {}"
                preset="marks"
                toolbar="bubble"
                :placeholder="$t('rich-content.content')"
                @update:model-value="updateField('body', $event)"
              />
              <div
                v-else
                class="min-h-12 cursor-text"
                data-rc-interactive
                data-rc-spotify-content
                role="button"
                tabindex="0"
                @click="$emit('claim-inline-field', bodyFieldId)"
                @keydown.enter.prevent="$emit('claim-inline-field', bodyFieldId)"
                @keydown.space.prevent="$emit('claim-inline-field', bodyFieldId)"
              >
                <RichContentTiptapHTML v-if="hasBody" :json_content="content.body" />
                <p v-else class="italic text-muted-foreground/60">
                  {{ $t('rich-content.content') }}
                </p>
              </div>
              <RCAddPlaceholder
                v-if="!hasBody && !isBodyActive"
                :label="$t('rich-content.content')"
                class="left-0 -bottom-2 translate-y-full"
                @click="$emit('claim-inline-field', bodyFieldId)"
              />
            </template>
            <RichContentTiptapHTML v-else-if="hasBody" :json_content="content.body" />
          </div>

          <HeroButtonsEditable
            v-if="editable"
            :buttons="content.buttons"
            :block-key="blockKey ?? ''"
            class="mt-6"
            @update:buttons="updateField('buttons', $event)"
          />
          <HeroButtons v-else-if="content.buttons?.length" :buttons="content.buttons" class="mt-6" />
        </div>

        <!-- Embed side -->
        <div :class="['relative overflow-hidden border border-border bg-ink lg:h-full', textLeft ? 'order-last' : 'order-first lg:order-last']">
          <img
            v-if="content.panelImage"
            :src="content.panelImage"
            alt=""
            class="absolute inset-0 h-full w-full object-cover opacity-25 grayscale"
          >
          <div v-if="content.panelImage" class="absolute inset-0 bg-gradient-to-br from-ink/85 to-ink" />

          <div class="relative flex h-full flex-col justify-center gap-6 p-7 sm:p-9">
            <div v-if="content.panelLabel || editable" class="flex items-center gap-2 text-[0.6875rem] font-bold uppercase tracking-[0.24em] text-white/70">
              <span class="size-2 shrink-0 animate-pulse bg-brand" />
              <RCInlineText
                v-if="editable"
                as="span"
                :model-value="content.panelLabel ?? ''"
                :editable
                :placeholder="$t('Naujausias epizodas')"
                @click.stop
                @update:model-value="updateField('panelLabel', $event)"
              />
              <template v-else>
                {{ content.panelLabel }}
              </template>
            </div>

            <iframe
              v-if="content.url"
              :src="resolvedEmbedUrl"
              frameborder="0"
              allowtransparency="true"
              :allow="isMixcloud ? 'encrypted-media; fullscreen; autoplay; idle-detection; speaker-selection; web-share;' : 'encrypted-media'"
              :title="isMixcloud ? 'Mixcloud Embed' : 'Spotify Embed'"
              :class="['block w-full border-0', isMixcloud ? 'h-[120px] min-h-[120px]' : 'h-[352px]', editable && 'pointer-events-none']"
            />
            <div
              v-else-if="editable"
              class="flex h-48 w-full flex-col items-center justify-center border border-dashed border-white/20 p-6 text-center text-white/60"
              data-rc-interactive
            >
              <SpotifyIcon class="mb-2 size-8 opacity-60" />
              <p class="text-xs uppercase tracking-wider">
                {{ $t('rich-content.enter_spotify_url') }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
/**
 * `spotify-embed`'s `promo` variant (v0: `podcast-section.tsx`). Unlike the original mockup's
 * fake play button + static waveform, the right-hand panel embeds the real Spotify/Mixcloud
 * iframe — that's the only way a visitor can actually start playback, and it's not full-bleed so
 * it reads as a panel beside the copy rather than taking over the row.
 *
 * Chrome comes entirely from the `band` prop (bandLayout.ts) — this type's `bandRole` resolves
 * to `'band'` only for the `promo` variant (see Types/index.ts), so it participates in the
 * page's automatic tint alternation exactly like every other band-capable block now.
 */
import { computed, defineAsyncComponent } from 'vue';
import { useDark } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';

import RichContentTiptapHTML from './RichContentTiptapHTML.vue';
import HeroButtons from './RCHeroSection/HeroButtons.vue';
import HeroButtonsEditable from './RCHeroSection/HeroButtonsEditable.vue';
import RCInlineText from './Editor/Fullscreen/RCInlineText.vue';
import RCAddPlaceholder from './Editor/Fullscreen/RCAddPlaceholder.vue';
import { isMixcloudUrl, toMixcloudEmbedUrl, toSpotifyEmbedUrl } from './embedUrl';
import type { BandResolution } from './bandLayout';

import { EyebrowLabel } from '@/Components/Public/Base';
import IconHeadphones from '~icons/fluent/headphones24-regular';
import SpotifyIcon from '~icons/simple-icons/spotify';
import type { SpotifyEmbed } from '@/Types/contentParts';

const TiptapEditor = defineAsyncComponent(() => import('@/Components/TipTap/TiptapEditor.vue'));

const props = defineProps<{
  element: SpotifyEmbed;
  anchorId?: number | null;
  band?: BandResolution;
  editable?: boolean;
  blockKey?: string;
  activeInlineField?: string | null;
}>();

const emit = defineEmits<{
  (e: 'update:element', value: SpotifyEmbed): void;
  (e: 'claim-inline-field', field: string | null): void;
}>();

const content = computed(() => props.element.json_content);
const anchorElementId = computed(() => (props.anchorId ? `rc-${props.anchorId}` : undefined));

const textLeft = computed(() => props.element.options?.textLeft !== false);

const bodyFieldId = computed(() => `${props.blockKey}:body`);
const isBodyActive = computed(() => props.activeInlineField === bodyFieldId.value);

const hasBody = computed(() => {
  const body = content.value.body as { content?: unknown[] } | undefined;
  return Array.isArray(body?.content) && body.content.length > 0;
});

const isMixcloud = computed(() => isMixcloudUrl(content.value.url));

const isDark = useDark();

const resolvedEmbedUrl = computed(() => (isMixcloud.value
  ? toMixcloudEmbedUrl(content.value.url, isDark.value)
  : toSpotifyEmbedUrl(content.value.url, isDark.value)));

function updateField<K extends keyof SpotifyEmbed['json_content']>(key: K, value: SpotifyEmbed['json_content'][K]): void {
  emit('update:element', {
    ...props.element,
    json_content: {
      ...content.value,
      [key]: value,
    },
  });
}
</script>
