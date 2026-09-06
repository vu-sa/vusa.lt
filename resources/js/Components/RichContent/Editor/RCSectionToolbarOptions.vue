<template>
  <div v-if="!hasSection" class="border-t border-border pt-3 space-y-3">
    <Button
      type="button"
      variant="outline"
      size="sm"
      class="w-full justify-center"
      data-rc-toolbar-add-section
      @click="addSection"
    >
      <IFluentAdd12Regular class="mr-1.5 size-3.5" />
      {{ $t('rich-content.add_section') }}
    </Button>
    <RCPresentationPicker
      :model-value="options.presentation"
      :plain-padding="options.plainPadding"
      :disabled="presentationDisabled"
      @update:model-value="patchOptions({ presentation: $event })"
      @update:plain-padding="patchOptions({ plainPadding: $event })"
    />
  </div>

  <div v-else class="border-t border-border pt-3 space-y-3" data-rc-toolbar-section-fields>
    <div class="flex items-center justify-between">
      <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('rich-content.section_options') }}
      </span>
      <Button
        type="button"
        variant="ghost"
        size="icon"
        class="size-6 text-muted-foreground hover:text-destructive"
        :title="$t('rich-content.remove_section')"
        data-rc-toolbar-remove-section
        @click="removeSection"
      >
        <IFluentDelete24Regular class="size-3.5" />
      </Button>
    </div>

    <RCSectionOptionsFields v-model="options" :presentation-disabled />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCSectionOptionsFields from './RCSectionOptionsFields.vue';
import RCPresentationPicker from './RCPresentationPicker.vue';

import type { SectionOptions } from '@/Types/contentParts';
import { Button } from '@/Components/ui/button';
import IFluentAdd12Regular from '~icons/fluent/add12-regular';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';

const props = defineProps<{
  presentationDisabled?: boolean;
}>();

const options = defineModel<SectionOptions>({ required: true });

function patchOptions(patch: Partial<SectionOptions>): void {
  options.value = { ...options.value, ...patch };
}

const isSectionActive = ref(false);

const hasSectionContent = computed(() => Boolean(
  options.value?.title?.trim()
  || options.value?.subtitle?.trim()
  || options.value?.eyebrow?.trim(),
));

const hasSection = computed(() => hasSectionContent.value || isSectionActive.value);

watch(hasSectionContent, (hasContent) => {
  if (hasContent) {
    isSectionActive.value = true;
  }
}, { immediate: true });

function addSection(): void {
  isSectionActive.value = true;
  options.value = {
    ...options.value,
    title: options.value?.title ?? '',
  };
}

function removeSection(): void {
  isSectionActive.value = false;
  options.value = {
    ...options.value,
    title: '',
    subtitle: '',
    eyebrow: '',
  };
}
</script>
