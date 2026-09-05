<template>
  <div class="flex flex-wrap items-center gap-3">
    <!-- Registration / participation CTA -->
    <Button
      v-if="registrationUrl && !isPast"
      variant="brand"
      size="public"
      as="a"
      :href="registrationUrl"
      target="_blank"
      rel="noopener noreferrer"
    >
      <IFluentPlay20Filled v-if="isLive" class="size-4" />
      <IFluentPersonAdd20Regular v-else class="size-4" />
      {{ isLive ? $t('Dalyvauk dabar') : $t('Registruotis') }}
    </Button>

    <Button
      v-if="facebookUrl"
      variant="brand-outline"
      size="public"
      as="a"
      :href="facebookUrl"
      target="_blank"
      rel="noopener noreferrer"
    >
      <ISimpleIconsFacebook class="size-4" />
      <span class="hidden sm:inline">Facebook</span>
    </Button>

    <Button
      variant="brand-outline"
      size="public"
      @click="handleShare"
    >
      <IFluentShare20Regular class="size-4" />
      <span class="hidden sm:inline">{{ $t('Dalinkis') }}</span>
    </Button>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import Button from '@/Components/ui/button/Button.vue';
import { useShareLink } from '@/Composables/useShareLink';

const props = defineProps<{
  registrationUrl?: string | null;
  facebookUrl?: string | null;
  shareTitle: string;
  isPast: boolean;
  isLive: boolean;
}>();

// Share/clipboard behaviour lives in useShareLink — it is shared with the news article's own
// share row, and it fixes what this used to do: a dismissed share sheet copied the link anyway,
// a successful copy said nothing, and a missing `navigator.clipboard` threw to the console.
const { share } = useShareLink();

const handleShare = () => share({ title: props.shareTitle });
</script>
