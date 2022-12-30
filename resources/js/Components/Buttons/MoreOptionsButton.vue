<template>
  <NDropdown trigger="click" :options="moreOptions" @select="handleSelect">
    <NButton circle :tertiary="!small" :quaternary="small" @click.stop
      ><template #icon
        ><NIcon :component="MoreHorizontal24Filled"></NIcon></template
    ></NButton>
  </NDropdown>
</template>

<script setup lang="tsx">
import {
  Delete24Filled,
  Edit24Filled,
  MoreHorizontal24Filled,
} from "@vicons/fluent";
import { NButton, NDropdown, NIcon } from "naive-ui";

const emit = defineEmits(["editClick", "deleteClick"]);

const props = defineProps<{
  small?: boolean;
  edit?: boolean;
  delete?: boolean;
}>();

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
      return <NIcon color="#bd2835" component={Delete24Filled}></NIcon>;
    },
    show: props.delete,
  },
];

const handleSelect = (key: string) => {
  switch (key) {
    case "edit":
      emit("editClick");
      break;
    case "delete":
      emit("deleteClick");
      break;
    default:
      break;
  }
};
</script>
