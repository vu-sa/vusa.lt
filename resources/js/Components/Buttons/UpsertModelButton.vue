<template>
  <NPopconfirm @positive-click="upsertModel">
    <template #trigger>
      <NSpin :show="showSpin" size="small">
        <NButton type="primary">{{ buttonText }}</NButton>
      </NSpin>
    </template>
    Ar tikrai {{ buttonText }}?
  </NPopconfirm>
</template>

<script setup lang="ts">
import { NButton, NPopconfirm, NSpin, type UploadFileInfo } from "naive-ui";
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps<{
  form: any;
  images?: UploadFileInfo[];
  routeParameters?: string[];
  modelRoute: string;
}>();

const showSpin = ref(false);

const modelMethod = computed(() => {
  return props.modelRoute.includes("update") ? "patch" : "post";
});

const buttonText = computed(() => {
  return modelMethod.value === "post" ? "Sukurti" : "Atnaujinti";
});

const upsertModel = () => {
  // check for substring method in props.modelRoute

  if (props.images === undefined) {
    showSpin.value = true;
    props.form.submit(
      modelMethod.value,
      route(props.modelRoute, props.routeParameters ?? props.form.id),
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
    // because formdata doesn't support patch, _method is needed to spoof it
    router.post(
      route(props.modelRoute, props.routeParameters ?? props.form.id),
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
