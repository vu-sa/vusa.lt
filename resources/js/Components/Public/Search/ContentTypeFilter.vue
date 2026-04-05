<template>
  <div class="content-type-filter">
    <div class="space-y-3">
      <!-- Content Type Groups -->
      <div class="space-y-3">
        <!-- Most Important Documents -->
        <div v-if="importantContentTypes.length > 0" class="space-y-2">
          <h5 class="text-xs font-medium text-amber-600 dark:text-amber-400 uppercase tracking-wide flex items-center gap-1">
            <Star class="w-3 h-3" />
            {{ $t('search.most_important') }}
          </h5>
          <div class="flex flex-wrap gap-1.5">
            <Badge v-for="contentType in importantContentTypes" :key="contentType.value"
              :variant="isSelected(contentType.value) ? 'default' : 'outline'" :class="[
                'cursor-pointer transition-all duration-200 text-xs hover:scale-105 py-1 px-2',
                isSelected(contentType.value)
                  ? 'bg-amber-500 text-white hover:bg-amber-600'
                  : 'border-amber-300 dark:border-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950'
              ]" @click="toggleContentType(contentType.value)">
              {{ contentType.label }}
              <span v-if="contentType.count > 0" class="ml-1.5 text-xs opacity-70 font-medium">
                {{ contentType.count }}
              </span>
            </Badge>
          </div>
        </div>

        <!-- VU SA Documents -->
        <div v-if="vuSaContentTypes.length > 0" class="space-y-2">
          <h5 class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
            VU SA
          </h5>
          <div class="flex flex-wrap gap-1.5">
            <Badge v-for="contentType in vuSaContentTypes" :key="contentType.value"
              :variant="isSelected(contentType.value) ? 'default' : 'outline'" :class="[
                'cursor-pointer transition-all duration-200 text-xs hover:scale-105 py-1 px-2',
                isSelected(contentType.value)
                  ? 'bg-primary text-primary-foreground'
                  : 'hover:bg-accent'
              ]" @click="toggleContentType(contentType.value)">
              {{ contentType.label }}
              <span v-if="contentType.count > 0" class="ml-1.5 text-xs opacity-70 font-medium">
                {{ contentType.count }}
              </span>
            </Badge>
          </div>
        </div>

        <!-- VU SA P Documents -->
        <div v-if="vuSaPContentTypes.length > 0" class="space-y-2">
          <h5 class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
            VU SA P
          </h5>
          <div class="flex flex-wrap gap-1.5">
            <Badge v-for="contentType in vuSaPContentTypes" :key="contentType.value"
              :variant="isSelected(contentType.value) ? 'default' : 'outline'" :class="[
                'cursor-pointer transition-all duration-200 text-xs hover:scale-105 py-1 px-2',
                isSelected(contentType.value)
                  ? 'bg-primary text-primary-foreground'
                  : 'hover:bg-accent'
              ]" @click="toggleContentType(contentType.value)">
              {{ contentType.label }}
              <span v-if="contentType.count > 0" class="ml-1.5 text-xs opacity-70 font-medium">
                {{ contentType.count }}
              </span>
            </Badge>
          </div>
        </div>

        <!-- Other Documents -->
        <div v-if="otherContentTypes.length > 0" class="space-y-2">
          <h5 class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
            {{ $t('search.other_types') }}
          </h5>
          <div class="flex flex-wrap gap-1.5">
            <Badge v-for="contentType in otherContentTypes" :key="contentType.value"
              :variant="isSelected(contentType.value) ? 'default' : 'outline'" :class="[
                'cursor-pointer transition-all duration-200 text-xs hover:scale-105 py-1 px-2',
                isSelected(contentType.value)
                  ? 'bg-primary text-primary-foreground'
                  : 'hover:bg-accent'
              ]" @click="toggleContentType(contentType.value)">
              {{ contentType.label }}
              <span v-if="contentType.count > 0" class="ml-1.5 text-xs opacity-70 font-medium">
                {{ contentType.count }}
              </span>
            </Badge>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="flex gap-1 pt-2 border-t border-border/50">
        <Button variant="ghost" size="sm" class="h-6 px-1.5 text-xs" @click="selectAll">
          <CheckSquare class="w-3 h-3 mr-1" />
          {{ $t('search.select_all') }}
        </Button>
        <Button variant="ghost" size="sm" class="h-6 px-1.5 text-xs" :disabled="selectedContentTypes.length === 0"
          @click="clearSelection">
          <RotateCcw class="w-3 h-3 mr-1" />
          {{ $t('search.clear_all') }}
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

