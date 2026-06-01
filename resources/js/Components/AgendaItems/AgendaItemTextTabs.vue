<template>
  <div>
    <Tabs v-model="activeTab">
      <div class="flex items-center justify-between gap-2">
        <TabsList class="h-9 gap-1 rounded-lg bg-zinc-100/80 p-1 dark:bg-zinc-800/60">
          <TabsTrigger value="description" class="gap-1.5 text-xs">
            {{ $t('Aprašymas') }}
            <span v-if="hasDescription" class="h-1.5 w-1.5 rounded-full bg-primary" :title="$t('Užpildyta')" />
          </TabsTrigger>
          <TabsTrigger value="position" class="gap-1.5 text-xs">
            {{ $t('Išsakyta studentų pozicija') }}
            <span v-if="hasPosition" class="h-1.5 w-1.5 rounded-full bg-primary" :title="$t('Užpildyta')" />
          </TabsTrigger>
        </TabsList>
        <slot name="trailing" />
      </div>

      <TabsContent value="description" class="mt-3">
        <Textarea
          v-if="editable"
          :model-value="description ?? ''"
          rows="4"
          :placeholder="$t('Aprašymas')"
          @update:model-value="(v) => emit('update:description', String(v))"
        />
        <p v-else-if="hasDescription" class="whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">
          {{ description }}
        </p>
        <p v-else class="text-sm italic text-muted-foreground">
          {{ $t('Nenurodyta') }}
        </p>
      </TabsContent>

      <TabsContent value="position" class="mt-3">
        <Textarea
          v-if="editable"
          :model-value="studentPosition ?? ''"
          rows="4"
          :placeholder="$t('Išsakyta studentų pozicija')"
          @update:model-value="(v) => emit('update:studentPosition', String(v))"
        />
        <p v-else-if="hasPosition" class="whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">
          {{ studentPosition }}
        </p>
        <p v-else class="text-sm italic text-muted-foreground">
          {{ $t('Nenurodyta') }}
        </p>
      </TabsContent>
    </Tabs>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Textarea } from '@/Components/ui/textarea';

const props = withDefaults(defineProps<{
  editable?: boolean;
  description?: string | null;
  studentPosition?: string | null;
}>(), {
  editable: false,
  description: '',
  studentPosition: '',
});

const emit = defineEmits<{
  'update:description': [value: string];
  'update:studentPosition': [value: string];
}>();

const activeTab = ref('description');

const hasDescription = computed(() => Boolean(props.description?.trim()));
const hasPosition = computed(() => Boolean(props.studentPosition?.trim()));
</script>
