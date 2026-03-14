<template>
  <Card>
    <CardHeader>
      <div class="flex items-center gap-3">
        <div class="shrink-0 h-9 w-9 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
          <UserIcon class="h-4.5 w-4.5 text-primary" />
        </div>
        <div class="flex-1 min-w-0">
          <CardTitle class="text-base">{{ $t("Moderatoriaus priskyrimas") }}</CardTitle>
          <CardDescription>{{ $t("Paskirtas moderatorius koordinuoja planavimo procesą") }}</CardDescription>
        </div>
      </div>
    </CardHeader>
    <CardContent>
      <div v-if="!editing" class="flex items-center gap-4">
        <div class="flex items-center gap-3 flex-1 min-w-0">
          <div
            class="shrink-0 h-10 w-10 rounded-full flex items-center justify-center text-sm font-semibold"
            :class="planningProcess.moderator
              ? 'bg-primary/10 text-primary dark:bg-primary/20'
              : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500'"
          >
            <UserIcon class="h-5 w-5" />
          </div>
          <div class="min-w-0">
            <p v-if="planningProcess.moderator" class="text-sm font-medium truncate">
              {{ planningProcess.moderator.name }}
            </p>
            <p v-else class="text-sm text-muted-foreground italic">{{ $t("Nepriskirtas") }}</p>
          </div>
        </div>
        <Button v-if="canAssignModerator" variant="outline" size="sm" class="gap-1.5 shrink-0" @click="startEditing">
          <PencilIcon class="h-3.5 w-3.5" />
          {{ $t("Keisti") }}
        </Button>
      </div>

      <div v-else class="flex flex-col gap-4">
        <div class="flex flex-col gap-1.5">
          <Label class="text-xs font-medium">{{ $t("Ieškoti moderatoriaus") }}</Label>
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
        </div>

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
import { ChevronsUpDown, X, User as UserIcon, Pencil as PencilIcon } from "lucide-vue-next";
import {
  ComboboxAnchor,
  ComboboxEmpty,
  ComboboxInput,
  ComboboxRoot as Combobox,
} from "reka-ui";
import { ComboboxItem, ComboboxList, ComboboxViewport } from "@/Components/ui/combobox";

import { useApi } from "@/Composables/useApi";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Label } from "@/Components/ui/label";

type UserOption = { id: string; name: string };

const props = defineProps<{
  planningProcess: App.Entities.PlanningProcess;
  canAssignModerator: boolean;
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
    const params = new URLSearchParams({ search: searchTerm.value, permission: "planningProcesses.update.padalinys" });
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
  form.patch(route("planavimai.assignModerator", props.planningProcess.id), {
    preserveScroll: true,
    onSuccess: () => {
      editing.value = false;
    },
  });
};
</script>
