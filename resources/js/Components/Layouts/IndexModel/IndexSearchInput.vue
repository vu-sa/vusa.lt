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
  ></NInput>
</template>

<script setup lang="ts">
import { NInput } from "naive-ui";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { useDebounceFn } from "@vueuse/core";

const props = defineProps<{
  // model?: string;
  payloadName: string;
}>();

// TODO: fix this event
const emit = defineEmits<{
  (event: "resetPaginate"): void;
}>();

const loading = ref(false);

const handleSearchInput = useDebounceFn((input) => {
  loading.value = true;
  router.reload({
    // only: [props.model],
    data: { page: 1, [props.payloadName]: input },
    onSuccess: () => {
      emit("resetPaginate");
      loading.value = false;
    },
  });
}, 500);
</script>
