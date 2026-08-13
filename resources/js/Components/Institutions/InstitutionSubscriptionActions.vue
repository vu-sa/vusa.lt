<template>
  <TooltipProvider>
    <Tooltip>
      <TooltipTrigger as-child>
        <Button
          variant="ghost"
          size="icon"
          :class="[buttonClass, props.class, followed && followedClass]"
          :disabled="dutyBased || followLoading"
          @click="$emit('toggle-follow')"
        >
          <Loader2 v-if="followLoading" :class="[iconClass, 'animate-spin']" />
          <Eye v-else-if="followed" :class="iconClass" />
          <EyeOff v-else :class="iconClass" />
        </Button>
      </TooltipTrigger>
      <TooltipContent>
        {{ dutyBased
          ? $t('visak.cannot_unfollow_duty_institution')
          : (followed ? $t('visak.unfollow') : $t('visak.follow')) }}
      </TooltipContent>
    </Tooltip>

    <!-- Muting only means anything while following. -->
    <Tooltip v-if="followed">
      <TooltipTrigger as-child>
        <Button
          variant="ghost"
          size="icon"
          :class="[buttonClass, props.class, muted && mutedClass]"
          :disabled="dutyBased || muteLoading"
          @click="$emit('toggle-mute')"
        >
          <Loader2 v-if="muteLoading" :class="[iconClass, 'animate-spin']" />
          <BellOff v-else-if="muted" :class="iconClass" />
          <Bell v-else :class="iconClass" />
        </Button>
      </TooltipTrigger>
      <TooltipContent>
        {{ dutyBased
          ? $t('visak.cannot_mute_duty_institution')
          : (muted ? $t('visak.unmute') : $t('visak.mute')) }}
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Bell, BellOff, Eye, EyeOff, Loader2 } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';

/**
 * Controlled: the parent owns follow/mute state and the pending flags, so a list
 * of institutions keeps one `useInstitutionSubscription` instance rather than one
 * per row.
 */
const props = withDefaults(defineProps<{
  followed: boolean;
  muted: boolean;
  followLoading?: boolean;
  muteLoading?: boolean;
  /** Subscriptions implied by a duty can't be turned off. */
  dutyBased?: boolean;
  size?: 'sm' | 'default';
  /** Applied to both buttons. */
  class?: HTMLAttributes['class'];
  /** Applied to the follow button while following. */
  followedClass?: HTMLAttributes['class'];
  /** Applied to the mute button while muted. */
  mutedClass?: HTMLAttributes['class'];
}>(), {
  size: 'default',
  class: undefined,
  followedClass: undefined,
  mutedClass: undefined,
});

defineEmits<{
  'toggle-follow': [];
  'toggle-mute': [];
}>();

const iconClass = computed(() => (props.size === 'sm' ? 'h-3.5 w-3.5' : 'h-4 w-4'));
const buttonClass = computed(() => (props.size === 'sm' ? 'h-7 w-7' : 'h-8 w-8'));
</script>
