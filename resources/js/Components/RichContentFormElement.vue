<template>
  <div class="rich-content-form-element mb-6">
    <!-- Tab Navigation -->
    <div class="mb-4 flex items-center gap-2 rounded-lg border bg-white p-1 dark:bg-zinc-900 dark:border-zinc-700">
      <Button :variant="activeTab === 'edit' ? 'default' : 'ghost'" size="sm" class="flex items-center gap-2"
        @click="activeTab = 'edit'">
        <IFluentEdit24Filled class="h-4 w-4" />
        Redagavimas
      </Button>
      <Button :variant="activeTab === 'preview' ? 'default' : 'ghost'" size="sm" class="flex items-center gap-2"
        @click="activeTab = 'preview'">
        <IFluentEye24Filled class="h-4 w-4" />
        Peržiūra
      </Button>
    </div>

    <!-- Tab Content -->
    <div v-show="activeTab === 'edit'">
      <Suspense>
        <RichContentEditor v-model:contents="contentParts" />
        <template #fallback>
          <div class="space-y-6">
            <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
              <div
                class="h-4 w-4 animate-spin rounded-full border-2 border-zinc-300 border-r-transparent dark:border-zinc-600" />
              Loading rich content editor...
            </div>
            <div class="space-y-4">
              <Skeleton class="h-32 w-full rounded-lg" />
              <div class="flex gap-2">
                <Skeleton class="h-10 w-32 rounded" />
                <Skeleton class="h-10 w-32 rounded" />
                <Skeleton class="h-10 w-32 rounded" />
              </div>
            </div>
          </div>
        </template>
      </Suspense>
    </div>

    <div v-show="activeTab === 'preview'" class="rounded-lg border bg-white p-6 dark:bg-zinc-900 dark:border-zinc-700">
      <div class="typography max-w-none">
        <Suspense>
          <RichContentParser :content="contentParts" />
          <template #fallback>
            <div class="space-y-4">
              <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                <div
                  class="h-4 w-4 animate-spin rounded-full border-2 border-zinc-300 border-r-transparent dark:border-zinc-600" />
                Loading preview...
              </div>
              <Skeleton class="h-48 w-full" />
            </div>
          </template>
        </Suspense>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

import RichContentEditor from "./RichContentEditor.vue";
import RichContentParser from "./RichContentParser.vue";

import { Button } from '@/Components/ui/button';
import { Skeleton } from '@/Components/ui/skeleton';
import IFluentEdit24Filled from '~icons/fluent/edit24-filled';
import IFluentEye24Filled from '~icons/fluent/eye24-filled';

const contentParts = defineModel();
const activeTab = ref<'edit' | 'preview'>('edit');
</script>
