<template>
  <tr>
    <td class="permission-description">
      <template v-if="ability === 'create'">
        <IFluentAddCircle24Filled />
        Sukurti
      </template>
      <template v-if="ability === 'read'">
        <IFluentEye24Filled />
        Matyti
      </template>
      <template v-if="ability === 'update'">
        <IFluentEdit24Filled />
        Redaguoti
      </template>
      <template v-if="ability === 'delete'">
        <IFluentDelete24Filled class="text-vusa-red" />
        Ištrinti
      </template>
    </td>
    <td>
      <div class="flex w-64 items-center gap-2">
        <NCheckbox v-model:checked="checkboxPadalinys" :disabled="switchAll || disabled"
          @update:checked="handleUpdate" />
        <NSwitch v-model:value="switchPadalinys" :disabled="switchAll || !checkboxPadalinys || disabled" size="small"
          @update:value="handleUpdate">
          <template #unchecked>
            {{ checkboxPadalinys ? `Savo` : "" }}
          </template>
          <template #checked>
            Visus
          </template>
          <template #checked-icon>
            <NIcon :component="icon" />
          </template>
        </NSwitch>
      </div>
    </td>
    <td>
      <NSwitch v-model:value="switchAll" :disabled="disabled" size="small" @update:value="handleUpdate">
        <template #checked-icon>
          <NIcon :component="icon" />
        </template>
      </NSwitch>
    </td>
  </tr>
</template>

<script setup lang="tsx">
import { type Component, ref } from "vue";
import type { CRUDEnum } from "@/Types/enums";

const emit = defineEmits<{
  (e: "update", scope: string | undefined): void;
}>();

const props = defineProps<{
  ability: CRUDEnum;
  disabled: boolean;
  defaultValue: string | undefined;
  icon: Component;
  permissions: string[];
}>();

const checkboxPadalinys = ref(
  ["own", "padalinys"].includes(props.defaultValue ?? "")
);
const switchPadalinys = ref(props.defaultValue === "padalinys");
const switchAll = ref(props.defaultValue === "*");

const handleUpdate = () => {
  if (!checkboxPadalinys.value) {
    switchPadalinys.value = false;
  }

  if (switchAll.value) {
    checkboxPadalinys.value = true;
    switchPadalinys.value = true;
  }

  if (switchPadalinys.value) {
    checkboxPadalinys.value = true;
  }

  emit("update", decidePermission());
};

const decidePermission = () => {
  if (switchAll.value) {
    return "*";
  }

  if (switchPadalinys.value) {
    return "padalinys";
  }

  if (checkboxPadalinys.value) {
    return "own";
  }

  return undefined;
};

handleUpdate();
</script>

<style scoped>
tr>td.permission-description {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

td {
  padding: 0.5rem;
}
</style>
