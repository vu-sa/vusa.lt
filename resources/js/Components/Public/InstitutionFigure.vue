<template>
  <div class="grid grid-cols-1 gap-8" :class="{
    'md:grid-cols-[1fr_2fr]': institution.image_url && !onlyVertical,
  }">
    <img v-if="institution.image_url && !imageError" :src="institution.image_url" :alt="institution.name ?? ''"
      class="h-56 w-full rounded-xs object-cover shadow-lg" :class="[imageError ? 'hidden' : '']" style="object-position: 50% 35%"
      @error="imageError = true">
    <div class="w-full" :class="[imageError ? 'col-span-full' : '']">
      <div>
        <a class="inline-flex w-fit items-center gap-4" :href="route('contacts.institution', {
          institution: institution.id,
          subdomain:
            institution.padalinys?.alias === 'vusa'
              ? 'www'
              : institution.padalinys?.alias ?? 'www',
          lang: $page.props.app.locale,
        })
          ">
          <img v-if="institution.logo_url" :alt="institution.name"
            class="size-16 rounded-full border border-zinc-600/50 bg-white object-contain shadow-xs"
            :src="institution.logo_url">
          <div>
            <div class="flex items-center gap-6">
              <h2
                class="mb-1 mt-0 w-fit text-2xl font-bold leading-6 text-zinc-800 transition-colors hover:text-vusa-red dark:text-zinc-100 xl:text-3xl xl:leading-7">
                {{ institution.name }}
              </h2>
              <div class="flex gap-2">
                <a v-if="institution.facebook_url" :href="institution.facebook_url" target="_blank"
                  rel="noopener noreferrer">
                  <NButton tertiary size="small" circle @click.stop>
                    <template #icon>
                      <IMdiFacebook />
                    </template>
                  </NButton>
                </a>
                <a v-if="institution.instagram_url" :href="institution.instagram_url" target="_blank"
                  rel="noopener noreferrer">
                  <NButton tertiary size="small" circle @click.stop>
                    <template #icon>
                      <IMdiInstagram />
                    </template>
                  </NButton>
                </a>
                <a v-if="institution.website" :href="institution.website" target="_blank" rel="noopener noreferrer">
                  <NButton tertiary size="small" circle @click.stop>
                    <template #icon>
                      <IFluentGlobe20Regular />
                    </template>
                  </NButton>
                </a>
                <a v-if="institution.email" :href="`mailto:${institution.email}`">
                  <NButton tertiary size="small" circle @click.stop>
                    <template #icon>
                      <IFluentMail20Regular />
                    </template>
                  </NButton>
                </a>
                <a v-if="institution.phone" :href="`tel:${institution.phone}`">
                  <NButton tertiary size="small" circle @click.stop>
                    <template #icon>
                      <IFluentPhone20Regular />
                    </template>
                  </NButton>
                </a>
              </div>
            </div>
            <small v-for="institutionType in institution.types" :key="institutionType.id"
              class="mb-4 inline-flex gap-2 text-zinc-500 last:mb-0">
              <span>{{ $t(institutionType.title) }}</span>
              <InfoPopover v-if="institutionType.description"> {{ institutionType.description }} </InfoPopover>
            </small>
          </div>
        </a>
      </div>
      <slot name="more" />
      <div v-if="institution.description" class="my-5">
        <Collapsible v-if="isMobile" v-model:open="isDescriptionOpen">
          <CollapsibleTrigger>
            <div class="flex items-center gap-4">
              <h2 class="mb-0 mt-0 underline">
                {{ $t('forms.fields.description') }}
              </h2>
              <NButton size="tiny" circle tertiary>
                <template #icon>
                  <IFluentChevronDown24Regular v-if="!isDescriptionOpen" />
                  <IFluentChevronUp24Regular v-else />
                </template>
              </NButton>
            </div>
          </CollapsibleTrigger>
          <CollapsibleContent>
            <p class="typography max-w-[80ch] text-base leading-6" v-html="institution.description" />
          </CollapsibleContent>
        </Collapsible>
        <p v-else class="typography max-w-[80ch] text-sm leading-6" v-html="institution.description" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { breakpointsTailwind, useBreakpoints } from "@vueuse/core";
import { ref } from "vue";

import Collapsible from "../ui/collapsible/Collapsible.vue";
import CollapsibleContent from "../ui/collapsible/CollapsibleContent.vue";
import CollapsibleTrigger from "../ui/collapsible/CollapsibleTrigger.vue";
import InfoPopover from "../Buttons/InfoPopover.vue";

defineProps<{
  institution: App.Entities.Institution;
  onlyVertical?: boolean;
}>();

const breakpoints = useBreakpoints(breakpointsTailwind);
const isMobile = breakpoints.smallerOrEqual("sm");

const imageError = ref(false);
const isDescriptionOpen = ref(false);
</script>
