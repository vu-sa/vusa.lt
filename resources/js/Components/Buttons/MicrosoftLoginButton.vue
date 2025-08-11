<template>
  <Button
    size="lg"
    class="w-full bg-gradient-to-r from-vusa-red to-red-600 hover:from-vusa-red/90 hover:to-red-600/90 text-white font-medium shadow-lg transition-all duration-200 focus:ring-2 focus:ring-vusa-red/20 disabled:opacity-70 disabled:cursor-not-allowed"
    :disabled="loading"
    @click="microsoftRedirect"
  >
    <span v-if="loading" class="flex items-center">
      <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2" />
      {{ $t("auth.continue_microsoft") }}
    </span>
    <span v-else class="flex items-center">
      <IMdiMicrosoft class="w-5 h-5 mr-2" />
      {{ $t("auth.continue_microsoft") }}
    </span>
  </Button>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { Button } from "@/Components/ui/button";
import IMdiMicrosoft from "~icons/mdi/microsoft";

const loading = ref(false);

const microsoftRedirect = () => {
  loading.value = true;
  window.location.href = route("microsoft.redirect");
};
</script>
