<template>
  <div class="space-y-4" data-document-selector>
    <div class="space-y-2">
      <Label for="document-search">Pasirinkite dokumentą</Label>
      
      <!-- Simple search input -->
      <div class="relative">
        <Input
          id="document-search"
          v-model="searchQuery"
          type="text"
          placeholder="Ieškokite dokumento..."
          class="pr-10"
          @input="handleSearch"
        />
        <SearchIcon class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
      </div>

      <!-- Simple dropdown list -->
      <div 
        v-if="showResults && (documents.length > 0 || isLoading || searchQuery.length > 0)"
        class="max-h-60 overflow-y-auto rounded-md border bg-popover text-popover-foreground shadow-md"
      >
        <!-- Loading state -->
        <div v-if="isLoading" class="flex items-center gap-2 p-3 text-sm text-muted-foreground">
          <div class="h-4 w-4 animate-spin rounded-full border-2 border-muted border-r-transparent"></div>
          Ieškoma...
        </div>

        <!-- No results -->
        <div v-else-if="documents.length === 0 && searchQuery.length > 0" class="p-3 text-sm text-muted-foreground">
          Dokumentų nerasta.
        </div>

        <!-- Search hint -->
        <div v-else-if="documents.length === 0 && searchQuery.length === 0" class="p-3 text-sm text-muted-foreground">
          Pradėkite rašyti, kad ieškoti dokumentų...
        </div>

        <!-- Results -->
        <div v-else class="py-1">
          <button
            v-for="document in documents"
            :key="document.id"
            @click="selectDocument(document)"
            class="flex w-full items-center px-3 py-2 text-left text-sm hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground focus:outline-none"
          >
            <span class="truncate">{{ document.title }}</span>
          </button>
          
          <!-- Show more hint if we hit the limit -->
          <div v-if="documents.length >= 20" class="border-t px-3 py-2 text-xs text-muted-foreground">
            Rodoma 20 rezultatų. Patikslinkite paiešką daugiau rezultatų.
          </div>
        </div>
      </div>
    </div>

    <!-- Selected document display -->
    <div v-if="selectedDocument" class="rounded-md border bg-muted/50 p-3">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium">Pasirinktas dokumentas:</p>
          <p class="text-sm text-muted-foreground">{{ selectedDocument.title }}</p>
        </div>
        <Button variant="ghost" size="sm" @click="clearSelection">
          <XIcon class="h-4 w-4" />
        </Button>
      </div>
    </div>

    <!-- Submit button -->
    <Button type="button" class="w-full" :disabled="!selectedDocument" @click="handleSubmit">
      Pridėti
    </Button>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Search as SearchIcon, X as XIcon } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

interface Document {
  id: number;
  title: string;
  anonymous_url: string;
}

const emit = defineEmits<{
  submit: [url: string];
}>();

const searchQuery = ref('');
const selectedDocument = ref<Document | null>(null);
const documents = ref<Document[]>([]);
const isLoading = ref(false);
const showResults = ref(false);

let searchTimeout: NodeJS.Timeout | null = null;

// Debounced search function
async function performSearch(query: string) {
  if (query.length < 2) {
    documents.value = [];
    isLoading.value = false;
    return;
  }

  isLoading.value = true;
  
  try {
    const response = await fetch(`${route('api.documents.index')}?search=${encodeURIComponent(query)}&limit=20`);
    if (response.ok) {
      const data = await response.json();
      documents.value = data;
    } else {
      console.error('Search failed:', response.statusText);
      documents.value = [];
    }
  } catch (error) {
    console.error('Search error:', error);
    documents.value = [];
  } finally {
    isLoading.value = false;
  }
}

function handleSearch() {
  showResults.value = true;
  
  // Clear existing timeout
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }

  // Debounce search by 300ms
  searchTimeout = setTimeout(() => {
    performSearch(searchQuery.value);
  }, 300);
}

function selectDocument(document: Document) {
  selectedDocument.value = document;
  showResults.value = false;
  searchQuery.value = document.title;
}

function clearSelection() {
  selectedDocument.value = null;
  searchQuery.value = '';
  documents.value = [];
  showResults.value = false;
}

function handleSubmit() {
  if (selectedDocument.value) {
    emit('submit', selectedDocument.value.anonymous_url);
  }
}

// Close dropdown when clicking outside
function handleClickOutside(event: Event) {
  const target = event.target as HTMLElement;
  if (!target.closest('[data-document-selector]')) {
    showResults.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  // Clean up event listener and timeout
  document.removeEventListener('click', handleClickOutside);
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }
});
</script>