<template>
  <StatusBadge v-if="active === false || active === 0" :status="inactiveInstitutionStatus" voice="sentence" />
</template>

<script setup lang="ts">
import StatusBadge from '@/Components/Patterns/StatusBadge.vue';
import { inactiveInstitutionStatus } from '@/Constants/statuses';

defineProps<{ active?: boolean | number }>();
</script>
