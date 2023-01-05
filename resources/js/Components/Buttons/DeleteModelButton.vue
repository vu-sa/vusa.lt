<template>
  <component
    :is="disabled ? NPopover : NPopconfirm"
    positive-text="Ištrinti!"
    negative-text="Palikti"
    @positive-click="destroyModel()"
  >
    <template #trigger>
      <NButton
        type="error"
        quaternary
        :loading="loading"
        :disabled="disabled"
        :size="size"
      >
        <template #icon>
          <NIcon :component="Delete20Filled" />
        </template>
      </NButton>
    </template>
    <!-- TODO: make this logic only for duties, or remove it -->
    <template v-if="disabled"
      >Pirmiausia išimkite visus kontaktus iš pareigybės.</template
    >
    <template v-else>Ištrinto elemento nebus galima atkurti!</template>
  </component>
</template>

<script setup lang="ts">
import { Delete20Filled } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NButton, NIcon, NPopconfirm, NPopover } from "naive-ui";
import { ref } from "vue";
import route from "ziggy-js";

const props = defineProps<{
  disabled?: boolean;
  form: App.Models.ModelTemplate;
  modelRoute: string;
  size?: "small" | "medium" | "large";
}>();

const loading = ref(false);

const destroyModel = () => {
  loading.value = true;
  Inertia.delete(route(props.modelRoute, props.form.id), {
    onSuccess: () => {
      loading.value = false;
    },
    preserveScroll: true,
  });
};
</script>
