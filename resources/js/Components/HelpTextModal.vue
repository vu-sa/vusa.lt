<template>
  <div>
    <NButton text size="tiny" @click="showModal = true">
      <template #icon
        ><NIcon :component="QuestionCircle32Regular"></NIcon
      ></template>
      {{ title }}
    </NButton>

    <NModal
      v-model:show="showModal"
      class="prose-sm prose max-w-xl dark:prose-invert"
      :title="$t(title)"
      :bordered="false"
      size="large"
      role="card"
      aria-modal="true"
      preset="card"
    >
      <slot />
    </NModal>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { NButton, NIcon, NModal } from "naive-ui";
import { QuestionCircle32Regular } from "@vicons/fluent";
import { ref } from "vue";

defineProps<{
  title: string;
}>();

const showModal = ref(false);
</script>
