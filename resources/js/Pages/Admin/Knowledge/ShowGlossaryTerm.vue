<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import type { GlossaryTermPageData } from '@/Types/learning';
defineProps<{ term: GlossaryTermPageData }>();
</script>

<template>
  <OverviewPage :title="term.term">
    <p v-if="term.abbreviation">{{ term.abbreviation }}</p>
    <p>{{ term.definition }}</p>
    <ul>
      <li v-for="item in term.items" :key="item.id"><Link :href="item.url">{{ item.title }}</Link></li>
    </ul>
  </OverviewPage>
</template>
