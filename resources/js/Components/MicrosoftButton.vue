<template>
  <NButton :loading="loadingRef" type="warning" @click="microsoftRedirect">
    <template #icon>
      <NIcon>
        <Microsoft />
      </NIcon>
    </template>
    Tęsti su Microsoft
  </NButton>
</template>

<script setup>
import { Microsoft } from "@vicons/fa";
import { NButton, NIcon } from "naive-ui";
import { ref } from "vue";

const loadingRef = ref(false);

const microsoftRedirect = () => {
  loadingRef.value = true;
  window.location.href = route("microsoft.redirect");
};
</script>
