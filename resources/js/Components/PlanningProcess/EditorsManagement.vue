<template>
  <Card>
    <CardHeader>
      <div class="flex items-center gap-3">
        <div class="shrink-0 h-9 w-9 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
          <UsersIcon class="h-4.5 w-4.5 text-primary" />
        </div>
        <div class="flex-1 min-w-0">
          <CardTitle class="text-base">{{ $t("Redaktoriai") }}</CardTitle>
          <CardDescription>{{ $t("Redaktoriai turi pilną prieigą redaguoti planavimo procesą") }}</CardDescription>
        </div>
      </div>
    </CardHeader>
    <CardContent>
      <!-- Editor list -->
      <div v-if="editors.length > 0" class="flex flex-col gap-2 mb-4">
        <div
          v-for="editor in editors"
          :key="editor.id"
          class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-800 px-3 py-2"
        >
          <div class="shrink-0 h-8 w-8 rounded-full bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
            <UserIcon class="h-4 w-4 text-primary" />
          </div>
          <span class="flex-1 text-sm font-medium truncate">{{ editor.name }}</span>
          <Button
            v-if="canManageEditors"
            variant="ghost"
            size="icon"
            class="shrink-0 h-7 w-7 text-muted-foreground hover:text-destructive"
            :disabled="removeForm.processing"
            @click="removeEditor(editor.id)"
          >
            <X class="h-3.5 w-3.5" />
          </Button>
        </div>
      </div>
      <p v-else class="text-sm text-muted-foreground italic mb-4">{{ $t("Nėra priskirtų redaktorių") }}</p>

      <!-- Add editor -->
      <div v-if="canManageEditors" class="flex flex-col gap-1.5">
        <Label class="text-xs font-medium">{{ $t("Pridėti redaktorių") }}</Label>
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
            <ChevronsUpDown class="size-4 shrink-0 opacity-50" />
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
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import { useDebounceFn } from "@vueuse/core";
import { ChevronsUpDown, X, User as UserIcon, Users as UsersIcon } from "lucide-vue-next";
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
  editors: Array<{ id: string; name: string }>;
  canManageEditors: boolean;
  isModerator: boolean;
}>();

const searchTerm = ref("");
const selectedUser = ref<UserOption | null>(null);
const searchUrl = ref("");

const { data: searchedUsers, isFetching: isSearching, execute } = useApi<UserOption[]>(
  searchUrl,
  { immediate: false, showErrorToast: false }
);

const userOptions = computed<UserOption[]>(() => {
  const results = searchedUsers.value ?? [];
  // Filter out users already added as editors or the moderator
  const editorIds = new Set(props.editors.map((e) => e.id));
  if (props.planningProcess.moderator_user_id) {
    editorIds.add(props.planningProcess.moderator_user_id);
  }
  return results.filter((u) => !editorIds.has(u.id));
});

const debouncedSearch = useDebounceFn(() => {
  if (searchTerm.value.length >= 2) {
    const params = new URLSearchParams({ search: searchTerm.value, permission: "planningProcesses.update.padalinys" });
    // When the current user is the moderator, restrict search to their padalinys
    if (props.isModerator && props.planningProcess.tenant_id) {
      params.set("tenant_id", String(props.planningProcess.tenant_id));
    }
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
  const user = val as UserOption | null;
  if (user) {
    addEditor(user.id);
    selectedUser.value = null;
    searchTerm.value = "";
  }
};

const addForm = useForm({ user_id: "" });
const removeForm = useForm({ user_id: "" });

const addEditor = (userId: string) => {
  addForm.user_id = userId;
  addForm.post(route("planningProcesses.addEditor", props.planningProcess.id), {
    preserveScroll: true,
  });
};

const removeEditor = (userId: string) => {
  removeForm.user_id = userId;
  removeForm.delete(route("planningProcesses.removeEditor", props.planningProcess.id), {
    preserveScroll: true,
  });
};
</script>
