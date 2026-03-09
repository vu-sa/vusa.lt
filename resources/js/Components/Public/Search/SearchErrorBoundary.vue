<template>
  <div class="search-error-boundary">
    <!-- Normal content when no error -->
    <slot v-if="!hasError" />
    
    <!-- Error states -->
    <div v-else class="error-state p-6 text-center">
      <!-- Network Error -->
      <div v-if="error?.type === 'network'" class="max-w-md mx-auto">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-orange-100 dark:bg-orange-950/30 flex items-center justify-center">
          <WifiOff class="w-8 h-8 text-orange-600 dark:text-orange-400" />
        </div>
        <h3 class="text-lg font-semibold text-foreground mb-2">
          {{ isOnline ? 'Ryšio problema' : 'Nėra interneto ryšio' }}
        </h3>
        <p class="text-muted-foreground mb-4">
          {{ error.message }}
        </p>
        <div class="flex gap-2 justify-center">
          <Button 
            v-if="error.retryable" 
            @click="$emit('retry')"
            :disabled="isRetrying"
          >
            <RefreshCw :class="['w-4 h-4 mr-2', { 'animate-spin': isRetrying }]" />
            Bandyti dar kartą
          </Button>
          <Button variant="outline" @click="$emit('clearError')">
            <X class="w-4 h-4 mr-2" />
            Užverti
          </Button>
        </div>
      </div>

      <!-- Server Error -->
      <div v-else-if="error?.type === 'server'" class="max-w-md mx-auto">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-950/30 flex items-center justify-center">
          <ServerCrash class="w-8 h-8 text-red-600 dark:text-red-400" />
        </div>
        <h3 class="text-lg font-semibold text-foreground mb-2">
          Serverio klaida
        </h3>
        <p class="text-muted-foreground mb-4">
          {{ error.message }}
        </p>
        <div class="flex gap-2 justify-center">
          <Button 
            v-if="error.retryable && retryCount < maxRetries" 
            @click="$emit('retry')"
            :disabled="isRetrying"
          >
            <RefreshCw :class="['w-4 h-4 mr-2', { 'animate-spin': isRetrying }]" />
            Bandyti dar kartą ({{ retryCount + 1 }}/{{ maxRetries }})
          </Button>
          <Button variant="outline" @click="$emit('clearError')">
            <X class="w-4 h-4 mr-2" />
            Užverti
          </Button>
        </div>
      </div>

      <!-- Timeout Error -->
      <div v-else-if="error?.type === 'timeout'" class="max-w-md mx-auto">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-yellow-100 dark:bg-yellow-950/30 flex items-center justify-center">
          <Clock class="w-8 h-8 text-yellow-600 dark:text-yellow-400" />
        </div>
        <h3 class="text-lg font-semibold text-foreground mb-2">
          Paieška užtruko per ilgai
        </h3>
        <p class="text-muted-foreground mb-4">
          {{ error.message }}
        </p>
        <div class="flex gap-2 justify-center">
          <Button @click="$emit('retry')" :disabled="isRetrying">
            <RefreshCw :class="['w-4 h-4 mr-2', { 'animate-spin': isRetrying }]" />
            Bandyti dar kartą
          </Button>
          <Button variant="outline" @click="$emit('clearError')">
            <X class="w-4 h-4 mr-2" />
            Užverti
          </Button>
        </div>
      </div>

      <!-- Client Error -->
      <div v-else-if="error?.type === 'client'" class="max-w-md mx-auto">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-blue-100 dark:bg-blue-950/30 flex items-center justify-center">
          <AlertTriangle class="w-8 h-8 text-blue-600 dark:text-blue-400" />
        </div>
        <h3 class="text-lg font-semibold text-foreground mb-2">
          Paieškos klaida
        </h3>
        <p class="text-muted-foreground mb-4">
          {{ error.message }}
        </p>
        <div class="flex gap-2 justify-center">
          <Button 
            v-if="error.retryable" 
            @click="$emit('retry')"
            :disabled="isRetrying"
          >
            <RefreshCw :class="['w-4 h-4 mr-2', { 'animate-spin': isRetrying }]" />
            Bandyti dar kartą
          </Button>
          <Button variant="outline" @click="$emit('clearError')">
            <X class="w-4 h-4 mr-2" />
            Užverti
          </Button>
        </div>
      </div>

      <!-- Generic Error -->
      <div v-else class="max-w-md mx-auto">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
          <AlertCircle class="w-8 h-8 text-gray-600 dark:text-gray-400" />
        </div>
        <h3 class="text-lg font-semibold text-foreground mb-2">
          Nežinoma klaida
        </h3>
        <p class="text-muted-foreground mb-4">
          Įvyko nenumatyta klaida. Pabandykite atnaujinti puslapį.
        </p>
        <div class="flex gap-2 justify-center">
          <Button @click="$emit('retry')" :disabled="isRetrying">
            <RefreshCw :class="['w-4 h-4 mr-2', { 'animate-spin': isRetrying }]" />
            Bandyti dar kartą
          </Button>
          <Button variant="outline" @click="$emit('clearError')">
            <X class="w-4 h-4 mr-2" />
            Užverti
          </Button>
        </div>
      </div>

      <!-- Debug info in development -->
      <details v-if="isDevelopment && error" class="mt-6 text-left max-w-2xl mx-auto">
        <summary class="text-sm text-muted-foreground cursor-pointer hover:text-foreground">
          Techninė informacija
        </summary>
        <div class="mt-2 p-3 bg-muted rounded-md text-xs font-mono">
          <div><strong>Tipo:</strong> {{ error.type }}</div>
          <div><strong>Kodas:</strong> {{ error.code || 'N/A' }}</div>
          <div><strong>Laikas:</strong> {{ error.timestamp.toLocaleString() }}</div>
          <div><strong>Retry:</strong> {{ error.retryable ? 'Taip' : 'Ne' }}</div>
          <div class="mt-2">
            <strong>Žinutė:</strong><br>
            {{ error.message }}
          </div>
        </div>
      </details>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/Components/ui/button'
import { 
  WifiOff, 
  ServerCrash, 
  Clock, 
  AlertTriangle, 
  AlertCircle, 
  RefreshCw, 
  X 
} from 'lucide-vue-next'
import type { SearchError } from '@/Shared/Search/types'

interface Props {
  error: SearchError | null
  isOnline?: boolean
  isRetrying?: boolean
  retryCount?: number
  maxRetries?: number
}

interface Emits {
  (e: 'retry'): void
  (e: 'clearError'): void
}

const props = withDefaults(defineProps<Props>(), {
  isOnline: true,
  isRetrying: false,
  retryCount: 0,
  maxRetries: 3
})

const emit = defineEmits<Emits>()

const hasError = computed(() => !!props.error)

const isDevelopment = computed(() => {
  return import.meta.env.DEV || process.env.NODE_ENV === 'development'
})
</script>

<style scoped>
.error-state {
  min-height: 300px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
