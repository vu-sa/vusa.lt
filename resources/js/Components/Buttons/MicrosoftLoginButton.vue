<template>
  <NButton
    type="primary"
    size="large"
    :loading="loadingRef"
    @click="microsoftRedirect"
  >
    <template #icon>
      <NIcon :component="MicrosoftIcon" />
    </template>
    Tęsti su Microsoft
  </NButton>
</template>

<script setup lang="ts">
import { Microsoft as MicrosoftIcon } from "@vicons/fa";
import { NButton, NIcon } from "naive-ui";
import { ref } from "vue";

const loadingRef = ref(false);

const microsoftRedirect = () => {
  loadingRef.value = true;
  window.location.href = route("microsoft.redirect");
};
</script>
