<template>
  <Breadcrumb>
    <BreadcrumbList>
      <!-- Dashboard home -->
      <BreadcrumbItem v-if="includeHome">
        <BreadcrumbLink :href="route('dashboard')">
          <div class="flex items-center gap-1.5">
            <Home24Filled class="h-3.5 w-3.5" />
            <span>{{ $t('Pradinis') }}</span>
          </div>
        </BreadcrumbLink>
      </BreadcrumbItem>
      <BreadcrumbSeparator v-if="includeHome && items.length > 0" />

      <!-- Dynamic items -->
      <template v-for="(item, index) in items" :key="index">
        <BreadcrumbItem>
          <template v-if="item.href">
            <BreadcrumbLink :href="item.href">
              <div class="flex items-center gap-1.5">
                <component v-if="item.icon" :is="item.icon" class="h-3.5 w-3.5" />
                <span>{{ item.label }}</span>
              </div>
            </BreadcrumbLink>
          </template>
          <template v-else>
            <BreadcrumbPage>
              <div class="flex items-center gap-1.5">
                <component v-if="item.icon" :is="item.icon" class="h-3.5 w-3.5" />
                <span>{{ item.label }}</span>
              </div>
            </BreadcrumbPage>
          </template>
        </BreadcrumbItem>
        
        <BreadcrumbSeparator v-if="index < items.length - 1" />
      </template>
    </BreadcrumbList>
  </Breadcrumb>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import Home24Filled from "~icons/fluent/home24-filled";
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/Components/ui/breadcrumb';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/Composables/useBreadcrumbs';

const props = withDefaults(defineProps<{
  items: BreadcrumbItemType[];
  includeHome?: boolean;
}>(), {
  includeHome: true
});
</script>