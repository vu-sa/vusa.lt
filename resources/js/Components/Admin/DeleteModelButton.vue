<template>
  <NPopconfirm
    positive-text="Ištrinti!"
    negative-text="Palikti"
    @positive-click="destroyModel()"
  >
    <template #trigger>
      <div class="mx-4 flex">
        <NButton text type="error">
          <NIcon size="18">
            <Delete20Filled />
          </NIcon>
        </NButton>
      </div>
    </template>
    Ištrinto elemento nebus galima atkurti!
  </NPopconfirm>
</template>

<script setup>
import { Delete20Filled } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NButton, NIcon, NPopconfirm, useMessage } from "naive-ui";

const props = defineProps({
  model: { type: Object, default: null },
  modelRoute: { type: String, default: null },
});

const message = useMessage();

const destroyModel = () => {
  Inertia.delete(route(props.modelRoute, props.model.id), {
    onSuccess: () => {
      message.success("Kalendoriaus įrašas ištrintas!");
    },
    preserveScroll: true,
  });
};
</script>
