<template>
  <div class="flex flex-wrap items-center gap-3">
    <!-- Registration / participation CTA -->
    <Button
      v-if="registrationUrl && !isPast"
      size="lg"
      :class="[
        'gap-2.5 font-semibold px-6 border-0 shadow-lg shadow-black/20 inline-flex',
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
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import Button from '@/Components/ui/button/Button.vue';
import { useShareLink } from '@/Composables/useShareLink';

const props = defineProps<{
  registrationUrl?: string | null;
  facebookUrl?: string | null;
  shareTitle: string;
  isPast: boolean;
  isLive: boolean;
  /** Secondary buttons sit on a dark image in the hero, on a light surface below it. */
  onImage?: boolean;
}>();

const secondaryClasses = computed(() =>
  props.onImage
    ? 'gap-2 bg-white/20 border-white/40 text-white hover:bg-white/30 hover:border-white/60 backdrop-blur-md transition-all'
    : 'gap-2',
);

// Share/clipboard behaviour lives in useShareLink — it is shared with the news article's own
// share row, and it fixes what this used to do: a dismissed share sheet copied the link anyway,
// a successful copy said nothing, and a missing `navigator.clipboard` threw to the console.
const { share } = useShareLink();

const handleShare = () => share({ title: props.shareTitle });
</script>
