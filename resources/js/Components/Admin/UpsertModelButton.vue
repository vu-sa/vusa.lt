<template>
  <NPopconfirm @positive-click="upsertModel">
    <template #trigger>
      <NSpin :show="showSpin" size="small">
        <NButton>Atnaujinti</NButton>
      </NSpin>
    </template>
    Ar tikrai atnaujinti?
  </NPopconfirm>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { NButton, NPopconfirm, NSpin, createDiscreteApi } from "naive-ui";
import { ref } from "vue";

const props = defineProps({
  model: { type: Object, default: null },
  modelRoute: { type: String, default: null },
});

const showSpin = ref(false);
const { message } = createDiscreteApi(["message"]);

const upsertModel = () => {
  // check for substring method in props.modelRoute
  const modelMethod = props.modelRoute.includes("update") ? "PATCH" : "POST";

  showSpin.value = true;
  Inertia.visit(route(props.modelRoute, props.model.id), {
    method: modelMethod,
    data: props.model,
    onSuccess: () => {
      showSpin.value = false;
      message.success("Sėkmingai atnaujinta!");
    },
    onError: () => {
      showSpin.value = false;
    },
    preserveScroll: true,
  });
};
</script>
