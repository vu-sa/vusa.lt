<template>
  <Breadcrumb v-if="items.length > 0" class="mb-4 ml-1">
    <BreadcrumbList>
      <BreadcrumbItem>
        <BreadcrumbLink :href="homeUrl" class="inline-flex items-center">
          <IFluentHome16Regular class="mr-1 size-3.5" />
          <span>{{ $t('Pradinis') }}</span>
        </BreadcrumbLink>
      </BreadcrumbItem>
      
      <BreadcrumbSeparator />
      
      <template v-for="(item, i) in items" :key="i">
        <BreadcrumbItem>
          <template v-if="i === items.length - 1 || !item.href">
            <BreadcrumbPage class="inline-flex items-center">
              <component :is="item.icon" v-if="item.icon" class="mr-1 size-3.5" />
              <span>{{ $t(item.label) }}</span>
            </BreadcrumbPage>
          </template>
          <template v-else>
            <BreadcrumbLink :href="item.href" class="inline-flex items-center">
              <component :is="item.icon" v-if="item.icon" class="mr-1 size-3.5" />
              <span>{{ $t(item.label) }}</span>
            </BreadcrumbLink>
          </template>
        </BreadcrumbItem>
        
        <BreadcrumbSeparator v-if="i < items.length - 1" />
      </template>
    </BreadcrumbList>
  </Breadcrumb>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { 
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator
} from '@/Components/ui/breadcrumb';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/Composables/useBreadcrumbs';
import IFluentHome16Regular from '~icons/fluent/home-16-regular';

const props = defineProps<{
  items: BreadcrumbItemType[];
}>();

const homeUrl = computed(() => {
  const { subdomain, locale } = usePage().props.app;
  return route('home', { subdomain: subdomain || 'www', lang: locale });
});
</script>

<style scoped>
/* Ensure proper alignment of breadcrumb items */
:deep([data-slot="breadcrumb-link"]),
:deep([data-slot="breadcrumb-page"]) {
  display: inline-flex;
  align-items: center;
}
</style>