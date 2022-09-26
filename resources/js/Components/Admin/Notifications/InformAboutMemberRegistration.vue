<template>
  <span>
    <strong>{{ data.registeredName }}</strong> užsiregistravo per VU SA
    registraciją.
  </span>
</template>

<script setup lang="ts">
defineProps<{
  data: Record<string, any>;
}>();
</script>
