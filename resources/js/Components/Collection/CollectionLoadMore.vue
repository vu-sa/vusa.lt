<template>
  <div v-if="hasMore" class="flex justify-center pt-6" data-slot="collection-load-more">
    <Button variant="brand-outline" :disabled="loading" @click="emit('load')">
      <Spinner v-if="loading" class="size-4" />
      {{ $t('Rodyti daugiau') }}
    </Button>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import { Spinner } from '@/Components/ui/spinner';

defineProps<{
  hasMore: boolean;
  loading: boolean;
}>();

const emit = defineEmits<{
  load: [];
}>();
</script>
