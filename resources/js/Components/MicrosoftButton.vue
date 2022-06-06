<template>
  <NButton @click="microsoftRedirect" :loading="loadingRef" type="warning">
    <template #icon>
      <NIcon>
        <Microsoft />
      </NIcon>
    </template>
    Tęsti su Microsoft
  </NButton>
</template>

<script setup>
import { Inertia } from "@inertiajs/inertia";
import { NButton, NIcon, useMessage } from "naive-ui";
import { Microsoft } from "@vicons/fa";
import { ref } from "vue";

const loadingRef = ref(false);
const message = useMessage();

const microsoftRedirect = () => {
  loadingRef.value = true;
  window.location.href = route("microsoft.callback");
};
</script>
