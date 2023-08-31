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
        :placeholder="`${$t('Ieškoti')}...`"
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
    <NBadge :value="other.length">
      <NPopover trigger="click">
        <template #trigger>
          <NButton circle>
            <template #icon
              ><NIcon :component="MoreVertical24Filled"
            /></template>
          </NButton>
        </template>
        <NCheckboxGroup
          v-model:value="other"
          @update:value="$emit('update:other', $event)"
        >
          <NCheckbox value="showSoftDeleted" label="Rodyti ištrintus"
            >Rodyti ištrintus</NCheckbox
          >
        </NCheckboxGroup>
      </NPopover>
    </NBadge>
  </div>
</template>

<script setup lang="tsx">
import {
  Broom16Regular,
  MoreVertical24Filled,
  Search24Filled,
} from "@vicons/fluent";
import {
  NBadge,
  NButton,
  NCheckbox,
  NCheckboxGroup,
  NIcon,
  NInput,
  NInputGroup,
  NPopover,
} from "naive-ui";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

// TODO: fix this event
const emit = defineEmits<{
  (event: "completeSearch"): void;
  (event: "update:other"): void;
  (event: "sweep"): void;
}>();

const props = defineProps<{
  // model?: string;
  payloadName: string;
}>();

const loading = ref(false);
const searchIsDirty = ref(false);
const searchValue = ref("");
// TODO: on page reload, other is not set according to query parameter
const other = ref([]);

// if query parameter showSoftDeleted is set to true, then show soft deleted models

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
  other.value = [];
  emit("sweep");
};
</script>
