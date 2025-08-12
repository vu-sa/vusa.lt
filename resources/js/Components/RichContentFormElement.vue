<template>
  <div class="rich-content-form-element">
    <!-- Tab Navigation -->
    <div class="mb-4 flex items-center gap-2 rounded-lg border bg-white p-1 dark:bg-zinc-900 dark:border-zinc-700">
      <Button
        :variant="activeTab === 'edit' ? 'default' : 'ghost'"
        size="sm"
        class="flex items-center gap-2"
        @click="activeTab = 'edit'"
      >
        <IFluentEdit24Filled class="h-4 w-4" />
        Redagavimas
      </Button>
      <Button
        :variant="activeTab === 'preview' ? 'default' : 'ghost'"
        size="sm"
        class="flex items-center gap-2"
        @click="activeTab = 'preview'"
      >
        <IFluentEye24Filled class="h-4 w-4" />
        Peržiūra
      </Button>
    </div>

    <!-- Tab Content -->
    <div v-show="activeTab === 'edit'">
      <RichContentEditor v-model:contents="contentParts" />
    </div>
    
    <div v-show="activeTab === 'preview'" class="rounded-lg border bg-white p-6 dark:bg-zinc-900 dark:border-zinc-700">
      <div class="typography max-w-none">
        <RichContentParser :content="contentParts" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/Components/ui/button';
import RichContentEditor from "./RichContentEditor.vue";
import RichContentParser from "./RichContentParser.vue";
import IFluentEdit24Filled from '~icons/fluent/edit24-filled';
import IFluentEye24Filled from '~icons/fluent/eye24-filled';

const contentParts = defineModel();
const activeTab = ref<'edit' | 'preview'>('edit');
</script>
