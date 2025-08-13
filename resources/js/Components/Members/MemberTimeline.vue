<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
          {{ $t('Istorija') }}
        </h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
          {{ members.length }} {{ $t('nariai per visą laiką') }}
        </p>
      </div>
    </div>

    <!-- Timeline -->
    <div v-if="members.length > 0" class="space-y-4">
      <div 
        v-for="(member, index) in sortedMembers" 
        :key="`${member.id}-${index}`"
        class="flex gap-4"
      >
        <!-- Timeline Line -->
        <div class="flex flex-col items-center">
          <div class="w-3 h-3 rounded-full bg-blue-500 border-2 border-white dark:border-zinc-900 shadow-sm"></div>
          <div 
            v-if="index < sortedMembers.length - 1" 
            class="w-0.5 h-16 bg-zinc-200 dark:bg-zinc-700 mt-2"
          ></div>
        </div>

        <!-- Member Info -->
        <div class="flex-1 pb-6">
          <Card class="hover:shadow-sm transition-shadow">
            <CardContent class="p-4">
              <div class="flex items-start gap-3">
                <!-- Avatar -->
                <UserPopover :user="member" :size="40" />
                
                <!-- Details -->
                <div class="flex-1">
                  <div class="flex items-center justify-between mb-1">
                    <h4 class="font-medium text-zinc-900 dark:text-zinc-100">
                      {{ member.name }}
                    </h4>
                    <Badge 
                      :variant="member.pivot?.end_date ? 'secondary' : 'default'"
                      class="text-xs"
                    >
                      {{ member.pivot?.end_date ? $t('Baigė') : $t('Aktyvus') }}
                    </Badge>
                  </div>
                  
                  <!-- Tenure Information -->
                  <div class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                    <div class="flex items-center gap-2">
                      <Calendar class="h-3 w-3" />
                      <span>
                        {{ $t('Nuo') }}: {{ formatDate(member.pivot?.start_date) }}
                      </span>
                      <span v-if="member.pivot?.end_date">
                        - {{ formatDate(member.pivot.end_date) }}
                      </span>
                    </div>
                    
                    <div class="flex items-center gap-2">
                      <Clock class="h-3 w-3" />
                      <span>{{ getTenure(member) }}</span>
                    </div>
                  </div>

                  <!-- Contact Info -->
                  <div v-if="showContact && (member.email || member.phone)" 
                       class="mt-2 flex items-center gap-3 text-xs">
                    <a v-if="member.email" 
                       :href="`mailto:${member.email}`"
                       class="flex items-center gap-1 text-blue-600 hover:text-blue-800 dark:text-blue-400">
                      <Mail class="h-3 w-3" />
                      {{ member.email }}
                    </a>
                    <a v-if="member.phone" 
                       :href="`tel:${member.phone}`"
                       class="flex items-center gap-1 text-blue-600 hover:text-blue-800 dark:text-blue-400">
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
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <div class="mx-auto h-16 w-16 bg-zinc-100 dark:bg-zinc-800 rounded-full flex items-center justify-center mb-4">
        <History class="h-8 w-8 text-zinc-400" />
      </div>
      <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">
        {{ $t('Nėra istorinių duomenų') }}
      </h3>
      <p class="text-zinc-500 dark:text-zinc-400">
        {{ $t('Šiose pareigose dar niekas nėra ėjęs anksčiau.') }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Calendar, Clock, Mail, Phone, User, History } from 'lucide-vue-next';
import UserPopover from '@/Components/Avatars/UserPopover.vue';

const props = defineProps<{
  members: App.Entities.User[];
  showContact?: boolean;
  showActions?: boolean;
}>();

const emit = defineEmits<{
  'view-profile': [member: App.Entities.User];
}>();

const sortedMembers = computed(() => {
  return [...props.members].sort((a, b) => {
    // Sort by end_date (most recent first), then by start_date
    const aEndDate = a.pivot?.end_date;
    const bEndDate = b.pivot?.end_date;
    
    // Active members (no end_date) should come first
    if (!aEndDate && bEndDate) return -1;
    if (aEndDate && !bEndDate) return 1;
    if (!aEndDate && !bEndDate) {
      // Both active, sort by start_date (most recent first)
      return new Date(b.pivot?.start_date || 0).getTime() - new Date(a.pivot?.start_date || 0).getTime();
    }
    
    // Both have end_dates, sort by end_date (most recent first)
    return new Date(bEndDate || 0).getTime() - new Date(aEndDate || 0).getTime();
  });
});

const formatDate = (dateString?: string) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString();
};

const getTenure = (member: App.Entities.User) => {
  const startDate = member.pivot?.start_date ? new Date(member.pivot.start_date) : null;
  const endDate = member.pivot?.end_date ? new Date(member.pivot.end_date) : new Date();
  
  if (!startDate) return $t('Nežinoma');
  
  const diffInMonths = (endDate.getFullYear() - startDate.getFullYear()) * 12 + 
                       (endDate.getMonth() - startDate.getMonth());
  
  if (diffInMonths < 1) {
    const diffInDays = Math.floor((endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24));
    if (diffInDays < 1) return $t('< 1 diena');
    return diffInDays + ' ' + (diffInDays === 1 ? $t('diena') : $t('dienos'));
  }
  
  if (diffInMonths < 12) {
    return diffInMonths + ' ' + (diffInMonths === 1 ? $t('mėnuo') : $t('mėnesiai'));
  }
  
  const years = Math.floor(diffInMonths / 12);
  const months = diffInMonths % 12;
  
  let result = years + ' ' + (years === 1 ? $t('metai') : $t('metai'));
  if (months > 0) {
    result += ' ' + months + ' ' + $t('mėn.');
  }
  return result;
};
</script>