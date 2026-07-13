<template>
  <Link
    v-if="institutionId"
    :href="route('institutions.show', Array.isArray(institutionId) ? institutionId[0] : institutionId)"
    class="text-primary hover:underline"
  >
    {{ displayName }}
  </Link>
  <template v-else>
    {{ displayName }}
  </template>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
  name?: string;
  institutionId?: string | string[];
  fallback?: string;
}>();

const displayName = computed(() => props.name || props.fallback || '—');
</script>
