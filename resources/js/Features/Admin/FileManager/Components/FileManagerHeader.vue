<template>
  <div class="space-y-4">
    <!-- Header with actions and search -->
    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
      <div class="flex gap-2" v-if="!selectionMode">
        <!-- Toggle between browse and upload modes -->
        <Button 
          :variant="isUploadMode ? 'outline' : 'default'" 
          size="sm" 
          @click="$emit('update:isUploadMode', false)"
        >
          <IFluentFolder24Regular class="mr-2 h-4 w-4" />
          Naršyti
        </Button>
        <Button 
          :variant="isUploadMode ? 'default' : 'outline'" 
          size="sm" 
          @click="$emit('update:isUploadMode', true)"
        >
          <IFluentCloudArrowUp24Regular class="mr-2 h-4 w-4" />
          Įkelti failus
        </Button>
        <Button variant="outline" size="sm" @click="$emit('showCreateFolder')">
          <IFluentFolderAdd24Regular class="mr-2 h-4 w-4" />
          Pridėti aplanką
        </Button>
      </div>
      <div class="flex-1"></div>
      <!-- Fixed width and height container to prevent layout shifts -->
      <div class="w-full sm:w-[300px] h-10 flex items-center">
        <Input 
          v-if="!isUploadMode"
          :model-value="search" 
          @update:model-value="$emit('update:search', $event)"
          class="w-full" 
          :size="small ? 'sm' : undefined" 
          placeholder="Ieškoti failų..." 
        />
      </div>
    </div>
    
    <!-- Interactive breadcrumb navigation -->
    <div class="flex items-center gap-2 text-sm bg-muted/30 rounded-md px-3 py-2">
      <IFluentFolder24Filled class="h-4 w-4 text-muted-foreground flex-shrink-0" />
      <nav class="flex items-center gap-1 text-foreground min-w-0 flex-1">
        <!-- Upload mode indicator -->
        <span v-if="isUploadMode && !selectionMode" class="text-xs text-muted-foreground mr-2">
          Įkeliama į:
        </span>
        <button 
          @click="!isUploadMode ? $emit('navigateToPath', 'public/files') : undefined"
          class="font-medium transition-colors truncate"
          :class="{ 
            'text-vusa-red hover:text-vusa-red': path === 'public/files',
            'hover:text-vusa-red': !isUploadMode,
            'cursor-default': isUploadMode 
          }"
        >
          Failai
        </button>
        <template v-if="breadcrumbParts.length > 0">
          <template v-for="(part, index) in breadcrumbParts" :key="index">
            <span class="text-muted-foreground flex-shrink-0">/</span>
            <button 
              @click="!isUploadMode ? $emit('navigateToPath', part.path) : undefined"
              class="transition-colors truncate"
              :class="{ 
                'text-vusa-red font-medium': index === breadcrumbParts.length - 1,
                'text-muted-foreground': index < breadcrumbParts.length - 1,
                'hover:text-vusa-red': !isUploadMode,
                'cursor-default': isUploadMode
              }"
            >
              {{ part.name }}
            </button>
          </template>
        </template>
      </nav>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

// Import icons
import IFluentFolder24Regular from '~icons/fluent/folder-24-regular';
import IFluentCloudArrowUp24Regular from '~icons/fluent/cloud-arrow-up-24-regular';
import IFluentFolderAdd24Regular from '~icons/fluent/folder-add-24-regular';
import IFluentFolder24Filled from '~icons/fluent/folder-24-filled';

const props = defineProps<{
  path: string;
  search: string;
  isUploadMode: boolean;
  selectionMode?: boolean;
  small?: boolean;
}>();

const emit = defineEmits<{
  'update:search': [value: string];
  'update:isUploadMode': [value: boolean];
  'navigateToPath': [path: string];
  'showCreateFolder': [];
}>();

const breadcrumbParts = computed(() => {
  if (props.path === 'public/files') return [];
  
  const pathWithoutPublicFiles = props.path.replace('public/files/', '');
  const parts = pathWithoutPublicFiles.split('/');
  
  return parts.map((part, index) => {
    const pathUpToIndex = 'public/files/' + parts.slice(0, index + 1).join('/');
    return {
      name: part,
      path: pathUpToIndex
    };
  });
});
</script>