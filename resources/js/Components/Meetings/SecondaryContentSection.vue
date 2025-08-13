<template>
  <div class="space-y-4">
    <!-- Files Section -->
    <Collapsible v-model:open="showFiles" class="space-y-2">
      <div class="flex items-center justify-between">
        <CollapsibleTrigger asChild>
          <Button variant="ghost" class="flex items-center gap-2 p-0 h-auto font-semibold text-zinc-900 dark:text-zinc-100">
            <FileText class="h-5 w-5 text-blue-500" />
            {{ $t('Failai') }}
            <Badge variant="secondary" class="ml-2 text-xs">
              {{ filesCount }}
            </Badge>
            <ChevronDown 
              class="h-4 w-4 transition-transform" 
              :class="showFiles ? 'rotate-180' : ''" 
            />
          </Button>
        </CollapsibleTrigger>
        
        <Button v-if="showFiles" variant="outline" size="sm" @click="openFileManager">
          <Upload class="h-4 w-4 mr-2" />
          {{ $t('Valdyti failus') }}
        </Button>
      </div>
      
      <CollapsibleContent class="space-y-2">
        <Card v-if="filesCount > 0">
          <CardContent class="p-4">
            <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
              {{ $t('Posėdžio failai ir dokumentai') }}
            </div>
            
            <!-- File Preview Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
              <div 
                v-for="file in previewFiles" 
                :key="file.id"
                class="flex items-center gap-2 p-2 bg-zinc-50 dark:bg-zinc-800 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors cursor-pointer"
              >
                <component :is="getFileIcon(file.type)" class="h-4 w-4 text-zinc-500 flex-shrink-0" />
                <span class="text-xs text-zinc-700 dark:text-zinc-300 truncate">
                  {{ file.name }}
                </span>
              </div>
            </div>
            
            <Button 
              v-if="filesCount > 6" 
              variant="link" 
              size="sm" 
              class="mt-3 p-0 h-auto text-xs"
              @click="openFileManager"
            >
              {{ $t('Žiūrėti visus failus') }} (+{{ filesCount - 6 }})
            </Button>
          </CardContent>
        </Card>
        
        <Card v-else class="border-dashed">
          <CardContent class="p-6 text-center">
            <FileX class="h-8 w-8 text-zinc-400 mx-auto mb-2" />
            <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
              {{ $t('Failų nėra') }}
            </div>
            <Button variant="outline" size="sm" @click="openFileManager">
              <Upload class="h-4 w-4 mr-2" />
              {{ $t('Įkelti failus') }}
            </Button>
          </CardContent>
        </Card>
      </CollapsibleContent>
    </Collapsible>
    
    <!-- Tasks Section -->
    <Collapsible v-model:open="showTasks" class="space-y-2">
      <div class="flex items-center justify-between">
        <CollapsibleTrigger asChild>
          <Button variant="ghost" class="flex items-center gap-2 p-0 h-auto font-semibold text-zinc-900 dark:text-zinc-100">
            <CheckSquare class="h-5 w-5 text-orange-500" />
            {{ $t('Užduotys') }}
            <Badge variant="secondary" class="ml-2 text-xs">
              {{ tasksCount }}
            </Badge>
            <ChevronDown 
              class="h-4 w-4 transition-transform" 
              :class="showTasks ? 'rotate-180' : ''" 
            />
          </Button>
        </CollapsibleTrigger>
        
        <Button v-if="showTasks" variant="outline" size="sm" @click="openTaskManager">
          <Plus class="h-4 w-4 mr-2" />
          {{ $t('Pridėti užduotį') }}
        </Button>
      </div>
      
      <CollapsibleContent class="space-y-2">
        <Card v-if="tasksCount > 0">
          <CardContent class="p-4">
            <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
              {{ $t('Posėdžio užduotys ir veiksmai') }}
            </div>
            
            <!-- Tasks Preview -->
            <div class="space-y-2">
              <div 
                v-for="task in previewTasks" 
                :key="task.id"
                class="flex items-center gap-3 p-2 bg-zinc-50 dark:bg-zinc-800 rounded-lg"
              >
                <component 
                  :is="task.completed ? CheckCircle : Circle" 
                  :class="task.completed ? 'text-green-500' : 'text-zinc-400'"
                  class="h-4 w-4 flex-shrink-0" 
                />
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">
                    {{ task.title }}
                  </div>
                  <div class="text-xs text-zinc-600 dark:text-zinc-400">
                    {{ task.assignee ? `Priskirta: ${task.assignee.name}` : $t('Nepriskirta') }}
                  </div>
                </div>
                <Badge 
                  :variant="getTaskPriorityVariant(task.priority)" 
                  class="text-xs"
                >
                  {{ getTaskPriorityText(task.priority) }}
                </Badge>
              </div>
            </div>
            
            <Button 
              v-if="tasksCount > 3" 
              variant="link" 
              size="sm" 
              class="mt-3 p-0 h-auto text-xs"
              @click="openTaskManager"
            >
              {{ $t('Žiūrėti visas užduotis') }} (+{{ tasksCount - 3 }})
            </Button>
          </CardContent>
        </Card>
        
        <Card v-else class="border-dashed">
          <CardContent class="p-6 text-center">
            <Square class="h-8 w-8 text-zinc-400 mx-auto mb-2" />
            <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
              {{ $t('Užduočių nėra') }}
            </div>
            <Button variant="outline" size="sm" @click="openTaskManager">
              <Plus class="h-4 w-4 mr-2" />
              {{ $t('Sukurti užduotį') }}
            </Button>
          </CardContent>
        </Card>
      </CollapsibleContent>
    </Collapsible>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useStorage } from '@vueuse/core'
