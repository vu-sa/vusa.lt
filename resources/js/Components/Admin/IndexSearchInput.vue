<template>
  <NInput
    class="md:col-span-4 mb-2"
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
import { Inertia } from "@inertiajs/inertia";
import { NInput } from "naive-ui";
import { ref } from "vue";

import { debounce } from "lodash";

const props = defineProps<{
  payloadName: string;
}>();

// TODO: fix this event
const emit = defineEmits<{
  (event: "resetPaginate"): void;
}>();

const loading = ref(false);

const handleSearchInput = debounce((input) => {
  loading.value = true;
  Inertia.reload({
    data: { page: 1, [props.payloadName]: input },
    onSuccess: () => {
      emit("resetPaginate");
      loading.value = false;
    },
  });
}, 500);
</script>
