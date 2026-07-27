<template>
  <div class="flex flex-wrap items-center gap-3">
    <!-- Registration / participation CTA -->
    <Button
      v-if="registrationUrl && !isPast"
      :size="variant === 'sticky' ? 'lg' : 'lg'"
      :class="[
        'gap-2.5 font-semibold px-6 border-0',
        variant === 'sticky' ? 'flex-1' : 'shadow-lg shadow-black/20',
        isLive ? 'bg-emerald-500 hover:bg-emerald-600 text-white' : 'bg-vusa-red hover:bg-red-700 text-white',
      ]"
      as="a"
      :href="registrationUrl"
      target="_blank"
      rel="noopener noreferrer"
    >
      <IFluentPlay20Filled v-if="isLive" class="w-5 h-5" />
      <IFluentPersonAdd20Regular v-else class="w-5 h-5" />
      {{ isLive ? $t('Dalyvauk dabar') : $t('Registruotis') }}
    </Button>

    <template v-if="variant === 'hero'">
      <Button
        v-if="facebookUrl"
        variant="outline"
        size="lg"
        :class="secondaryClasses"
        as="a"
        :href="facebookUrl"
        target="_blank"
        rel="noopener noreferrer"
      >
        <ISimpleIconsFacebook class="w-5 h-5" />
        <span class="hidden sm:inline">Facebook</span>
      </Button>

      <Button
        variant="outline"
        size="lg"
        :class="secondaryClasses"
        @click="handleShare"
      >
        <IFluentShare20Regular class="w-5 h-5" />
        <span class="hidden sm:inline">{{ $t('Dalinkis') }}</span>
      </Button>
    </template>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import Button from '@/Components/ui/button/Button.vue';

const props = withDefaults(defineProps<{
  registrationUrl?: string | null;
  facebookUrl?: string | null;
  shareTitle: string;
  isPast: boolean;
  isLive: boolean;
  /** `hero` renders the full action row over the hero image, `sticky` only the CTA. */
  variant?: 'hero' | 'sticky';
  /** Secondary buttons sit on a dark image in the hero, on a light surface below it. */
  onImage?: boolean;
}>(), {
  variant: 'hero',
  onImage: false,
});

const secondaryClasses = computed(() =>
  props.onImage
    ? 'gap-2 bg-white/20 border-white/40 text-white hover:bg-white/30 hover:border-white/60 backdrop-blur-md transition-all'
    : 'gap-2',
);

const handleShare = async () => {
  const shareData = {
    title: props.shareTitle,
    text: props.shareTitle,
    url: window.location.href,
  };

  if (typeof navigator !== 'undefined' && 'share' in navigator) {
    try {
      await navigator.share(shareData);
      return;
    }
    catch {
      // Dismissed or unsupported — fall through to the clipboard.
    }
  }

  try {
    await navigator.clipboard.writeText(window.location.href);
  }
  catch (error) {
    console.error('Failed to copy to clipboard:', error);
  }
};
</script>
