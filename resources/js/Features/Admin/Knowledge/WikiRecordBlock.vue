<script setup lang="ts">
import type { WikiPageSummary } from '@/Types/learning';

import WikiPageList from './WikiPageList.vue';

defineProps<{ pages: WikiPageSummary[] }>();
</script>

<template>
  <section aria-labelledby="record-wiki-title" class="space-y-3" data-slot="record-wiki">
    <div>
      <h2 id="record-wiki-title" class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">{{ $t('learning.wiki.in_institution') }}</h2>
      <p class="mt-1 text-sm text-muted-foreground">{{ $t('learning.wiki.in_institution_hint') }}</p>
    </div>
    <WikiPageList :pages />
  </section>
</template>
