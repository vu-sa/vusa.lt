<template>
  <Card class="group transition-all hover:shadow-md">
    <CardContent size="compact" class="py-3 px-4">
      <div class="flex items-center gap-4">
        <!-- Status Indicator Dot -->
        <div 
          class="h-2.5 w-2.5 rounded-full flex-shrink-0"
          :class="isActive ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600'"
        ></div>

        <!-- Avatar with link to user -->
        <Link 
          :href="route('users.edit', member.id)" 
          class="flex-shrink-0 rounded-full ring-2 ring-transparent hover:ring-blue-500 transition-all"
        >
          <UserPopover :user="member" :size="44" />
        </Link>

        <!-- Main Info -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-1">
            <Link 
              :href="route('users.edit', member.id)"
              class="font-medium text-zinc-900 dark:text-zinc-100 hover:text-blue-600 dark:hover:text-blue-400 truncate transition-colors"
            >
              {{ member.name }}
            </Link>
            <Badge 
              :variant="isActive ? 'default' : 'secondary'"
              class="text-xs flex-shrink-0"
            >
              {{ isActive ? $t('Aktyvus') : $t('Baigė') }}
            </Badge>
          </div>
          
          <!-- Tenure Info Row -->
          <div class="flex items-center gap-4 text-sm text-zinc-600 dark:text-zinc-400">
            <div class="flex items-center gap-1.5">
              <Calendar class="h-3.5 w-3.5 flex-shrink-0" />
              <span class="truncate">
                {{ formatDateRange(member.pivot?.start_date, member.pivot?.end_date) }}
              </span>
            </div>
            <div class="flex items-center gap-1.5">
              <Clock class="h-3.5 w-3.5 flex-shrink-0" />
              <span>{{ getTenure(member) }}</span>
            </div>
          </div>

          <!-- Contact Info (expandable) -->
          <Collapsible v-if="showContact && (member.email || member.phone)" class="mt-2">
            <CollapsibleTrigger class="flex items-center gap-1 text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
              <Mail class="h-3 w-3" />
              <span>{{ $t('Kontaktai') }}</span>
              <ChevronDown class="h-3 w-3 transition-transform group-data-[state=open]:rotate-180" />
            </CollapsibleTrigger>
            <CollapsibleContent class="mt-2">
              <div class="flex flex-wrap items-center gap-3 text-xs">
                <a 
                  v-if="member.email" 
                  :href="`mailto:${member.email}`"
                  class="flex items-center gap-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                >
                  <Mail class="h-3 w-3" />
                  {{ member.email }}
                </a>
                <a 
                  v-if="member.phone" 
                  :href="`tel:${member.phone}`"
                  class="flex items-center gap-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                >
                  <Phone class="h-3 w-3" />
                  {{ member.phone }}
                </a>
              </div>
            </CollapsibleContent>
          </Collapsible>
        </div>

        <!-- Actions -->
        <div v-if="canEdit" class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger as-child>
                <Button
                  variant="ghost"
                  size="sm"
                  class="h-8 w-8 p-0"
                  @click="$emit('edit-period', member)"
                >
                  <Pencil class="h-4 w-4" />
                  <span class="sr-only">{{ $t('Redaguoti laikotarpį') }}</span>
                </Button>
              </TooltipTrigger>
              <TooltipContent>
                {{ $t('Redaguoti laikotarpį') }}
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Link } from '@inertiajs/vue3';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { Calendar, ChevronDown, Clock, Mail, Pencil, Phone } from 'lucide-vue-next';
import UserPopover from '@/Components/Avatars/UserPopover.vue';

const props = defineProps<{
  member: App.Entities.User;
  isActive: boolean;
  showContact?: boolean;
  canEdit?: boolean;
}>();

const emit = defineEmits<{
  'edit-period': [member: App.Entities.User];
}>();

const formatDateRange = (startDate?: string, endDate?: string | null) => {
  if (!startDate) return $t('Nežinoma');
  
  const start = new Date(startDate).toLocaleDateString('lt-LT', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  });
  
  if (!endDate) {
    return `${start} – ${$t('dabar')}`;
  }
  
  const end = new Date(endDate).toLocaleDateString('lt-LT', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  });
  
  return `${start} – ${end}`;
};

const getTenure = (member: App.Entities.User) => {
  const startDate = member.pivot?.start_date ? new Date(member.pivot.start_date) : null;
  const endDate = member.pivot?.end_date ? new Date(member.pivot.end_date) : new Date();
  
  if (!startDate) return $t('Nežinoma');
  
  const diffInMonths = (endDate.getFullYear() - startDate.getFullYear()) * 12 + 
                       (endDate.getMonth() - startDate.getMonth());
  
  if (diffInMonths < 1) {
    const diffInDays = Math.floor((endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24));
    if (diffInDays < 1) return `< 1 ${$t('diena')}`;
    return `${diffInDays} ${diffInDays === 1 ? $t('diena') : $t('d.')}`;
  }
  
  if (diffInMonths < 12) {
    return `${diffInMonths} ${$t('mėn.')}`;
  }
  
  const years = Math.floor(diffInMonths / 12);
  const months = diffInMonths % 12;
  
  let result = `${years} ${years === 1 ? $t('m.') : $t('m.')}`;
  if (months > 0) {
    result += ` ${months} ${$t('mėn.')}`;
  }
  return result;
};
</script>
