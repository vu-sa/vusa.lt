<template>
  <NDropdown trigger="click" :options="moreOptions" @select="handleSelect">
    <NButton
      :size="small ? 'small' : 'medium'"
      circle
      :tertiary="!small"
      :quaternary="small"
      @click.stop
      ><template #icon
        ><NIcon :component="MoreHorizontal24Filled"></NIcon></template
    ></NButton>
  </NDropdown>
  <NModal
    v-model:show="showDeleteModal"
    preset="dialog"
    title="Dialog"
    content="Ar tikrai ištrinti?"
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
import { NButton, NDropdown, NIcon, NModal } from "naive-ui";
import { ref } from "vue";

const emit = defineEmits(["editClick", "deleteClick"]);

const props = defineProps<{
  small?: boolean;
  edit?: boolean;
  delete?: boolean;
}>();

const showDeleteModal = ref(false);

const moreOptions = [
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
      return <NIcon color="text-vusa-red" component={Delete24Filled}></NIcon>;
    },
  },
];

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
