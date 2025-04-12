<template>
  <Card class="h-full border shadow-xs transition-shadow hover:shadow-md dark:border-zinc-800/40">
    <CardHeader class="my-auto h-full justify-center p-4">
      <CardTitle class="mb-0 text-xl">
        {{ page.title }}
      </CardTitle>
      <!-- <CardContent>
        {{ page.title }}
      </CardContent> -->
    </CardHeader>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardHeader, CardTitle } from '@/Components/ui/card';
import CardContent from '../ui/card/CardContent.vue';

defineProps<{
  page: App.Entities.Page;
}>();
</script>
