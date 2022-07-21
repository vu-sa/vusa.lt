<template>
  <NPopconfirm
    positive-text="Ištrinti!"
    negative-text="Palikti"
    @positive-click="destroyModel()"
  >
    <template #trigger>
      <div class="mx-4 flex">
        <NButton type="error">
          <NIcon size="18">
            <Delete20Filled />
          </NIcon>
        </NButton>
      </div>
    </template>
    Ištrinto elemento nebus galima atkurti!
  </NPopconfirm>
</template>

<script setup lang="ts">
import { Delete20Filled } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NButton, NIcon, NPopconfirm, createDiscreteApi } from "naive-ui";
import route from "ziggy-js";

const props = defineProps<{
  form: App.Models.ModelTemplate;
  modelRoute: string;
}>();

const { message } = createDiscreteApi(["message"]);

const destroyModel = () => {
  Inertia.delete(route(props.modelRoute, props.form.id), {
    onSuccess: () => {
      message.success("Įrašas ištrintas!");
    },
    preserveScroll: true,
  });
};
</script>
