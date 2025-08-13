<template>
  <Card class="hover:shadow-md transition-shadow">
    <CardContent class="p-4">
      <div class="flex items-start gap-3">
        <!-- Avatar -->
        <div class="flex-shrink-0">
          <UserPopover :user="member" :size="40" />
        </div>
        
        <!-- Member Info -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between">
            <h3 class="font-medium text-zinc-900 dark:text-zinc-100 truncate">
              {{ member.name }}
            </h3>
            <div class="flex items-center gap-1">
              <Badge v-if="isExceeded" variant="destructive" class="text-xs">
                {{ $t('Viršija limitą') }}
              </Badge>
              <Badge v-else-if="isActive" variant="secondary" class="text-xs">
                {{ $t('Aktyvus') }}
              </Badge>
            </div>
          </div>
          
          <!-- Duties -->
          <div v-if="memberDuties.length > 0" class="mt-1">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
              {{ memberDuties.map(d => d.name).join(', ') }}
            </p>
          </div>
          
          <!-- Tenure -->
          <div class="mt-2 flex items-center gap-4 text-xs text-zinc-500 dark:text-zinc-400">
            <span v-if="startDate">
              {{ $t('Nuo') }}: {{ formatDate(startDate) }}
            </span>
            <span v-if="tenure">
              {{ tenure }}
            </span>
          </div>
          
          <!-- Contact Info -->
          <div v-if="showContact && (member.email || member.phone)" class="mt-2 flex items-center gap-3 text-xs">
            <a 
              v-if="member.email" 
              :href="`mailto:${member.email}`"
              class="flex items-center gap-1 text-blue-600 hover:text-blue-800 dark:text-blue-400"
            >
              <Mail class="h-3 w-3" />
              {{ member.email }}
            </a>
            <a 
              v-if="member.phone" 
              :href="`tel:${member.phone}`"
              class="flex items-center gap-1 text-blue-600 hover:text-blue-800 dark:text-blue-400"
            >
              <Phone class="h-3 w-3" />
              {{ member.phone }}
            </a>
          </div>
        </div>
        
        <!-- Actions -->
        <div v-if="showActions" class="flex flex-col gap-1">
          <Button
            variant="ghost"
            size="sm"
            @click="$emit('view-profile', member)"
            class="h-8 w-8 p-0"
          >
            <User class="h-4 w-4" />
          </Button>
          <Button
            v-if="canEdit"
            variant="ghost"
            size="sm"
            @click="$emit('edit-member', member)"
            class="h-8 w-8 p-0"
          >
            <Edit class="h-4 w-4" />
          </Button>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Mail, Phone, User, Edit } from 'lucide-vue-next';
import UserPopover from '@/Components/Avatars/UserPopover.vue';

const props = defineProps<{
  member: App.Entities.User;
  institution?: App.Entities.Institution;
  maxPositions?: number;
  showContact?: boolean;
  showActions?: boolean;
  canEdit?: boolean;
}>();

const emit = defineEmits<{
  'view-profile': [member: App.Entities.User];
  'edit-member': [member: App.Entities.User];
}>();

const memberDuties = computed(() => {
  return props.member.current_duties || [];
});

const startDate = computed(() => {
  // Get the earliest start date from current duties
  if (!memberDuties.value.length) return null;
  const dates = memberDuties.value
    .map(duty => duty.pivot?.start_date)
    .filter(Boolean)
    .sort();
  return dates[0] || null;
});

const isActive = computed(() => {
  return memberDuties.value.some(duty => 
    duty.pivot?.end_date === null || new Date(duty.pivot.end_date) >= new Date()
  );
});

const isExceeded = computed(() => {
  if (!props.maxPositions) return false;
  const currentCount = props.institution?.current_users?.length || 0;
  return currentCount > props.maxPositions;
});

const tenure = computed(() => {
  if (!startDate.value) return null;
  
  const start = new Date(startDate.value);
  const now = new Date();
  const diffInMonths = (now.getFullYear() - start.getFullYear()) * 12 + 
                       (now.getMonth() - start.getMonth());
  
  if (diffInMonths < 1) return $t('Naujas narys');
  if (diffInMonths < 12) return diffInMonths + ' ' + $t('mėn.');
  
  const years = Math.floor(diffInMonths / 12);
  const months = diffInMonths % 12;
  
  let result = years + ' ' + (years === 1 ? $t('metai') : $t('metai'));
  if (months > 0) {
    result += ' ' + months + ' ' + $t('mėn.');
  }
  return result;
});

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};
</script>