import { trans as $t } from 'laravel-vue-i18n'

// UI Components
import { Card, CardContent } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible'

// Icons
import { 
  FileText, 
  FileX, 
  Upload, 
  CheckSquare, 
  Plus, 
  ChevronDown, 
  CheckCircle, 
  Circle, 
  Square,
  FileImage,
  FileCode,
  FileSpreadsheet,
  File
} from 'lucide-vue-next'

interface Props {
  meeting: App.Entities.Meeting
}

interface Emits {
  (e: 'openFileManager'): void
  (e: 'openTaskManager'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Persistent state for sections
const showFiles = useStorage('meeting-show-files', false)
const showTasks = useStorage('meeting-show-tasks', false)

const filesCount = computed(() => props.meeting.files?.length || 0)
const tasksCount = computed(() => props.meeting.tasks?.length || 0)

const previewFiles = computed(() => {
  if (!props.meeting.files) return []
  return props.meeting.files.slice(0, 6)
})

const previewTasks = computed(() => {
  if (!props.meeting.tasks) return []
  return props.meeting.tasks.slice(0, 3)
})

const getFileIcon = (fileType: string) => {
  const type = fileType?.toLowerCase() || ''
  
  if (type.includes('image')) return FileImage
  if (type.includes('code') || type.includes('text')) return FileCode
  if (type.includes('sheet') || type.includes('excel')) return FileSpreadsheet
  
  return File
}

const getTaskPriorityVariant = (priority: string) => {
  switch (priority?.toLowerCase()) {
    case 'high':
      return 'destructive'
    case 'medium':
      return 'default'
    case 'low':
      return 'secondary'
    default:
      return 'outline'
  }
}

const getTaskPriorityText = (priority: string) => {
  switch (priority?.toLowerCase()) {
    case 'high':
      return $t('Aukštas')
    case 'medium':
      return $t('Vidutinis')
    case 'low':
      return $t('Žemas')
    default:
      return $t('Nepriskirtas')
  }
}

const openFileManager = () => {
  emit('openFileManager')
}

const openTaskManager = () => {
  emit('openTaskManager')
}
</script>