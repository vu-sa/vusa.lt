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
  (e: "click", value: string | null): void;
}>();

defineProps<{
  buttonNames: Array<string | null>;
}>();

const currentButton = ref<string | null>(null);

const handleClick = (name: string | null) => {
  currentButton.value = name ?? "Be pavadinimo";
  emit("click", name);
};
</script>
