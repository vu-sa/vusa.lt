<template>
  <div class="flex gap-2">
    <NPopover>
      Išvalyti paiešką...
      <template #trigger>
        <NButton round @click="sweepSearch"
          ><template #icon><NIcon :component="Broom16Regular"></NIcon></template
        ></NButton>
      </template>
    </NPopover>
    <NInputGroup>
      <NInput
        v-model:value="searchValue"
        class="mb-4 md:col-span-4"
        type="text"
        clearable
        round
        :placeholder="`${$t('Įvesk pavadinimą ir spausk „Enter“')}...`"
        @update:value="searchIsDirty = true"
        @keyup.enter="handleSearchInput"
        ><template #prefix>
          <NIcon class="mr-1" :component="Search24Filled" /> </template
      ></NInput>
      <NButton
        round
        :loading="loading"
        :type="searchIsDirty ? 'primary' : 'default'"
        @click="handleSearchInput"
        ><template #icon> <NIcon :component="Search24Filled" /> </template
      ></NButton>
    </NInputGroup>
  </div>
</template>

<script setup lang="ts">
import { Broom16Regular, Search24Filled } from "@vicons/fluent";
import { NButton, NIcon, NInput, NInputGroup, NPopover } from "naive-ui";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

// TODO: fix this event
const emit = defineEmits<{
  (event: "completeSearch"): void;
  (event: "sweep"): void;
}>();

const props = defineProps<{
  // model?: string;
  payloadName: string;
}>();

const loading = ref(false);
const searchIsDirty = ref(false);
const searchValue = ref("");

const handleSearchInput = () => {
  loading.value = true;
  router.reload({
    data: { page: 1, [props.payloadName]: searchValue.value },
    onSuccess: () => {
      emit("completeSearch");
      loading.value = false;
      searchIsDirty.value = false;
    },
  });
};

const sweepSearch = () => {
  searchValue.value = "";
  searchIsDirty.value = false;
  emit("sweep");
};
</script>
