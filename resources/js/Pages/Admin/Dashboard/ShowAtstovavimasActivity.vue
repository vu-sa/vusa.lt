<template>
  <AdminContentPage :title="$t('Changes History') + ' (' + date + ' | ' + providedTenant.shortname + ')'">
    <div class="space-y-6">
      <!-- Filters Section -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <Label>{{ $t('Date') }}</Label>
          <DatePicker
            v-model="form.date"
            class="w-full"
            @update:model-value="handleDateUpdate"
          />
        </div>
        <div>
          <Label>{{ $t('Unit') }}</Label>
          <Select
            v-model="form.tenant_id"
            :placeholder="$t('Select a unit')"
            @update:model-value="handleTenantUpdate"
          >
            <SelectTrigger class="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                <SelectItem
                  v-for="tenant in tenants"
                  :key="tenant.id"
                  :value="tenant.id"
                >
                  {{ tenant.shortname }}
                </SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>
        </div>
      </div>
      
      <!-- Empty State -->
      <EmptyState 
        v-if="filteredMeetings.length === 0"
        :title="$t('No changes on this date')"
        :description="$t('Try selecting a different date or unit')"
      >
        <template #icon>
          <CalendarX class="h-10 w-10 text-muted-foreground" />
        </template>
        <div class="text-muted-foreground mt-4 text-sm">
          <ul class="list-disc list-inside space-y-1">
            <li>
              {{ providedTenant ? $t('Selected unit') + ': ' + providedTenant.shortname : '' }}
            </li>
            <li>
              {{ date ? $t('Selected date') + ': ' + date : '' }}
            </li>
          </ul>
        </div>
      </EmptyState>
      
      <!-- Meetings with Activities -->
      <div class="space-y-4">
        <Card v-for="meeting in filteredMeetings" :key="meeting.id" class="overflow-visible">
          <CardHeader class="border-b pb-3">
            <CardTitle>
              <SmartLink :href="route('institutions.show', meeting.institutions?.[0]?.id)" class="hover:underline">
                {{ meeting.institutions?.[0]?.name }}
              </SmartLink>
            </CardTitle>
          </CardHeader>
          
          <CardContent class="pt-4 space-y-4">
            <!-- Meeting Title -->
            <div class="flex items-center gap-2">
              <Icons.MEETING class="h-5 w-5 text-primary" />
              <h3 class="text-lg font-medium">
                <SmartLink :href="route('meetings.show', meeting.id)" class="hover:underline">
                  {{ meeting.title }}
                </SmartLink>
              </h3>
            </div>
            
            <!-- Meeting Activities -->
            <div v-if="meeting.activities && meeting.activities.length > 0" class="space-y-3">
              <div 
                v-for="activity in meeting.activities" 
                :key="activity.id"
                class="bg-muted/50 rounded-md p-4"
              >
                <ActivityTimeline :activities="[activity]" />
              </div>
            </div>
            
            <!-- Agenda Item Activities -->
            <div v-if="meeting.changedAgendaItems?.length > 0" class="border rounded-md mt-4">
              <Collapsible v-model:open="openItems[meeting.id]" class="w-full">
                <div class="p-4">
                  <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                      <ClipboardList class="h-4 w-4 text-primary" />
                      <h4 class="text-base font-medium">{{ $t('Changed agenda items') }}</h4>
                    </div>
                    <CollapsibleTrigger asChild>
                      <Button variant="ghost" size="icon">
                        <ChevronDown 
                          v-if="!openItems[meeting.id]" 
                          class="h-4 w-4 text-muted-foreground" 
                        />
                        <ChevronUp 
                          v-else 
                          class="h-4 w-4 text-muted-foreground" 
                        />
                        <span class="sr-only">{{ $t('Toggle') }}</span>
                      </Button>
                    </CollapsibleTrigger>
                  </div>
                </div>
                
                <CollapsibleContent class="px-4 pb-4">
                  <Separator class="mb-4" />
                  <div class="space-y-6">
                    <div 
                      v-for="agendaItem in meeting.changedAgendaItems" 
                      :key="agendaItem.id"
                      class="border-b last:border-0 pb-4 last:pb-0"
                    >
                      <div class="flex items-center gap-2 mb-3">
                        <Icons.AGENDA_ITEM class="h-4 w-4 text-primary" />
                        <h5 class="font-medium">{{ agendaItem.title }}</h5>
                      </div>
                      
                      <div v-if="agendaItem.activities && agendaItem.activities.length > 0" class="space-y-3">
                        <div 
                          v-for="activity in agendaItem.activities" 
                          :key="activity.id"
                          class="bg-muted/50 rounded-md p-4"
                        >
                          <ActivityTimeline :activities="[activity]" />
                        </div>
                      </div>
                    </div>
                  </div>
                </CollapsibleContent>
              </Collapsible>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { format } from "date-fns";
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

