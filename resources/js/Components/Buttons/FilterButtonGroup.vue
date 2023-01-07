<template>
  <NButton
    v-for="(name, index) in buttonNames"
    :key="index"
    :type="currentButton === name ? 'primary' : 'default'"
    round
    size="small"
    @click="handleClick(name)"
    >{{ name }}</NButton
  >
</template>

<script setup lang="tsx">
import { NButton } from "naive-ui";
import { ref } from "vue";

const emit = defineEmits<{
  (e: "click", value: string): void;
}>();

const props = defineProps<{
  buttonNames: string[];
}>();

const currentButton = ref<string>(props.buttonNames[0]);

const handleClick = (name: string) => {
  currentButton.value = name;
  emit("click", name);
};
</script>
