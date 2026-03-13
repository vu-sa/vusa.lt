<template>
  <Card>
    <CardHeader>
      <CardTitle class="text-base">{{ $t("Moderatorius") }}</CardTitle>
    </CardHeader>
    <CardContent>
      <div v-if="!editing" class="flex items-center justify-between gap-4">
        <div class="text-sm">
          <span v-if="planningProcess.moderator">
            {{ planningProcess.moderator.name }}
          </span>
          <span v-else class="text-muted-foreground">{{ $t("Nepriskirtas") }}</span>
        </div>
        <Button v-if="canUpdate" variant="outline" size="sm" @click="startEditing">
          {{ $t("Keisti") }}
        </Button>
      </div>

      <div v-else class="flex flex-col gap-3">
        <Label>{{ $t("Ieškoti moderatoriaus") }}</Label>

        <Combobox
          v-model="selectedUser"
          :filter-function="() => userOptions"
          @update:model-value="handleUserSelect"
        >
          <ComboboxAnchor :class="[
            'flex h-9 w-full items-center justify-between gap-2',
            'rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs',
            'border-zinc-200 dark:border-zinc-800 dark:bg-zinc-800/30',
            'focus-within:border-zinc-950 focus-within:ring-zinc-950/50 focus-within:ring-[3px]',
            'dark:focus-within:border-zinc-300 dark:focus-within:ring-zinc-300/50',
            'transition-[color,box-shadow] outline-none',
          ]">
            <ComboboxInput
              :display-value="(val: any) => (val as UserOption)?.name ?? ''"
              :placeholder="$t('Ieškoti pagal vardą...')"
              class="w-full bg-transparent text-sm outline-none placeholder:text-zinc-500 dark:placeholder:text-zinc-400"
              @input="onSearchInput"
            />
            <button
              v-if="selectedUser"
              type="button"
              class="shrink-0 rounded-sm opacity-50 hover:opacity-100"
              @click.prevent.stop="clearUser"
            >
              <X class="size-4" />
            </button>
            <ChevronsUpDown v-else class="size-4 shrink-0 opacity-50" />
          </ComboboxAnchor>
          <ComboboxList>
            <ComboboxViewport class="max-h-60">
              <div v-if="searchTerm.length < 2" class="px-2 py-4 text-center text-sm text-muted-foreground">
                {{ $t("Įveskite bent 2 simbolius") }}
              </div>
              <div v-else-if="isSearching" class="px-2 py-4 text-center text-sm text-muted-foreground">
                {{ $t("Ieškoma...") }}
              </div>
              <template v-else>
                <ComboboxEmpty>{{ $t("Nerasta") }}</ComboboxEmpty>
                <ComboboxItem
                  v-for="user in userOptions"
                  :key="user.id"
                  :value="user"
                  class="relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
                >
                  {{ user.name }}
                </ComboboxItem>
              </template>
            </ComboboxViewport>
          </ComboboxList>
        </Combobox>

        <div class="flex gap-2">
          <Button size="sm" :disabled="form.processing" @click="save">
            {{ $t("Išsaugoti") }}
          </Button>
          <Button variant="outline" size="sm" @click="cancelEditing">
            {{ $t("Atšaukti") }}
          </Button>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import { useDebounceFn } from "@vueuse/core";
import { ChevronsUpDown, X } from "lucide-vue-next";
import {
  ComboboxAnchor,
  ComboboxEmpty,
  ComboboxInput,
  ComboboxRoot as Combobox,
} from "reka-ui";
import { ComboboxItem, ComboboxList, ComboboxViewport } from "@/Components/ui/combobox";

import { useApi } from "@/Composables/useApi";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Label } from "@/Components/ui/label";

type UserOption = { id: string; name: string };

const props = defineProps<{
  planningProcess: App.Entities.PlanningProcess;
  canUpdate: boolean;
}>();

const editing = ref(false);
const searchTerm = ref("");
const selectedUser = ref<UserOption | null>(
  props.planningProcess.moderator
    ? { id: props.planningProcess.moderator_user_id!, name: props.planningProcess.moderator.name! }
    : null
);
const searchUrl = ref("");

const { data: searchedUsers, isFetching: isSearching, execute } = useApi<UserOption[]>(
  searchUrl,
  { immediate: false, showErrorToast: false }
);

const userOptions = computed<UserOption[]>(() => searchedUsers.value ?? []);

const debouncedSearch = useDebounceFn(() => {
  if (searchTerm.value.length >= 2) {
    const params = new URLSearchParams({ search: searchTerm.value });
    searchUrl.value = route("api.v1.admin.users.search") + "?" + params.toString();
    execute();
  }
}, 300);

const onSearchInput = (event: Event) => {
  searchTerm.value = (event.target as HTMLInputElement).value;
  if (searchTerm.value.length >= 2) {
    debouncedSearch();
  }
};

const handleUserSelect = (val: unknown) => {
  selectedUser.value = val as UserOption | null;
};

const clearUser = () => {
  selectedUser.value = null;
  searchTerm.value = "";
};

const form = useForm({ moderator_user_id: null as string | null });

const startEditing = () => {
  editing.value = true;
};

const cancelEditing = () => {
  editing.value = false;
  searchTerm.value = "";
  selectedUser.value = props.planningProcess.moderator
    ? { id: props.planningProcess.moderator_user_id!, name: props.planningProcess.moderator.name! }
    : null;
};

const save = () => {
  form.moderator_user_id = selectedUser.value?.id ?? null;
  form.patch(route("planningProcesses.assignModerator", props.planningProcess.id), {
    preserveScroll: true,
    onSuccess: () => {
      editing.value = false;
    },
  });
};
</script>