// Layout Components
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';

// UI Components
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle
} from '@/Components/ui/card';
import { Label } from '@/Components/ui/label';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/Components/ui/select';
import { DatePicker } from '@/Components/ui/date-picker';
import { Separator } from '@/Components/ui/separator';
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger
} from '@/Components/ui/collapsible';
import { Button } from '@/Components/ui/button';
import EmptyState from '@/Components/Empty/EmptyState.vue';
import ActivityTimeline from '@/Components/Dashboard/ActivityTimeline.vue';

// Icons
import Icons from '@/Types/Icons/filled';
import { 
  ChevronDown, 
  ChevronUp,
  ClipboardList,
  CalendarX
} from 'lucide-vue-next';

import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';

const props = defineProps<{
  meetings: Array<App.Entities.Meeting & { changedAgendaItems: App.Entities.AgendaItem[] }>;
  date: string;
  tenants: App.Entities.Tenant[];
  providedTenant: App.Entities.Tenant;
}>();

// Convert string date to Date object for DatePicker compatibility
const parseDate = (dateString: string): Date | undefined => {
  if (!dateString) return undefined;
  const parsedDate = new Date(dateString);
  return isNaN(parsedDate.getTime()) ? undefined : parsedDate;
};

const form = useForm({
  date: parseDate(props.date),
  tenant_id: props.providedTenant.id,
});

// Get current locale for date picker
const currentLocale = usePage().props.app.locale;

// Track the open state for each meeting's collapsible
const openItems = ref<Record<string, boolean>>({});

// Filter meetings by if they have an activity on this date
const filteredMeetings = computed(() => {
  return props.meetings.filter(meeting => {
    // Check if the meeting has activities on this date
    const hasMeetingActivities = meeting.activities?.some(
      activity => activity.created_at.includes(props.date)
    );
    
    // Check if any agenda items have activities on this date
    const hasAgendaItemActivities = meeting.changedAgendaItems?.some(
      item => item.activities?.some(
        activity => activity.created_at.includes(props.date)
      )
    );
    
    return hasMeetingActivities || hasAgendaItemActivities;
  });
});

// Setup breadcrumbs for the Atstovavimas Activity page
usePageBreadcrumbs([
  { label: $t('Atstovavimas'), href: route('dashboard.atstovavimas'), icon: Icons.MEETING },
  { label: $t('Veiklos'), icon: Icons.DOING }
]);

// Handle date change
const handleDateUpdate = () => {
  // Extract the date from the form
  const selectedDate = form.date;
  // Format it properly for the URL
  const formattedDate = selectedDate ? format(selectedDate, 'yyyy-MM-dd') : '';
  
  router.visit(route('dashboard.atstovavimas.summary', { 
    date: formattedDate, 
    tenant_id: props.providedTenant.id 
  }));
};

// Handle tenant change
const handleTenantUpdate = (value: any) => {
  // Ensure the value is a valid tenant ID before using it
  if (value !== null && value !== undefined) {
    router.reload({ data: { tenant_id: value } });
  }
};

// Initialize collapsible states
onMounted(() => {
  // Default to closed state for all collapsibles
  props.meetings.forEach(meeting => {
    openItems.value[meeting.id] = false;
  });
});
</script>
