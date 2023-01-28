<template>
  <span>
    <strong>{{ notification.data.objectName }}</strong> užsiregistravo per VU SA
    registraciją.
  </span>
</template>

<script setup lang="ts">
defineProps<{
  notification: Record<string, any>;
}>();
</script>
