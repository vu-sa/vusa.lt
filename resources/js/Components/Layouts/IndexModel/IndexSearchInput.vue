<template>
  <div class="relative flex gap-2">
    <Badge v-if="hasSoftDeletes" class="absolute -top-3 -right-3">
      <span> {{ other.length }}</span>

    </Badge>
    <NConfigProvider :theme="naiveTheme" class="flex gap-2 flex-1">
      <NPopover>
        Išvalyti paiešką...
        <template #trigger>
          <Button variant="outline" size="icon" class="rounded-full" @click="sweepSearch">
            <IFluentBroom16Regular />
          </Button>
        </template>
      </NPopover>
      <NInputGroup class="flex-1">
        <NInput v-model:value="searchValue" class="mb-4 md:col-span-4" type="text" clearable round
          :placeholder="`${$t('Ieškoti')}...`" @update:value="searchIsDirty = true" @keyup.enter="handleSearchInput">
          <template #prefix>
            <IFluentSearch20Filled />
          </template>
        </NInput>
        <Button class="rounded-full" :disabled="loading" :variant="searchIsDirty ? 'default' : 'outline'" @click="handleSearchInput">
          <Spinner v-if="loading" />
          <IFluentSearch20Filled v-else />
        </Button>
      </NInputGroup>
    </NConfigProvider>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Spinner } from '@/Components/ui/spinner';
import { NConfigProvider, darkTheme } from "naive-ui";
import { useDark } from "@vueuse/core";

// TODO: fix this event
const emit = defineEmits<{
  (event: "completeSearch"): void;
  (event: "update:other"): void;
  (event: "sweep"): void;
}>();

const props = defineProps<{
  // model?: string;
  payloadName: string;
  hasSoftDeletes: boolean;
}>();

const isDark = useDark();
const naiveTheme = computed(() => isDark.value ? darkTheme : null);

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
