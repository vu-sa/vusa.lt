<template>
  <div class="flex flex-col gap-4 sm:gap-6" :class="{
    'lg:flex-row lg:gap-6': institution.image_url && !onlyVertical,
  }">
    <!-- Image with overlay logo for grid layout -->
    <div v-if="institution.image_url && !imageError && !onlyVertical"
      class="relative h-32 w-full rounded-lg shadow-sm transition-shadow hover:shadow-md sm:h-40 lg:h-32 lg:w-48 lg:flex-shrink-0">
      <img :src="institution.image_url" :alt="institution.name ? `${institution.name} image` : 'Institution image'"
        class="h-full w-full rounded-lg object-cover blur-[1px]" style="object-position: 50% 35%" loading="lazy"
        @error="imageError = true">
      <div class="absolute inset-0 bg-black/10 rounded-lg" />
      <img v-if="institution.logo_url" :alt="`${institution.name} logo`"
        class="absolute left-1/2 top-1/2 size-16 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-white object-contain shadow-lg sm:size-20"
        :src="institution.logo_url" loading="lazy">
    </div>

    <!-- Standard image for vertical layout -->
    <img v-else-if="institution.image_url && !imageError && onlyVertical" :src="institution.image_url"
      :alt="institution.name ? `${institution.name} image` : 'Institution image'"
      class="h-32 w-full rounded-lg object-cover shadow-sm transition-shadow hover:shadow-md sm:h-40 lg:h-40"
      style="object-position: 50% 35%" loading="lazy" @error="imageError = true">
    <div class="w-full" :class="[imageError ? 'col-span-full' : '']">
      <div>
        <a class="group inline-flex w-fit items-center gap-3 sm:gap-4" :href="route('contacts.institution', {
          institution: institution.id,
          subdomain: institution.tenant?.alias === 'vusa' ? 'www' : institution.tenant?.alias ?? 'www',
          lang: $page.props.app.locale,
        })" :aria-describedby="institution.description ? `institution-${institution.id}-description` : undefined">
          <img v-if="institution.logo_url && onlyVertical" :alt="`${institution.name} logo`"
            class="size-12 rounded-full border border-zinc-200 bg-white object-contain shadow-sm transition-shadow group-hover:shadow-md dark:border-zinc-700 sm:size-16"
            :src="institution.logo_url" loading="lazy">
          <div class="min-w-0 flex-1">
            <div class="space-y-4">
              <h2 :id="`institution-${institution.id}-title`"
                class="mb-0 mt-0 w-fit text-xl font-bold leading-tight text-zinc-800 transition-colors group-hover:text-vusa-red dark:text-zinc-100 sm:text-2xl xl:text-3xl">
                {{ institution.name }}
              </h2>
              <div class="flex flex-col gap-2 mt-2 sm:flex-row sm:flex-wrap sm:gap-3">
                <!-- Social Media Links with handles -->
                <template v-if="institution.facebook_url || institution.instagram_url">
                  <div class="flex flex-wrap gap-1.5">
                    <a v-if="institution.facebook_url" :href="institution.facebook_url" target="_blank"
                      rel="noopener noreferrer"
                      class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 transition-colors hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50"
                      @click.stop>
                      <IMdiFacebook class="size-3" />
                      <span class="text-[10px] font-medium">{{ extractSocialHandle(institution.facebook_url, 'facebook')
                        }}</span>
                    </a>
                    <a v-if="institution.instagram_url" :href="institution.instagram_url" target="_blank"
                      rel="noopener noreferrer"
                      class="inline-flex items-center gap-1 rounded-md bg-pink-50 px-2 py-0.5 text-xs font-medium text-pink-700 transition-colors hover:bg-pink-100 dark:bg-pink-900/30 dark:text-pink-300 dark:hover:bg-pink-900/50"
                      @click.stop>
                      <IMdiInstagram class="size-3" />
                      <span class="text-[10px] font-medium">{{ extractSocialHandle(institution.instagram_url,
                        'instagram') }}</span>
                    </a>
                  </div>
                </template>

                <!-- Contact Links -->
                <div class="flex flex-wrap gap-1.5">
                  <a v-if="institution.website" :href="institution.website" target="_blank" rel="noopener noreferrer"
                    class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                    @click.stop>
                    <IFluentGlobe20Regular class="size-3" />
                    <span class="text-xs font-medium">{{ extractDomain(institution.website) }}</span>
                  </a>
                  <a v-if="institution.email" :href="`mailto:${institution.email}`"
                    class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                    @click.stop>
                    <IFluentMail20Regular class="size-3" />
                    <span class="text-xs font-medium">{{ institution.email }}</span>
                  </a>
                  <a v-if="institution.phone" :href="`tel:${institution.phone}`"
                    class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                    @click.stop>
                    <IFluentPhone20Regular class="size-3" />
                    <span class="text-xs font-medium">{{ institution.phone }}</span>
                  </a>
                </div>
              </div>
            </div>
            <small v-for="institutionType in institution.types" v-show="!props.hideTypes" :key="institutionType.id"
              class="mb-4 inline-flex gap-2 text-zinc-500 last:mb-0">
              <span>{{ $t(institutionType.title) }}</span>
              <InfoPopover v-if="institutionType.description"> {{ institutionType.description }} </InfoPopover>
            </small>
          </div>
        </a>
      </div>
      <slot name="more" />
      <div v-if="institution.description" class="my-4 sm:my-5">
        <Collapsible v-if="isMobile" v-model:open="isDescriptionOpen">
          <CollapsibleTrigger class="w-full" :aria-expanded="isDescriptionOpen">
            <div class="flex items-center justify-between gap-4 text-left">
              <h2 class="mb-0 mt-0 font-medium hover:underline focus:underline">
                {{ $t('forms.fields.description') }}
              </h2>
              <Button variant="ghost" size="sm" class="size-6 rounded-full p-0 shrink-0">
                <IFluentChevronDown24Regular v-if="!isDescriptionOpen"
                  class="size-4 transition-transform duration-200" />
                <IFluentChevronUp24Regular v-else class="size-4 transition-transform duration-200" />
                <span class="sr-only">{{ isDescriptionOpen ? $t('Hide description') : $t('Show description') }}</span>
              </Button>
            </div>
          </CollapsibleTrigger>
          <CollapsibleContent>
            <div :id="`institution-${institution.id}-description`"
              class="typography max-w-[80ch] pt-3 text-base leading-6" v-html="institution.description" />
          </CollapsibleContent>
        </Collapsible>
        <div v-else :id="`institution-${institution.id}-description`"
          class="typography max-w-[80ch] text-sm leading-6 sm:text-base" v-html="institution.description" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { breakpointsTailwind, useBreakpoints } from "@vueuse/core";
