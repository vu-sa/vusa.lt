<template>
  <div class="space-y-2">
    <Label v-if="label" :for="id">{{ label }}</Label>
    <Popover v-model:open="open">
      <ListboxRoot
        v-model="selectedUsers"
        highlight-on-hover
        multiple
      >
        <PopoverAnchor class="w-full">
          <TagsInput :id v-slot="{ modelValue: tags }" v-model="selectedNames" class="w-full">
            <TagsInputItem v-for="item in tags" :key="item" :value="item">
              <div class="flex items-center gap-1">
                <UserAvatar :user="getUserByName(item)" :size="16" />
                <TagsInputItemText />
              </div>
              <TagsInputItemDelete />
            </TagsInputItem>

            <ListboxFilter v-model="searchTerm" as-child>
              <TagsInputInput 
                :placeholder="selectedUsers.length === 0 ? placeholder : ''" 
                @keydown.enter.prevent 
                @keydown.down="open = true" 
              />
            </ListboxFilter>

            <PopoverTrigger as-child>
              <Button size="icon" variant="ghost" class="order-last ml-auto size-6">
                <ChevronDownIcon class="size-3.5" />
              </Button>
            </PopoverTrigger>
          </TagsInput>
        </PopoverAnchor>

        <PopoverContent
          class="p-1 w-[var(--reka-popper-anchor-width)]"
          @open-auto-focus.prevent
        >
          <ListboxContent class="max-h-[300px] scroll-py-1 overflow-x-hidden overflow-y-auto">
            <ListboxItem
              v-for="user in filteredUsers"
              :key="user.id"
              class="relative flex cursor-default items-center gap-2 rounded-sm px-2 py-1.5 text-sm
                outline-hidden select-none data-[highlighted]:bg-accent
                data-[highlighted]:text-accent-foreground data-[disabled]:pointer-events-none
                data-[disabled]:opacity-50"
              :value="user"
              @select="() => { searchTerm = '' }"
            >
              <UserAvatar :user :size="24" />
              <span>{{ user.name }}</span>
              <ListboxItemIndicator class="ml-auto">
                <CheckIcon class="size-4" />
              </ListboxItemIndicator>
            </ListboxItem>
            <div v-if="filteredUsers.length === 0" class="p-2 text-center text-sm text-muted-foreground">
              {{ emptyText }}
            </div>
          </ListboxContent>
        </PopoverContent>
      </ListboxRoot>
    </Popover>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed, ref, watch } from "vue";
import { useFilter, ListboxRoot, ListboxContent, ListboxFilter, ListboxItem, ListboxItemIndicator } from "reka-ui";
import { CheckIcon, ChevronDownIcon } from "lucide-vue-next";

import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import { Popover, PopoverAnchor, PopoverContent, PopoverTrigger } from "@/Components/ui/popover";
import { TagsInput, TagsInputInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText } from "@/Components/ui/tags-input";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";

const props = withDefaults(defineProps<{
  users: App.Entities.User[];
  modelValue?: App.Entities.User[];
  label?: string;
  placeholder?: string;
  emptyText?: string;
  id?: string;
}>(), {
  modelValue: () => [],
  label: undefined,
  placeholder: 'Pasirinkite...',
  emptyText: 'No users found.',
  id: 'user-multi-select',
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: App.Entities.User[]): void;
}>();

const open = ref(false);
const searchTerm = ref('');
const selectedUsers = ref<App.Entities.User[]>([...props.modelValue]);

// Computed names for TagsInput display
const selectedNames = computed({
  get: () => selectedUsers.value.map(u => u.name),
  set: (names: string[]) => {
    // When a tag is removed, update the list
    selectedUsers.value = selectedUsers.value.filter(u => names.includes(u.name));
  }
});

// Filter users based on search term
const { contains } = useFilter({ sensitivity: 'base' });
const filteredUsers = computed(() => {
  if (!searchTerm.value) return props.users;
  return props.users.filter(user => contains(user.name, searchTerm.value));
});

// Helper to get user by name for avatar display
const getUserByName = (name: string): App.Entities.User | undefined => {
  return selectedUsers.value.find(u => u.name === name);
};

// Sync internal state with prop changes
watch(() => props.modelValue, (newValue) => {
  selectedUsers.value = [...newValue];
}, { deep: true });

// Emit changes to parent
watch(selectedUsers, (users) => {
  emit('update:modelValue', users);
}, { deep: true });

// Expose reset method
const reset = () => {
  selectedUsers.value = [];
  searchTerm.value = '';
  open.value = false;
};

defineExpose({ reset });
</script>
