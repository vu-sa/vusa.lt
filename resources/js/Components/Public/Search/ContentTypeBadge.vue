<template>
  <button
    :class="getBadgeClasses()"
    @click="$emit('toggle')"
  >
    <div class="flex items-center gap-2 flex-1 min-w-0">
      <span class="text-sm">{{ getTypeIcon() }}</span>
      <div class="flex-1 min-w-0 text-left">
        <div class="text-sm font-medium truncate">
          {{ getDisplayName() }}
        </div>
        <div v-if="type.count > 0" class="text-xs text-muted-foreground">
          {{ type.count }} {{ type.count === 1 ? 'dokumentas' : 'dokumentai' }}
        </div>
      </div>
      <Badge
        v-if="type.count > 0"
        :variant="isSelected ? 'default' : 'outline'"
        :class="getCountBadgeClasses()"
        class="text-xs font-medium"
      >
        {{ formatCount(type.count) }}
      </Badge>
    </div>
  </button>
</template>

<script setup lang="ts">
import { Badge } from '@/Components/ui/badge';

interface ContentTypeValue {
  value: string;
  label: string;
  count: number;
}

interface Props {
  type: ContentTypeValue;
  isSelected: boolean;
  color: 'red' | 'purple' | 'blue' | 'green';
}

type Emits = (e: 'toggle') => void;

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Helper functions
const formatCount = (count: number): string => {
  if (count >= 1000) {
    return `${(count / 1000).toFixed(1)}k`;
  }
  return count.toString();
};

const getDisplayName = (): string => {
  return props.type.value.replace(/^VU SA P? /, '');
};

const getTypeIcon = (): string => {
  const type = props.type.value.toLowerCase();

  if (type.includes('protokolas')) return '📋';
  if (type.includes('statutas')) return '📜';
  if (type.includes('nuostatai')) return '📄';
  if (type.includes('sprendimas')) return '⚖️';
  if (type.includes('reglamentas')) return '📋';
  if (type.includes('ataskaita')) return '📊';
  if (type.includes('informacija')) return 'ℹ️';
  if (type.includes('pranešimas')) return '📢';
  if (type.includes('kvietimas')) return '✉️';
  if (type.includes('darbotvarkė')) return '📝';

  return '📄';
};

const getBadgeClasses = (): string => {
  const baseClasses = 'w-full p-3 rounded-lg border transition-all duration-200 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 cursor-pointer';

  if (props.isSelected) {
    switch (props.color) {
      case 'red':
        return `${baseClasses} bg-red-50 dark:bg-red-950/30 border-red-500 dark:border-red-400 text-red-900 dark:text-red-100 focus:ring-red-500`;
      case 'purple':
        return `${baseClasses} bg-purple-50 dark:bg-purple-950/30 border-purple-500 dark:border-purple-400 text-purple-900 dark:text-purple-100 focus:ring-purple-500`;
      case 'blue':
        return `${baseClasses} bg-blue-50 dark:bg-blue-950/30 border-blue-500 dark:border-blue-400 text-blue-900 dark:text-blue-100 focus:ring-blue-500`;
      case 'green':
        return `${baseClasses} bg-green-50 dark:bg-green-950/30 border-green-500 dark:border-green-400 text-green-900 dark:text-green-100 focus:ring-green-500`;
    }
  }

  return `${baseClasses} bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100 hover:bg-accent/50 focus:ring-zinc-500`;
};

const getCountBadgeClasses = (): string => {
  if (props.isSelected) {
    switch (props.color) {
      case 'red':
        return 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/50 dark:text-red-200 dark:border-red-700';
      case 'purple':
        return 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/50 dark:text-purple-200 dark:border-purple-700';
      case 'blue':
        return 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/50 dark:text-blue-200 dark:border-blue-700';
      case 'green':
        return 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/50 dark:text-green-200 dark:border-green-700';
    }
  }

  return 'bg-zinc-100 text-zinc-700 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700';
};
</script>

<style scoped>
/* Hover animations */
button:hover {
  transform: translateY(-1px);
}

/* Focus states for accessibility */
button:focus-visible {
  outline: none;
}

/* Selected state animation */
button[data-selected="true"] {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Responsive text sizing */
@media (max-width: 640px) {
  .text-sm {
    @apply text-xs;
  }

  .text-xs {
    @apply text-xs;
  }
}
</style>