import { ref } from "vue";

import Button from "../ui/button/Button.vue";
import Collapsible from "../ui/collapsible/Collapsible.vue";
import CollapsibleContent from "../ui/collapsible/CollapsibleContent.vue";
import CollapsibleTrigger from "../ui/collapsible/CollapsibleTrigger.vue";
import InfoPopover from "../Buttons/InfoPopover.vue";

const props = defineProps<{
  institution: App.Entities.Institution;
  onlyVertical?: boolean;
  hideTypes?: boolean;
}>();

const breakpoints = useBreakpoints(breakpointsTailwind);
const isMobile = breakpoints.smallerOrEqual("sm");

const imageError = ref(false);
const isDescriptionOpen = ref(false);

// Extract social media handles from URLs
const extractSocialHandle = (url: string, platform: string) => {
  if (!url) return '';

  try {
    const urlObj = new URL(url);
    const { pathname } = urlObj;

    if (platform === 'facebook') {
      // Handle facebook.com/pages/name/id or facebook.com/name
      const parts = pathname.split('/').filter(part => part && part !== 'pages');
      return parts.length > 0 ? `@${parts[0]}` : 'Facebook';
    }

    if (platform === 'instagram') {
      // Handle instagram.com/name
      const parts = pathname.split('/').filter(part => part);
      return parts.length > 0 ? `@${parts[0]}` : 'Instagram';
    }

    return platform;
  } catch {
    return platform;
  }
};

// Extract domain from website URL
const extractDomain = (url: string) => {
  if (!url) return '';

  try {
    const urlObj = new URL(url.startsWith('http') ? url : `https://${url}`);
    return urlObj.hostname.replace('www.', '');
  } catch {
    return url;
  }
};
</script>
