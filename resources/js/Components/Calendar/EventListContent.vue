<template>
  <div>
    <!-- Events counter with improved readability -->
    <div class="my-4 text-zinc-500 dark:text-zinc-400">
      {{ $t("Rasta :count renginių", { count: events.total.toString() }) }}
      <template v-if="events.last_page > 1">
        | {{ $t("Psl.") }} {{ events.current_page }} / {{ events.last_page }}
      </template>
    </div>

    <!-- List view of events -->
    <div class="mb-8">
      <div v-if="events.data.length > 0" class="space-y-3">
        <EventCard v-for="event in events.data" :key="event.id" :event
          :variant="tab === 'upcoming' ? 'upcoming' : 'past'" :google-link="generateGoogleLink(event)" />
      </div>
      <div v-else
        class="rounded-lg border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-800">
        <p class="text-zinc-600 dark:text-zinc-400">
          {{ tab === 'upcoming' ? $t("Nėra būsimų renginių") : $t("Nėra praėjusių renginių") }}
        </p>
      </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex justify-center">
      <Pagination v-if="events.total > events.per_page" 
        :total="events.total" 
        :items-per-page="events.per_page" 
        :page="events.current_page"
        :sibling-count="1"
        show-edges
        @update:page="onPageChange"
      >
        <PaginationContent>
          <PaginationFirst />
          <PaginationPrevious />
          <template v-for="(item, index) in paginationItems" :key="index">
            <PaginationEllipsis v-if="item.type === 'ellipsis'" :index="index" />
            <PaginationItem v-else :value="item.value" :is-active="item.value === events.current_page" as-child>
              <Button :variant="item.value === events.current_page ? 'default' : 'outline'" class="h-9 w-9 p-0">
                {{ item.value }}
              </Button>
            </PaginationItem>
          </template>
          <PaginationNext />
          <PaginationLast />
        </PaginationContent>
      </Pagination>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

import EventCard from "./EventCard.vue";
import { Button } from "@/Components/ui/button";
import { 
  Pagination, 
  PaginationContent, 
  PaginationEllipsis, 
  PaginationFirst, 
  PaginationItem, 
  PaginationLast, 
  PaginationNext, 
  PaginationPrevious 
} from "@/Components/ui/pagination";

const props = defineProps<{
  events: {
    data: App.Entities.Calendar[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    path: string;
    links: any[];
  };
  tab: string;
}>();

const emit = defineEmits(['pageChange']);

// Handle page change
const onPageChange = (page: number) => {
  emit('pageChange', page);
};

// Generate pagination items with ellipsis
const paginationItems = computed(() => {
  const items: Array<{ type: 'page' | 'ellipsis'; value?: number }> = [];
  const currentPage = props.events.current_page;
  const lastPage = props.events.last_page;
  const siblingCount = 1;
  
  // Always show first page
  items.push({ type: 'page', value: 1 });
  
  // Calculate range around current page
  const leftSibling = Math.max(currentPage - siblingCount, 2);
  const rightSibling = Math.min(currentPage + siblingCount, lastPage - 1);
  
  // Add ellipsis if needed on the left
  if (leftSibling > 2) {
    items.push({ type: 'ellipsis' });
  }
  
  // Add pages around current
  for (let i = leftSibling; i <= rightSibling; i++) {
    if (i > 1 && i < lastPage) {
      items.push({ type: 'page', value: i });
    }
  }
  
  // Add ellipsis if needed on the right
  if (rightSibling < lastPage - 1) {
    items.push({ type: 'ellipsis' });
  }
  
  // Always show last page if more than 1 page
  if (lastPage > 1) {
    items.push({ type: 'page', value: lastPage });
  }
  
  return items;
});

// Generate Google Calendar link for events
const generateGoogleLink = (event: App.Entities.Calendar) => {
  if (!event) return '';

  const startDate = new Date(event.date);
  const endDate = event.end_date ? new Date(event.end_date) : new Date(startDate.getTime() + 60 * 60 * 1000); // Default 1 hour

  const formatGoogleDate = (date: Date) => {
    return date.toISOString().replace(/-|:|\.\d+/g, '');
  };

  const { description } = event;
  const details = Array.isArray(description)
    ? description.join(' ').replace(/<[^>]*>/g, ' ')
    : (description || '').replace(/<[^>]*>/g, ' ');

  const title = Array.isArray(event.title) ? event.title.join(' ') : (event.title || '');
  const location = Array.isArray(event.location) ? event.location.join(' ') : (event.location || '');

  return `https://www.google.com/calendar/render?action=TEMPLATE` +
    `&text=${encodeURIComponent(title)}` +
    `&dates=${formatGoogleDate(startDate)}/${formatGoogleDate(endDate)}` +
    `&details=${encodeURIComponent(details)}${location ? `&location=${encodeURIComponent(location)}` : ''}`;
};
</script>
