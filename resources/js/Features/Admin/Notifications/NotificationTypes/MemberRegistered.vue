<template>
  <span>
    <strong>{{ data.objectName }}</strong> užsiregistravo per VU SA
    registraciją.
  </span>
</template>

<script setup lang="ts">
defineProps<{
  data: Record<string, any>;
}>();
</script>
