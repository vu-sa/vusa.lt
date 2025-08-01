<template>
  <div class="animate-pulse">
    <!-- List View Skeleton -->
    <div v-if="viewMode === 'list'" class="h-28">
      <div class="border rounded-lg p-4 bg-background h-full">
        <div class="flex items-start gap-4">
          <!-- Icon skeleton -->
          <div class="w-10 h-10 rounded-lg bg-muted animate-pulse flex-shrink-0"></div>
          
          <!-- Main content -->
          <div class="flex-1 space-y-3">
            <!-- Title and badges row -->
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1 space-y-3">
                <!-- Title -->
                <div class="h-4 bg-muted rounded animate-pulse w-4/5"></div>
                
                <!-- Badges -->
                <div class="flex items-center gap-2 flex-wrap">
                  <div class="h-5 bg-muted rounded-full animate-pulse w-20"></div>
                  <div class="h-5 bg-muted rounded-full animate-pulse w-16"></div>
                  <div class="h-5 bg-muted rounded-full animate-pulse w-12"></div>
                  <div class="h-5 bg-muted rounded-full animate-pulse w-14"></div>
                </div>
              </div>
              
              <!-- Date and actions -->
              <div class="flex items-center gap-3 flex-shrink-0">
                <div class="text-right space-y-1">
                  <div class="h-3 bg-muted rounded animate-pulse w-20"></div>
                  <div class="h-3 bg-muted rounded animate-pulse w-16"></div>
                </div>
                <div class="w-4 h-4 bg-muted rounded animate-pulse"></div>
              </div>
            </div>
            
            <!-- Summary -->
            <div class="space-y-2">
              <div class="h-3 bg-muted rounded animate-pulse w-full"></div>
              <div class="h-3 bg-muted rounded animate-pulse w-4/5"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Compact View Skeleton -->
    <div v-else-if="viewMode === 'compact'" class="h-12">
      <div class="border border-border/50 rounded-md bg-background h-full">
        <div class="flex items-center gap-3 px-4 py-2">
          <!-- Icon skeleton -->
          <div class="w-4 h-4 rounded bg-muted animate-pulse flex-shrink-0"></div>
          
          <!-- Title -->
          <div class="flex-1">
            <div class="h-3 bg-muted rounded animate-pulse w-3/5"></div>
          </div>
          
          <!-- Compact metadata -->
          <div class="flex items-center gap-2 flex-shrink-0">
            <!-- Content type badge -->
            <div class="h-4 bg-muted rounded-full animate-pulse w-16"></div>
            <!-- Organization -->
            <div class="h-3 bg-muted rounded animate-pulse w-12"></div>
            <!-- Date -->
            <div class="h-3 bg-muted rounded animate-pulse w-10"></div>
            <!-- External link icon -->
            <div class="w-3.5 h-3.5 bg-muted rounded animate-pulse"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  viewMode: 'list' | 'compact'
}

const props = withDefaults(defineProps<Props>(), {
  viewMode: 'list'
})
</script>

<style scoped>
/* Custom pulse animation with slightly different timing */
.animate-pulse {
  animation: skeleton-pulse 1.5s ease-in-out infinite;
}

@keyframes skeleton-pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.4;
  }
}

/* Staggered animation for multiple elements */
.animate-pulse:nth-child(1) {
  animation-delay: 0s;
}

.animate-pulse:nth-child(2) {
  animation-delay: 0.1s;
}

.animate-pulse:nth-child(3) {
  animation-delay: 0.2s;
}

.animate-pulse:nth-child(4) {
  animation-delay: 0.3s;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .animate-pulse {
    animation: none;
  }
}
</style>