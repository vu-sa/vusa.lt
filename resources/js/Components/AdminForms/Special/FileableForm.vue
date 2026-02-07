<template>
  <div>
    <FadeTransition>
      <SuggestionAlert class="mt-4" :show-alert @alert-closed="$emit('close:alert')">
        <p>
          <span><strong>mano.vusa.lt</strong> platformoje failai yra
            laikomi</span>
          <Badge size="tiny" variant="secondary" class="mx-1">
            <IFluentDocumentTableSearch24Regular class="h-3 w-3" />
            <strong>objektuose</strong>
          </Badge><span>
            (institucijose, posėdžiuose, etc.), kad būtų išlaikyta failų
            struktūra.</span>
        </p>
        <p class="my-0">
          Dažniausiai šis <em>objektas</em> parenkamas automatiškai, tačiau
          šiuo atveju turėsi pasirinkti, kur įkelti failą. 😊
        </p>
      </SuggestionAlert>
    </FadeTransition>
    <div class="mt-4 space-y-4">
      <div class="space-y-2">
        <Label>Objektas</Label>
        <Popover v-model:open="popoverOpen">
          <PopoverTrigger as-child>
            <Button
              variant="outline"
              role="combobox"
              class="w-full justify-between font-normal"
              :class="{ 'text-muted-foreground': !selectedFileable }"
            >
              <span class="truncate">{{ selectedPath || 'Pasirink instituciją, susitikimą, etc.' }}</span>
              <div class="ml-auto flex shrink-0 items-center gap-1">
                <button
                  v-if="selectedFileable"
                  type="button"
                  class="rounded-sm p-0.5 hover:bg-accent"
                  @click.stop="selectedFileable = undefined"
                >
                  <X class="size-3" />
                </button>
                <ChevronsUpDown class="size-4 opacity-50" />
              </div>
            </Button>
          </PopoverTrigger>
          <PopoverContent class="w-[--reka-popover-trigger-width] p-0" align="start">
            <div class="flex flex-col">
              <div class="border-b p-2">
                <Input v-model="filterText" placeholder="Ieškoti..." class="h-8" />
              </div>
              <ScrollArea class="max-h-72">
                <Tree
                  v-if="treeOptions.length > 0"
                  :items="treeOptions"
                  :get-key="(item) => item.key"
                  :get-label="(item) => item.label"
                  :is-item-disabled="(item) => item.key === 'institutions' || item.key === 'types'"
                  :filter="filterText"
                  default-expand-all
                  class="p-1"
                  @select="handleTreeSelect"
                />
                <div v-else class="flex items-center justify-center p-4 text-sm text-muted-foreground">
                  <Spinner v-if="isFetching" class="size-4" />
                  <span v-else>Nėra pasirinkimų</span>
                </div>
              </ScrollArea>
            </div>
          </PopoverContent>
        </Popover>
      </div>
      <Button type="button" @click="handleClick">
        Pateikti
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useFetch } from "@vueuse/core";
import { ChevronsUpDown, X } from "lucide-vue-next";

import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Popover, PopoverContent, PopoverTrigger } from "@/Components/ui/popover";
import { ScrollArea } from "@/Components/ui/scroll-area";
import { Spinner } from "@/Components/ui/spinner";
import { Tree } from "@/Components/ui/tree";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import SuggestionAlert from "@/Components/Alerts/SuggestionAlert.vue";

const emit = defineEmits<{
  (e: "close:alert"): void;
  (e: "submit", data: any): void;
}>();

defineProps<{
  showAlert: boolean;
}>();

interface TreeOption {
  label: string
  key: string
  children?: TreeOption[]
}

const popoverOpen = ref(false);
const filterText = ref("");
const selectedFileable = ref<string | undefined>(undefined);
const treeOptions = ref<TreeOption[]>([]);

const { data: fileablesData, isFetching } = useFetch(
  route("api.v1.admin.sharepoint.potentialFileables")
).json();

watch([fileablesData, isFetching], ([data, fetching]) => {
  if (!fetching && data) {
    const institutionOptions = data.institutions?.map(
      (institution: App.Entities.Institution) => ({
        label: institution.name,
        key: `${institution.id}_${institution.name}_Institution`,
        children:
          institution.meetings && institution.meetings.length > 0
            ? institution.meetings?.map((meeting: App.Entities.Meeting) => ({
              label: meeting.title,
              key: `${meeting.id}_${meeting.start_time}_Meeting`,
            }))
            : undefined,
      })
    ) ?? [];

    const typeOptions = data.types?.map((type: App.Entities.Type) => ({
      label: type.title,
      key: `${type.id}_${type.title}_Type`,
    })) ?? [];

    treeOptions.value = [
      {
        label: "Institucijos",
        key: "institutions",
        children: institutionOptions,
      },
      {
        label: "Tipai",
        key: "types",
        children: typeOptions,
      },
    ];
  }
}, { immediate: true });

// Compute display path for the selected item (e.g. "Institucijos / VU SA / 2024-01-15")
const selectedPath = computed(() => {
  if (!selectedFileable.value || treeOptions.value.length === 0) return "";

  function findPath(items: TreeOption[], target: string, path: string[]): string[] | null {
    for (const item of items) {
      if (item.key === target) return [...path, item.label];
      if (item.children) {
        const found = findPath(item.children, target, [...path, item.label]);
        if (found) return found;
      }
    }
    return null;
  }

  const path = findPath(treeOptions.value, selectedFileable.value, []);
  return path ? path.join(" / ") : "";
});

const handleTreeSelect = (_item: Record<string, any>, key: string) => {
  selectedFileable.value = key;
  popoverOpen.value = false;
  filterText.value = "";
};

const handleClick = () => {
  if (!selectedFileable.value) return;

  // delimit fileable type, name and id by _
  const [fileableId, fileableName, fileableType] = selectedFileable.value
    ?.split("_")
    .map((item) => item.trim()) ?? [];

  emit("submit", {
    id: fileableId,
    fileable_name: fileableName,
    type: fileableType,
  });
};
</script>
