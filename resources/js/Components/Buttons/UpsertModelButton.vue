<template>
  <NPopconfirm @positive-click="upsertModel">
    <template #trigger>
      <NSpin :show="showSpin" size="small">
        <NButton>{{ buttonText }}</NButton>
      </NSpin>
    </template>
    Ar tikrai {{ buttonText }}?
  </NPopconfirm>
</template>

<script setup lang="ts">
import { Inertia, Method } from "@inertiajs/inertia";
import { InertiaForm } from "@inertiajs/inertia-vue3";
import { NButton, NPopconfirm, NSpin, type UploadFileInfo } from "naive-ui";
import { computed, ref } from "vue";

const props = defineProps<{
  form: InertiaForm<App.Models.ModelTemplate>;
  images?: UploadFileInfo[];
  modelRoute: string;
}>();

const showSpin = ref(false);

const modelMethod = computed(() => {
  return props.modelRoute.includes("update") ? Method.PATCH : Method.POST;
});

const buttonText = computed(() => {
  return modelMethod.value === Method.POST ? "Sukurti" : "Atnaujinti";
});

const upsertModel = () => {
  // check for substring method in props.modelRoute

  if (props.images === undefined) {
    showSpin.value = true;
    props.form.submit(
      modelMethod.value,
      route(props.modelRoute, props.form.id),
      {
        onSuccess: () => {
          showSpin.value = false;
        },
        onError: () => {
          showSpin.value = false;
        },
      }
    );
  } else {
    showSpin.value = true;
    Inertia.post(
      route(props.modelRoute, props.form.id),
      {
        ...props.form,
        images: props.images,
        _method: modelMethod.value,
      },
      {
        onSuccess: () => {
          showSpin.value = false;
        },
        onError: () => {
          showSpin.value = false;
        },
      }
    );
  }
};
</script>
