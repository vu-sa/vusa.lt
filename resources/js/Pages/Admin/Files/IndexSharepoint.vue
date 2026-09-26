<template>
  <div class="space-y-6">
    <Head :title="$t('Sharepoint failai')" />

    <header class="flex flex-col gap-1 border-b border-border pb-4">
      <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Sistema') }} · {{ $t('Sharepoint failai') }}
      </p>
      <h1 class="text-2xl font-bold tracking-tight text-foreground font-heading">
        {{ $t('Sharepoint failai') }}
      </h1>
      <p class="text-sm text-muted-foreground">
        {{ $t('Peržiūrėkite ir tvarkykite universiteto „Microsoft SharePoint“ dokumentus bei failus.') }}
      </p>
    </header>

    <SharepointFileManager :starting-path="path" />
  </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import SharepointFileManager from '@/Features/Admin/SharepointFileManager/SharepointFileManager.vue';

defineProps<{
  path: string;
}>();
</script>
