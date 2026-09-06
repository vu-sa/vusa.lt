<template>
  <section :class="bandClasses" :aria-labelledby="headingId">
    <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
      <!-- Section head: eyebrow + display heading -->
      <div v-if="editable || showHeader" class="border-b border-border pb-5">
        <EyebrowLabel v-if="editable || showEyebrow">
          <RCInlineText
            as="span"
            :model-value="editable ? (element.json_content?.eyebrow ?? '') : eyebrowText"
            :editable
            :placeholder="$t('VU SA')"
            @update:model-value="updateEyebrow"
          />
        </EyebrowLabel>
        <!-- eslint-disable-next-line vuejs-accessibility/heading-has-content -- RCInlineText renders text at runtime -->
        <h2
          v-if="editable || heading"
          :id="headingId"
          class="u-display mt-2 text-3xl text-foreground sm:text-4xl"
        >
          <RCInlineText
            as="span"
            :model-value="editable ? (element.json_content?.title ?? '') : heading"
            :editable
            :placeholder="$t('Iniciatyvos ir organizacijos')"
            @update:model-value="updateTitle"
          />
        </h2>
      </div>

      <!-- Loading skeleton -->
      <div v-if="!isResolved" class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
        <div v-for="i in 3" :key="i" class="border border-border bg-card p-4 space-y-4">
          <Skeleton class="aspect-[16/10] w-full" />
          <Skeleton class="h-6 w-3/4" />
          <Skeleton class="h-4 w-full" />
        </div>
      </div>

      <!-- Empty state -->
      <div v-else-if="institutions.length === 0" class="mt-8 border border-border bg-card p-12 text-center">
        <div class="mx-auto max-w-md space-y-3">
          <h3 class="text-base font-bold text-foreground">
            {{ $t('search.no_institutions_found') }}
          </h3>
          <p class="text-sm text-muted-foreground">
            {{ $t('search.no_institutions_criteria') }}
          </p>
        </div>
      </div>

      <!-- Institution cards grid -->
      <div v-else class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
        <NewInstitutionCard
          v-for="institution in institutions"
          :key="institution.id"
          :institution
          show-metadata
        />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type { InstitutionList, InstitutionListItem, InstitutionListResolved } from '@/Types/contentParts';
import { EyebrowLabel } from '@/Components/Public/Base';
import { Skeleton } from '@/Components/ui/skeleton';
import RCInlineText from '@/Components/RichContent/Editor/Fullscreen/RCInlineText.vue';
import NewInstitutionCard from '@/Components/Cards/NewInstitutionCard.vue';
import type { BandResolution } from '@/Components/RichContent/bandLayout';
import { BAND_GROUND_CLASS, BAND_PADDING } from '@/Components/RichContent/sectionClasses';

const props = defineProps<{
  element: InstitutionList;
  resolved?: InstitutionListResolved | null;
  editable?: boolean;
  blockKey?: string;
  activeInlineField?: string | null;
  band?: BandResolution;
}>();

const emit = defineEmits<(e: 'update:element', value: InstitutionList) => void>();

const headingId = computed(() => `institution-list-${props.blockKey ?? 'block'}-heading`);

const heading = computed(() => props.element?.json_content?.title || '');
const eyebrowText = computed(() => props.element?.json_content?.eyebrow || '');

const showEyebrow = computed(() => Boolean(eyebrowText.value.trim()));
const showHeader = computed(() => Boolean(heading.value.trim() || eyebrowText.value.trim()));

function updateTitle(title: string): void {
  emit('update:element', {
    ...props.element,
    json_content: { ...props.element.json_content, title },
  });
}

function updateEyebrow(eyebrow: string): void {
  emit('update:element', {
    ...props.element,
    json_content: { ...props.element.json_content, eyebrow },
  });
}

const isResolved = computed(() => props.resolved !== undefined);
const institutions = computed<InstitutionListItem[]>(() => props.resolved?.items ?? []);

const bandClasses = computed(() => props.band?.classes
  ?? ['rc-band', 'relative', 'scroll-mt-32', BAND_PADDING, BAND_GROUND_CLASS.tint, 'rc-viewport']);
</script>
