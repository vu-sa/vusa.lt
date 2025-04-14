<template>
  <div>
    <Combobox v-model="selectedUsers" multiple>
      <div class="relative mt-1">
        <div
          class="flex min-h-10 w-full items-center gap-1 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
          :class="{ 'border-muted-foreground': open }"
        >
          <div class="flex flex-wrap gap-1">
            <Badge
              v-for="user in selectedUsers"
              :key="user.id"
              variant="secondary"
              class="flex items-center gap-1"
            >
              <UserAvatar :user="user" size="16" />
              {{ user.name }}
              <button
                class="ml-1 rounded-full outline-none ring-offset-background hover:bg-muted hover:text-muted-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2"
                @click.prevent="removeUser(user)"
              >
                <XIcon class="h-3 w-3" />
                <span class="sr-only">{{ $t('Remove') }} {{ user.name }}</span>
              </button>
            </Badge>
          </div>
          <ComboboxInput
            class="flex h-8 w-full flex-1 bg-background p-1 text-sm placeholder:text-muted-foreground focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
            :placeholder="selectedUsers.length === 0 ? $t('forms.fields.select_users') : ''"
            @change="onSearch"
          />
          <ComboboxTrigger asChild>
            <Button
              variant="ghost"
              role="combobox"
              :aria-expanded="open"
              class="h-8 px-2 hover:bg-muted"
              size="icon"
            >
              <ChevronDownIcon class="h-4 w-4 shrink-0 opacity-50" />
            </Button>
          </ComboboxTrigger>
        </div>
        <ComboboxAnchor>
          <ComboboxViewport
            class="relative z-50 min-w-[200px] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2"
          >
            <ComboboxList>
              <ComboboxEmpty v-if="filteredUsers.length === 0" class="flex flex-col items-center justify-center p-4 text-center">
                <p>{{ $t('No users found.') }}</p>
              </ComboboxEmpty>
              <ComboboxItem
                v-for="user in filteredUsers"
                :key="user.id"
                :value="user"
                class="relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none aria-selected:bg-accent aria-selected:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
              >
                <div class="flex items-center gap-2">
                  <UserAvatar :user="user" size="24" />
                  <div>
                    <p>{{ user.name }}</p>
                  </div>
                </div>
                <ComboboxItemIndicator class="absolute right-2 flex h-3.5 w-3.5 items-center justify-center">
                  <CheckIcon class="h-4 w-4" />
                </ComboboxItemIndicator>
              </ComboboxItem>
            </ComboboxList>
          </ComboboxViewport>
        </ComboboxAnchor>
      </div>
    </Combobox>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { usePage } from '@inertiajs/vue3';
import { 
  Combobox,
  ComboboxAnchor,
  ComboboxInput,
  ComboboxItem,
  ComboboxItemIndicator,
  ComboboxList,
  ComboboxTrigger,
  ComboboxViewport,
  ComboboxEmpty
} from '@/Components/ui/combobox';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { XIcon, ChevronDownIcon, CheckIcon } from 'lucide-vue-next';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';

const props = defineProps<{
  modelValue: string[]
}>();

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const searchQuery = ref('');
const selectedUsers = ref<App.Entities.User[]>([]);

// Get all users from the current tenant
const allUsers = computed(() => {
  // TODO: doesn't return the users that way, need to think of a solution
  const users = usePage().props.auth?.user?.tenants?.[0]?.users || [];
  return users;
});

// Filter users based on search query
const filteredUsers = computed(() => {
  if (!searchQuery.value.trim()) {
    return allUsers.value;
  }
  
  return allUsers.value.filter((user) => 
    user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    user.email.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

// When the user types in the search input
const onSearch = (e: Event) => {
  const target = e.target as HTMLInputElement;
  searchQuery.value = target.value;
};

// Remove a user from selection
const removeUser = (user: App.Entities.User) => {
  selectedUsers.value = selectedUsers.value.filter(u => u.id !== user.id);
  updateModelValue();
};

// Update the v-model value when selectedUsers change
const updateModelValue = () => {
  emit('update:modelValue', selectedUsers.value.map(user => user.id));
};

// Watch for changes in selectedUsers and emit update
watch(selectedUsers, () => {
  updateModelValue();
}, { deep: true });

// When modelValue changes from outside, update selectedUsers
watch(() => props.modelValue, (newVal) => {
  if (!newVal || newVal.length === 0) {
    selectedUsers.value = [];
    return;
  }
  
  // Find the user objects that match the IDs in modelValue
  selectedUsers.value = allUsers.value.filter(user => 
    newVal.includes(user.id)
  );
}, { immediate: true });
</script>