<template>
  <NDropdown placement="bottom-end" trigger="click" :options="dropdownOptions" @select="handleSelect">
    <NButton :size="small ? 'tiny' : 'small'" :disabled="disabled" circle quaternary @click.stop><template #icon>
        <IFluentMoreHorizontal24Filled />
      </template></NButton>
  </NDropdown>
  <NModal v-model:show="showDeleteModal" preset="dialog" title="Ištrinti įrašą?"
    content="Šis įrašas bus ištrintas negrįžtamai..." type="warning" :positive-text="$t('forms.delete')"
    :negative-text="$t('forms.cancel')" @positive-click="$emit('deleteClick')"
    @negative-click="showDeleteModal = false" />
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import {
  type DropdownOption,
  NIcon,
} from "naive-ui";
import { computed, ref } from "vue";

import Delete24Filled from "~icons/fluent/delete24-filled";
import Edit24Filled from "~icons/fluent/edit24-filled";

const emit = defineEmits<{
  (event: "editClick"): void;
  (event: "deleteClick"): void;
  (event: "moreOptionClick", key: string): void;
}>();

const props = defineProps<{
  disabled?: boolean;
  small?: boolean;
  edit?: boolean;
  delete?: boolean;
  moreOptions?: DropdownOption[];
}>();

const showDeleteModal = ref(false);

const defaultOptions: DropdownOption[] = [
  {
    label() {
      return $t("forms.edit");
    },
    key: "edit",
    icon: () => {
      return <NIcon component={Edit24Filled}></NIcon>;
    },
    show: props.edit,
  },
  {
    label() {
      return $t("forms.delete");
    },
    key: "delete",
    icon: () => {
      return <NIcon color="#bd2835" component={Delete24Filled}></NIcon>;
    },
    show: props.delete,
  },
];

// add those two arrays of options if they are not empty
const dropdownOptions = computed(() => {
  const options = props.moreOptions ? props.moreOptions : [];
  return [...options, ...defaultOptions];
});

const handleSelect = (key: string) => {
  switch (key) {
    case "edit":
      emit("editClick");
      break;
    case "delete":
      showDeleteModal.value = true;
      break;
    default:
      emit("moreOptionClick", key);
      break;
  }
};
</script>
