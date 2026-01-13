<template>
  <Dialog :open="open" @update:open="$emit('close')">
    <DialogContent class="sm:max-w-[500px]">
      <DialogHeader>
        <DialogTitle>{{ $t("tasks.create_new") }}</DialogTitle>
        <DialogDescription>{{ $t("tasks.create_description") }}</DialogDescription>
      </DialogHeader>

      <div class="grid gap-4 py-4">
        <div class="grid gap-2">
          <Label :for="'task-name'">{{ $t("forms.fields.title") }}</Label>
          <Input 
            id="task-name" 
            v-model="taskForm.name" 
            :placeholder="$t('tasks.name_placeholder')"
            :class="{ 'border-destructive': errors.name }"
          />
          <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
        </div>

        <div class="grid gap-2">
          <Label :for="'task-date'">{{ $t("forms.fields.due_date") }}</Label>
          <DatePicker 
            v-model="taskForm.due_date" 
            :placeholder="$t('tasks.date_placeholder')" 
            :class="{ 'border-destructive': errors.due_date }"
            @change="handleDateChange"
          />
          <p v-if="errors.due_date" class="text-sm text-destructive">{{ errors.due_date }}</p>
        </div>

        <div class="grid gap-2">
          <Label :for="'task-users'">{{ $t("forms.fields.responsible_people") }}</Label>
          <MultiSelect 
            v-model="selectedUsers" 
            :options="props.users ?? []"
            label-field="name"
            value-field="id"
            :placeholder="$t('forms.fields.select_users')"
            :empty-text="$t('No users found.')"
            :class="{ 'border-destructive': errors.users }"
          >
            <template #selected-item="{ item: user }">
              <div class="flex items-center gap-1">
                <UserAvatar :user="(user as App.Entities.User)" :size="16" />
                <span class="max-w-[120px] truncate">{{ (user as App.Entities.User).name }}</span>
              </div>
            </template>
            <template #option="{ item: user }">
              <UserAvatar :user="(user as App.Entities.User)" :size="24" class="shrink-0" />
              <span class="min-w-0 truncate">{{ (user as App.Entities.User).name }}</span>
            </template>
          </MultiSelect>
          <p v-if="errors.users" class="text-sm text-destructive">{{ errors.users }}</p>
        </div>

        <div class="flex items-center space-x-2" v-if="taskForm.user_ids.length > 1">
          <Checkbox id="separate-tasks" v-model:checked="taskForm.separate_tasks" />
          <Label :for="'separate-tasks'">{{ $t("tasks.create_separate_tasks") }}</Label>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="$emit('close')">{{ $t("Cancel") }}</Button>
        <Button type="submit" :disabled="isSubmitting" @click="createTask">
          {{ isSubmitting ? $t("forms.buttons.saving") : $t("forms.buttons.create") }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { ref, reactive, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import { DatePicker } from "@/Components/ui/date-picker";
import { Checkbox } from "@/Components/ui/checkbox";
import { Button } from "@/Components/ui/button";
import { MultiSelect } from "@/Components/ui/multi-select";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";
import { CheckCircleIcon } from "lucide-vue-next";

// Component props
const props = defineProps<{
  open: boolean;
  taskable?: {
    id: string | number;
    type: string;
  };
  users?: App.Entities.User[];
}>();

// Component events
const emit = defineEmits<{
  'close': [];
  'task-created': [];
}>();

// Form state
const isSubmitting = ref(false);
const selectedUsers = ref<App.Entities.User[]>([]);
const taskForm = reactive({
  name: "",
  due_date: undefined as Date | undefined, // Changed from null to undefined for DatePicker compatibility
  user_ids: [] as string[],
  separate_tasks: false
});

// Form errors
const errors = reactive({
  name: '',
  due_date: '',
  users: ''
});

// Sync selected users to form user_ids
watch(selectedUsers, (users) => {
  taskForm.user_ids = users.map(u => u.id);
}, { deep: true });

/**
 * Validate form fields
 */
const validateForm = (): boolean => {
  let isValid = true;
  
  // Reset errors
  errors.name = '';
  errors.due_date = '';
  errors.users = '';
  
  // Validate name
  if (!taskForm.name.trim()) {
    errors.name = $t("validation.required", { attribute: $t("forms.fields.title").toLowerCase() });
    isValid = false;
  }
  
  // Validate due date
  if (!taskForm.due_date) {
    errors.due_date = $t("validation.required", { attribute: $t("forms.fields.due_date").toLowerCase() });
    isValid = false;
  }
  
  // Validate users
  if (taskForm.user_ids.length === 0) {
    errors.users = $t("validation.required", { attribute: $t("forms.fields.responsible_people").toLowerCase() });
    isValid = false;
  }
  
  return isValid;
};

/**
 * Submit the task creation form
 */
const createTask = () => {
  if (!props.taskable || !validateForm()) return;
  
  isSubmitting.value = true;
  
  router.post(route("tasks.store"), {
    name: taskForm.name,
    due_date: taskForm.due_date,
    responsible_people: taskForm.user_ids,
    separate_tasks: taskForm.separate_tasks,
    taskable_type: props.taskable.type,
    taskable_id: props.taskable.id
  }, {
    onSuccess: () => {
      resetForm();
      isSubmitting.value = false;
      emit('close');
      emit('task-created');
      
      // Show success toast
      toast.success($t("tasks.creation_success"), {
        description: taskForm.separate_tasks && taskForm.user_ids.length > 1 
          ? $t("tasks.multiple_tasks_created") 
          : $t("tasks.task_created"),
        icon: <CheckCircleIcon class="h-4 w-4" />
      });
    },
    onError: (formErrors) => {
      isSubmitting.value = false;
      
      // Map backend errors to form fields
      if (formErrors.name) {
        errors.name = formErrors.name;
      }
      if (formErrors.due_date) {
        errors.due_date = formErrors.due_date;
      }
      if (formErrors.responsible_people) {
        errors.users = formErrors.responsible_people;
      }
      
      // Show error toast for other errors
      if (!formErrors.name && !formErrors.due_date && !formErrors.responsible_people) {
        toast.error($t("tasks.creation_error"), {
          description: $t("tasks.try_again")
        });
      }
    },
    preserveScroll: true,
    preserveState: true,
  });
};

/**
 * Reset form to initial state
 */
const resetForm = () => {
  taskForm.name = "";
  taskForm.due_date = undefined; // Changed from null to undefined for DatePicker compatibility
  taskForm.user_ids = [];
  taskForm.separate_tasks = false;
  selectedUsers.value = [];
  
  // Reset errors
  errors.name = '';
  errors.due_date = '';
  errors.users = '';
};

/**
 * Handle date change from DatePicker
 * Clears any validation errors related to the date field
 */
const handleDateChange = (date: Date) => {
  if (date) {
    errors.due_date = '';
  }
};
</script>