// ShadcnVue components
import { FileText, CheckSquare, RotateCcw, Star } from 'lucide-vue-next';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

// Icons

// Props & Emits
interface ContentTypeOption {
  value: string;
  label: string;
  count: number;
}

interface GroupedContentTypes {
  vusa: Array<{ value: string; label: string; count: number }>;
  vusaP: Array<{ value: string; label: string; count: number }>;
  other: Array<{ value: string; label: string; count: number }>;
}

interface Props {
  groupedTypes: GroupedContentTypes;
  selectedTypes: string[];
  importantTypes?: string[];
}

type Emits = (e: 'toggleType', value: string) => void;

const props = withDefaults(defineProps<Props>(), {
  groupedTypes: () => ({ vusa: [], vusaP: [], other: [] }),
  selectedTypes: () => [],
  importantTypes: () => [],
});

const emit = defineEmits<Emits>();

// Helper function to truncate long labels
const truncateLabel = (label: string, maxLength: number): string => {
  if (label.length <= maxLength) return label;
  return `${label.substring(0, maxLength - 3)}...`;
};

// All content types combined for lookup
const allGroupedTypes = computed(() => [
  ...props.groupedTypes.vusa,
  ...props.groupedTypes.vusaP,
  ...props.groupedTypes.other,
]);

// Important content types (extracted from other groups based on importantTypes prop)
const importantContentTypes = computed(() => {
  if (!props.importantTypes || props.importantTypes.length === 0) return [];

  return props.importantTypes
    .map((importantValue) => {
      const found = allGroupedTypes.value.find(ct => ct.value === importantValue);
      if (!found) return null;
      return {
        value: found.value,
        label: truncateLabel(found.label || found.value, 28),
        count: found.count,
      };
    })
    .filter((ct): ct is { value: string; label: string; count: number } => ct !== null);
});

// Set of important type values for quick lookup
const importantTypeSet = computed(() => new Set(props.importantTypes || []));

// Use the grouped types directly from props, but exclude important ones
const vuSaContentTypes = computed(() => {
  return props.groupedTypes.vusa
    .filter(ct => !importantTypeSet.value.has(ct.value))
    .map(ct => ({
      value: ct.value,
      label: truncateLabel(ct.label || ct.value, 28),
      count: ct.count,
    }));
});

const vuSaPContentTypes = computed(() => {
  return props.groupedTypes.vusaP
    .filter(ct => !importantTypeSet.value.has(ct.value))
    .map(ct => ({
      value: ct.value,
      label: truncateLabel(ct.label || ct.value, 28),
      count: ct.count,
    }));
});

const otherContentTypes = computed(() => {
  return props.groupedTypes.other
    .filter(ct => !importantTypeSet.value.has(ct.value))
    .map(ct => ({
      value: ct.value,
      label: truncateLabel(ct.label || ct.value, 28),
      count: ct.count,
    }));
});

const allContentTypes = computed(() => [
  ...props.groupedTypes.vusa,
  ...props.groupedTypes.vusaP,
  ...props.groupedTypes.other,
]);

// Methods
const isSelected = (contentType: string): boolean => {
  return props.selectedTypes.includes(contentType);
};

const toggleContentType = (contentType: string) => {
  emit('toggleType', contentType);
};

const selectAll = () => {
  const allTypes = allContentTypes.value.map(ct => ct.value);
  allTypes.forEach((type) => {
    if (!isSelected(type)) {
      emit('toggleType', type);
    }
  });
};

const clearSelection = () => {
  // Create a copy of selectedTypes to avoid mutation during iteration
  const typesToClear = [...props.selectedTypes];
  typesToClear.forEach((type) => {
    emit('toggleType', type);
  });
};

// Computed for selected content types count
const selectedContentTypes = computed(() => {
  return allContentTypes.value.filter(ct => props.selectedTypes.includes(ct.value));
});
</script>
