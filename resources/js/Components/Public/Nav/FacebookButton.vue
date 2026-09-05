<template>
  <Button v-if="iconOnly" variant="ghost" size="icon" as-child :class="socialIconButtonClass">
    <a :href="page.props.organization.social.facebook" target="_blank" rel="noopener noreferrer" :aria-label="$t('Facebook')">
      <ISimpleIconsFacebook class="size-4" />
    </a>
  </Button>
  <Button v-else variant="ghost" size="sm" as-child>
    <a :href="page.props.organization.social.facebook" target="_blank" rel="noopener noreferrer" title="Facebook">
      <ISimpleIconsFacebook class="w-4 h-4 mr-2" />
      <slot />
    </a>
  </Button>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { usePage } from '@inertiajs/vue3';

import { socialIconButtonClass } from './socialIconButtonClass';

import { Button } from '@/Components/ui/button';

/** `iconOnly` renders the square bordered treatment used in the footer's social row. */
defineProps<{
  iconOnly?: boolean;
}>();

const page = usePage();
</script>
