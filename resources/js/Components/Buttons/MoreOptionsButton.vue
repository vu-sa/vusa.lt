<template>
  <NDropdown
    placement="bottom-end"
    trigger="click"
    :options="dropdownOptions"
    @select="handleSelect"
  >
    <NButton
      :size="small ? 'tiny' : 'small'"
      :disabled="disabled"
      circle
      quaternary
      @click.stop
      ><template #icon
        ><NIcon :component="MoreHorizontal24Filled"></NIcon></template
    ></NButton>
  </NDropdown>
  <NModal
    v-model:show="showDeleteModal"
    preset="dialog"
    title="Ištrinti įrašą?"
    content="Šis įrašas bus ištrintas negrįžtamai..."
    type="warning"
    positive-text="Ištrinti"
    negative-text="Atšaukti"
    @positive-click="$emit('deleteClick')"
    @negative-click="showDeleteModal = false"
  />
</template>

<script setup lang="tsx">
import {
  Delete24Filled,
  Edit24Filled,
  MoreHorizontal24Filled,
} from "@vicons/fluent";
import {
  type DropdownOption,
  NButton,
  NDropdown,
  NIcon,
  NModal,
} from "naive-ui";
import { computed, ref } from "vue";

const emit = defineEmits(["editClick", "deleteClick"]);

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
    label: "Redaguoti",
    key: "edit",
    icon: () => {
      return <NIcon component={Edit24Filled}></NIcon>;
    },
    show: props.edit,
  },
  {
    label: "Ištrinti",
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
      break;
  }
};
</script>
