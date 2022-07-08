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

<script setup>
import { Inertia } from "@inertiajs/inertia";
import { NButton, NPopconfirm, NSpin, useMessage } from "naive-ui";
import { ref } from "vue";

const props = defineProps({
  model: { type: Object, default: null },
  modelRoute: { type: String, default: null },
});

const showSpin = ref(false);
const message = useMessage();

const upsertModel = () => {
  showSpin.value = !showSpin.value;
  Inertia.patch(route(props.modelRoute, props.model.id), props.model, {
    onSuccess: () => {
      showSpin.value = !showSpin.value;
      message.success("Sėkmingai atnaujinta!");
    },
    onError: () => {
      showSpin.value = !showSpin.value;
    },
    preserveScroll: true,
  });
};
</script>
