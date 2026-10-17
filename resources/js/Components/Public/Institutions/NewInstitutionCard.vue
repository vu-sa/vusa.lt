<template>
  <!-- The title is a stretched link rather than the whole card being one: the social links
       inside would otherwise be nested <a>s, which the parser splits apart in SSR markup. -->
  <article
    class="group relative flex h-full min-w-0 flex-col border border-border bg-card transition-colors duration-200 hover:border-brand"
    data-slot="institution-card"
  >
    <MediaFrame
      :image="institution.image_media ?? imageFromSearch(institution as unknown as Record<string, unknown>)"
      sizes="(min-width: 1280px) 392px, (min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw"
      :src="institution.image_url ?? undefined"
      :alt="String(institution.name || '')"
      :focal-point="institution.image_focal_point"
      :grayscale="false"
      hover-zoom
      class="aspect-[2/1] shrink-0 border-0 border-b border-border"
    >
      <template #fallback>
        <img
          v-if="institution.logo_url"
          :src="institution.logo_url"
          :alt="`${String(institution.name || '')} logo`"
          class="max-h-16 max-w-[70%] object-contain"
          loading="lazy"
        >
        <IFluentBuilding24Regular v-else class="size-10 text-muted-foreground/50" />
      </template>
    </MediaFrame>

    <div class="flex flex-1 flex-col p-5">
      <InstitutionCardHeading :eyebrow :href="url" :title="String(institution.name || '')" />

      <InstitutionCardDescription :text="description" />

      <div class="mt-5 flex items-center justify-between gap-3 border-t border-border pt-4">
        <div class="min-w-0 flex-1">
          <a
            v-if="institution.email"
            :href="`mailto:${institution.email}`"
            class="relative z-10 inline-flex max-w-full items-center gap-2 text-sm font-bold text-brand transition-colors hover:text-foreground"
          >
            <IFluentMail16Regular class="size-4 shrink-0" />
            <span class="truncate">{{ institution.email }}</span>
          </a>
          <a
            v-else-if="institution.website"
            :href="institution.website"
            target="_blank"
            rel="noopener noreferrer"
            class="relative z-10 inline-flex max-w-full items-center gap-2 text-sm font-bold text-brand transition-colors hover:text-foreground"
          >
            <IFluentGlobe16Regular class="size-4 shrink-0" />
            <span class="truncate">{{ formatWebsite(institution.website) }}</span>
          </a>
        </div>

        <div class="flex shrink-0 items-center gap-1.5">
          <a
            v-if="institution.facebook_url"
            :href="institution.facebook_url"
            target="_blank"
            rel="noopener noreferrer"
            :class="socialLinkClass"
          >
            <span class="sr-only">Facebook</span>
            <ISimpleIconsFacebook class="size-3.5" />
          </a>
          <a
            v-if="institution.instagram_url"
            :href="institution.instagram_url"
            target="_blank"
            rel="noopener noreferrer"
            :class="socialLinkClass"
          >
            <span class="sr-only">Instagram</span>
            <ISimpleIconsInstagram class="size-3.5" />
          </a>
          <InstitutionCardArrow />
        </div>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { imageFromSearch } from '@/Types/media';
import InstitutionCardArrow from './InstitutionCardArrow.vue';
import InstitutionCardDescription from './InstitutionCardDescription.vue';
import InstitutionCardHeading from './InstitutionCardHeading.vue';
import { type InstitutionCardData, useInstitutionCard } from './useInstitutionCard';

import MediaFrame from '@/Components/Public/Base/MediaFrame.vue';
import IFluentBuilding24Regular from '~icons/fluent/building-24-regular';
import IFluentMail16Regular from '~icons/fluent/mail-16-regular';
import IFluentGlobe16Regular from '~icons/fluent/globe-16-regular';
import ISimpleIconsFacebook from '~icons/simple-icons/facebook';
import ISimpleIconsInstagram from '~icons/simple-icons/instagram';

const props = defineProps<{
  institution: InstitutionCardData | App.Entities.Institution;
  href?: string | null;
  /** Types the page already names; left out of the card's label. */
  omitTypeSlugs?: string[];
}>();

const { url, eyebrow, description } = useInstitutionCard(
  () => props.institution as InstitutionCardData,
  () => props.href,
  () => props.omitTypeSlugs,
);

const socialLinkClass = [
  'relative z-10 inline-flex size-9 items-center justify-center pointer-coarse:size-11',
  'border border-border text-muted-foreground transition-colors hover:border-brand hover:text-brand',
];

const formatWebsite = (website: string) => website.replace(/^https?:\/\//i, '').replace(/^www\./i, '').replace(/\/$/, '');
</script>
