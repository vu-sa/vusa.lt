<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { ClipboardList } from 'lucide-vue-next'

// Task count from auth user data
const taskCount = computed(() => usePage().props.auth?.user.tasks_count || 0)
const loading = ref(false)

const handleClick = () => {
  loading.value = true
  router.visit(route("userTasks"), {
    preserveState: true,
    onSuccess: () => {
      loading.value = false
    },
  })
}
</script>

<template>
  <div class="relative">
    <Badge 
      v-if="taskCount > 0" 
      class="absolute -right-1 -top-1 z-10"
      variant="destructive"
    >
      {{ taskCount }}
    </Badge>
    <Button 
      variant="ghost" 
      size="icon" 
      class="relative" 
      :disabled="loading" 
      @click="handleClick"
    >
      <ClipboardList :class="['size-5', loading ? 'animate-pulse' : '']" />
      <span class="sr-only">{{ $t('Tasks') }}</span>
    </Button>
  </div>
</template>