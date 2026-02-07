<template>
  <Head>
    <title>{{ $t('search.meeting_page_title') }}</title>
    <meta name="description" :content="$t('search.meeting_page_description')">
  </Head>

  <MeetingSearchInterface
    :initial-query
    :initial-filters
  />
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import MeetingSearchInterface from '@/Components/Public/Search/MeetingSearchInterface.vue';

// Set wider layout for meeting search page
onMounted(() => {
  const page = usePage();
  // This will be picked up by PublicLayout for wider content area
  page.props.layoutWidth = 'content';
});

// Extract initial search parameters from URL
const getUrlParams = () => {
  if (typeof window === 'undefined') return new URLSearchParams();
  return new URLSearchParams(window.location.search);
};

// Initial query from URL
const initialQuery = computed(() => {
  const params = getUrlParams();
  return params.get('q') || '';
});

// Initial filters from URL parameters
const initialFilters = computed(() => {
  const params = getUrlParams();
  const filters: Record<string, any> = {};

  // Parse array parameters (tenants, years, successRateRanges)
  const parseArrayParam = (paramName: string, maxItems = 29) => {
    const items = [];
    for (let i = 0; i < maxItems; i++) {
      const value = params.get(`${paramName}[${i}]`);
      if (value) {
        items.push(value);
      } else {
        break;
      }
    }
    return items.length > 0 ? items : undefined;
  };

  // Parse number array parameters (years)
  const parseNumberArrayParam = (paramName: string, maxItems = 29) => {
    const items = [];
    for (let i = 0; i < maxItems; i++) {
      const value = params.get(`${paramName}[${i}]`);
      if (value) {
        const num = Number(value);
        if (!isNaN(num)) {
          items.push(num);
        }
      } else {
        break;
      }
    }
    return items.length > 0 ? items : undefined;
  };

  // Extract filter parameters
  filters.tenants = parseArrayParam('tenants');
  filters.years = parseNumberArrayParam('years', 10); // Limited to 10 years
  filters.successRateRanges = parseArrayParam('successRateRanges', 3); // Max 3 ranges

  // Date range filters
  const dateFrom = params.get('dateFrom');
  const dateTo = params.get('dateTo');
  const datePreset = params.get('datePreset');

  if (dateFrom || dateTo || datePreset) {
    filters.dateRange = {
      from: dateFrom ? new Date(Number(dateFrom) * 1000) : undefined,
      to: dateTo ? new Date(Number(dateTo) * 1000) : undefined,
      preset: datePreset || undefined
    };
  }

  return filters;
});
</script>
