<template>
  <div class="space-y-4">
    <div class="space-y-2">
      <Label for="document-select">Pasirinkite dokumentą</Label>
      <Combobox v-model="selectedDocument" by="label">
        <ComboboxAnchor as-child>
          <ComboboxTrigger as-child>
            <Button variant="outline" class="w-full justify-between">
              {{ selectedDocument?.label ?? 'Ieškokite dokumento...' }}
              <ChevronsUpDownIcon class="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </Button>
          </ComboboxTrigger>
        </ComboboxAnchor>

        <ComboboxList class="w-full">
          <div class="relative items-center">
            <ComboboxInput class="pl-9 focus-visible:ring-0 rounded-none h-10"
              placeholder="Ieškokite dokumento..." @input="handleSearch" />
            <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
              <SearchIcon class="size-4 text-muted-foreground" />
            </span>
          </div>

          <ComboboxEmpty>
            Dokumentų nerasta.
          </ComboboxEmpty>

          <ComboboxGroup>
            <ComboboxItem v-for="option in displayedOptions" :key="option.value" :value="option">
              <span class="truncate">{{ option.label }}</span>
              <ComboboxItemIndicator>
                <CheckIcon class="ml-auto h-4 w-4" />
              </ComboboxItemIndicator>
            </ComboboxItem>

            <div v-if="footerMessage" class="border-t border-border mx-1 mt-1 pt-1">
              <div class="px-2 py-1.5 text-xs text-muted-foreground">
                {{ footerMessage }}
              </div>
            </div>
          </ComboboxGroup>
        </ComboboxList>
      </Combobox>
    </div>
    <Button type="button" class="w-full" :disabled="!selectedDocument" @click="handleSubmit">
      Pridėti
    </Button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { ChevronsUpDown as ChevronsUpDownIcon, Check as CheckIcon, Search as SearchIcon } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import {
  Combobox,
  ComboboxAnchor,
  ComboboxInput,
  ComboboxItem,
  ComboboxItemIndicator,
  ComboboxList,
  ComboboxTrigger,
  ComboboxEmpty,
  ComboboxGroup
} from '@/Components/ui/combobox';

interface Document {
  title: string;
  anonymous_url: string;
}

interface DocumentOption {
  label: string;
  value: string;
}

const emit = defineEmits<{
  submit: [url: string];
}>();

const selectedDocument = ref<DocumentOption | null>(null);
const searchQuery = ref('');
const maxDisplayedResults = 5;

const options = await fetch(route('api.documents.index'))
  .then((response) => response.json())
  .then((data: Document[]) => data.map((document) => ({
    label: document.title,
    value: document.anonymous_url
  })));

const filteredOptions = computed(() => {
  if (!searchQuery.value.trim()) {
    return options;
  }

  const query = searchQuery.value.toLowerCase();
  return options.filter((option) =>
    option.label.toLowerCase().includes(query)
  );
});

const displayedOptions = computed(() => filteredOptions.value.slice(0, maxDisplayedResults));

const footerMessage = computed(() => {
  if (filteredOptions.value.length === 0) return '';
  if (filteredOptions.value.length <= maxDisplayedResults) return '';
  // If user is searching, clarify counts
  const searching = !!searchQuery.value.trim();
  return searching
    ? `Rasta ${filteredOptions.value.length} rezultatų (rodoma ${displayedOptions.value.length})`
    : `Rodoma ${displayedOptions.value.length} iš ${filteredOptions.value.length} rezultatų`;
});

function handleSearch(event: Event) {
  const target = event.target as HTMLInputElement;
  searchQuery.value = target.value;
}

function handleSubmit() {
  if (selectedDocument.value) {
    emit('submit', selectedDocument.value.value);
  }
}
</script>
