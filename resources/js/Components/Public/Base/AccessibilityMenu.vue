<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button
        voice="brand"
        variant="ghost"
        size="icon"
        :class="cn('border border-transparent', !isDefault && 'border-brand text-brand', props.class)"
        :aria-label="$t('accessibility.menu_open')"
        :title="$t('accessibility.menu_title')"
        data-slot="accessibility-menu-trigger"
      >
        <IFluentAccessibility24Regular class="size-4" />
        <span v-if="!isDefault" class="sr-only">{{ $t('accessibility.preferences_active') }}</span>
      </Button>
    </PopoverTrigger>

    <PopoverContent align="end" class="z-[70] w-80 p-0" data-slot="accessibility-menu">
      <AccessibilitySettings />
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import AccessibilitySettings from './AccessibilitySettings.vue';

import IFluentAccessibility24Regular from '~icons/fluent/accessibility-24-regular';
import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { cn } from '@/Utils/Shadcn/utils';
import { useAccessibilityPreferences } from '@/Composables/useAccessibilityPreferences';

const props = withDefaults(defineProps<{
  class?: HTMLAttributes['class'];
}>(), {
  class: undefined,
});

const { isDefault } = useAccessibilityPreferences();
</script>
