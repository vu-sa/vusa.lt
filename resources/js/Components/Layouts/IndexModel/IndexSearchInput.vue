<template>
  <NInput
    class="mb-4 md:col-span-4"
    type="text"
    size="medium"
    clearable
    round
    placeholder="Ieškoti pagal pavadinimą..."
    :loading="loading"
    @input="handleSearchInput"
    ><template #prefix>
      <NIcon class="mr-1" :component="Search24Filled" /> </template
  ></NInput>
</template>

<script setup lang="ts">
import { NIcon, NInput } from "naive-ui";
import { Search24Filled } from "@vicons/fluent";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { useDebounceFn } from "@vueuse/core";

const props = defineProps<{
  // model?: string;
  payloadName: string;
}>();

// TODO: fix this event
const emit = defineEmits<{
  (event: "completeSearch"): void;
}>();

const loading = ref(false);

const handleSearchInput = useDebounceFn((input) => {
  loading.value = true;
  router.reload({
    data: { page: 1, [props.payloadName]: input },
    onSuccess: () => {
      emit("completeSearch");
      loading.value = false;
    },
  });
}, 500);
</script>
