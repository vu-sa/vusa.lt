<template>
  <component
    :is="disabled ? NPopover : NPopconfirm"
    positive-text="Ištrinti!"
    negative-text="Palikti"
    @positive-click="destroyModel()"
  >
    <template #trigger>
      <div class="flex">
        <NButton type="error" :disabled="disabled" :size="size">
          <NIcon size="18">
            <Delete20Filled />
          </NIcon>
        </NButton>
      </div>
    </template>
    <template v-if="disabled"
      >Pirmiausia išimkite visus kontaktus iš pareigybės.</template
    >
    <template v-else>Ištrinto elemento nebus galima atkurti!</template>
  </component>
</template>

<script setup lang="ts">
import { Delete20Filled } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import {
  NButton,
  NIcon,
  NPopconfirm,
  NPopover,
  createDiscreteApi,
} from "naive-ui";
import route from "ziggy-js";

const props = defineProps<{
  disabled?: boolean;
  form: App.Models.ModelTemplate;
  modelRoute: string;
  size?: "small" | "medium" | "large";
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
