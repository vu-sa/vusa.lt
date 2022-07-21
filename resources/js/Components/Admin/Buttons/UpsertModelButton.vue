<template>
  <NPopconfirm @positive-click="upsertModel">
    <template #trigger>
      <NSpin :show="showSpin" size="small">
        <NButton>{{ capitalize(buttonText) }}</NButton>
      </NSpin>
    </template>
    Ar tikrai {{ buttonText }}?
  </NPopconfirm>
</template>

<script setup lang="ts">
import { Inertia, Method } from "@inertiajs/inertia";
import { NButton, NPopconfirm, NSpin, createDiscreteApi } from "naive-ui";
import { capitalize } from "lodash";
import { computed, ref } from "vue";
import route from "ziggy-js";

const props = defineProps<{
  model: App.Models.ModelTemplate;
  modelRoute: string;
}>();

const showSpin = ref(false);
const { message } = createDiscreteApi(["message"]);

const modelMethod = computed(() => {
  return props.modelRoute.includes("update") ? Method.PATCH : Method.POST;
});

const buttonText = computed(() => {
  //captizalize
  return modelMethod.value === Method.POST ? "sukurti" : "atnaujinti";
});

const upsertModel = () => {
  // check for substring method in props.modelRoute

  showSpin.value = true;
  Inertia.visit(route(props.modelRoute, props.model.id), {
    method: modelMethod.value,
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
