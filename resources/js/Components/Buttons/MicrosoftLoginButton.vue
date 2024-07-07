<template>
  <NButton type="primary" size="large" :loading @click="microsoftRedirect">
    <template #icon>
      <IMdiMicrosoft />
    </template>
    {{ $t("auth.continue_microsoft") }}
  </NButton>
</template>

<script setup lang="ts">
import { ref } from "vue";

const loading = ref(false);

const microsoftRedirect = () => {
  loading.value = true;
  window.location.href = route("microsoft.redirect");
};
</script>
