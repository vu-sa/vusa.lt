<template>
  <section :id="anchorElementId" :class="band?.classes ?? []">
    <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
      <div class="grid items-stretch gap-8 lg:grid-cols-2 lg:gap-14">
        <!-- Text side -->
        <div :class="['flex flex-col justify-center', textLeft ? 'order-first' : 'order-last lg:order-first']">
          <EyebrowLabel v-if="content.eyebrow" class="inline-flex w-fit items-center gap-2 border border-brand px-3 py-1.5">
            <IconHeadphones class="size-3.5" />
            {{ content.eyebrow }}
          </EyebrowLabel>
          <h2 v-if="content.title" class="u-display mt-5 text-pretty text-3xl leading-[0.95] text-foreground sm:text-4xl">
            {{ content.title }}
          </h2>
          <RichContentTiptapHTML v-if="hasBody" :json_content="content.body" class="mt-4 max-w-lg" />

          <HeroButtons :buttons="content.buttons" class="mt-6" />
        </div>

        <!-- Embed side — a real iframe, not a mockup: it's the only way a visitor can press
             play on a Spotify/Mixcloud episode. -->
        <div :class="['relative overflow-hidden border border-border bg-ink lg:h-full', textLeft ? 'order-last' : 'order-first lg:order-last']">
          <img
            v-if="content.panelImage"
            :src="content.panelImage"
            alt=""
            class="absolute inset-0 h-full w-full object-cover opacity-25 grayscale"
          >
          <div v-if="content.panelImage" class="absolute inset-0 bg-gradient-to-br from-ink/85 to-ink" />

          <div class="relative flex h-full flex-col justify-center gap-6 p-7 sm:p-9">
            <div v-if="content.panelLabel" class="flex items-center gap-2 text-[0.6875rem] font-bold uppercase tracking-[0.24em] text-white/70">
              <span class="size-2 shrink-0 animate-pulse bg-brand" />
              {{ content.panelLabel }}
            </div>

            <iframe
              v-if="content.url"
              :src="resolvedEmbedUrl"
              frameborder="0"
              allowtransparency="true"
              :allow="isMixcloud ? 'encrypted-media; fullscreen; autoplay; idle-detection; speaker-selection; web-share;' : 'encrypted-media'"
              :title="isMixcloud ? 'Mixcloud Embed' : 'Spotify Embed'"
              :class="['block w-full border-0', isMixcloud ? 'h-[120px] min-h-[120px]' : 'h-[352px]']"
            />
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
import { computed } from 'vue';
import { useDark } from '@vueuse/core';

import RichContentTiptapHTML from './RichContentTiptapHTML.vue';
import HeroButtons from './RCHeroSection/HeroButtons.vue';
import { isMixcloudUrl, toMixcloudEmbedUrl, toSpotifyEmbedUrl } from './embedUrl';
import { EyebrowLabel } from '@/Components/Public/Base';
import IconHeadphones from '~icons/fluent/headphones24-regular';
import type { SpotifyEmbed } from '@/Types/contentParts';
import type { BandResolution } from './bandLayout';

const props = defineProps<{
  element: SpotifyEmbed;
  anchorId?: number | null;
  band?: BandResolution;
}>();

const content = computed(() => props.element.json_content);
const anchorElementId = computed(() => (props.anchorId ? `rc-${props.anchorId}` : undefined));

const textLeft = computed(() => props.element.options?.textLeft !== false);

const hasBody = computed(() => {
  const body = content.value.body as { content?: unknown[] } | undefined;
  return Array.isArray(body?.content) && body.content.length > 0;
});

const isMixcloud = computed(() => isMixcloudUrl(content.value.url));

const isDark = useDark();

const resolvedEmbedUrl = computed(() => (isMixcloud.value
  ? toMixcloudEmbedUrl(content.value.url, isDark.value)
  : toSpotifyEmbedUrl(content.value.url, isDark.value)));
</script>
