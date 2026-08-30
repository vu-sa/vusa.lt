<template>
  <div>
    <Tabs v-model="activeTab">
      <div class="flex items-center justify-between gap-2">
        <!-- h-auto + wrapping: the two labels are long enough to overflow a phone. -->
        <TabsList class="h-auto flex-wrap gap-1 rounded-lg bg-zinc-100/80 p-1 dark:bg-zinc-800/60">
          <TabsTrigger value="description" class="gap-1.5 text-xs">
            {{ $t('Aprašymas') }}
            <span v-if="hasDescription" class="h-1.5 w-1.5 rounded-full bg-primary" :title="$t('Užpildyta')" />
          </TabsTrigger>
          <TabsTrigger v-if="showStudentPosition" value="position" class="gap-1.5 text-xs">
            {{ $t('Išsakyta studentų pozicija') }}
            <span v-if="hasPosition" class="h-1.5 w-1.5 rounded-full bg-primary" :title="$t('Užpildyta')" />
          </TabsTrigger>
        </TabsList>
        <slot name="trailing" />
      </div>

      <!-- The same textarea in both modes, merely locked: swapping it for a paragraph
           made the page jump every time the edit toggle flipped. -->
      <TabsContent value="description" class="mt-4">
        <Textarea
          :model-value="description ?? ''"
          rows="5"
          :class="FIELD_CLASS"
          :readonly="!editable"
          :placeholder="editable ? $t('Aprašymas') : $t('Nenurodyta')"
          @update:model-value="(v) => emit('update:description', String(v))"
        />
      </TabsContent>

      <TabsContent v-if="showStudentPosition" value="position" class="mt-4">
        <Textarea
          :model-value="studentPosition ?? ''"
          rows="5"
          :class="FIELD_CLASS"
          :readonly="!editable"
          :placeholder="editable ? $t('Išsakyta studentų pozicija') : $t('Nenurodyta')"
          @update:model-value="(v) => emit('update:studentPosition', String(v))"
        />
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
  /**
   * False for VU SA's own bodies: the representatives *are* the organisation, so there is no
   * separate student position to state.
   */
  showStudentPosition?: boolean;
}>(), {
  editable: false,
  description: '',
  studentPosition: '',
  showStudentPosition: true,
});

const emit = defineEmits<{
  'update:description': [value: string];
  'update:studentPosition': [value: string];
}>();

/** These sit on a tinted card, so the writing surface needs its own ground. */
const FIELD_CLASS = 'bg-white dark:bg-zinc-950/40';

const activeTab = ref('description');

const hasDescription = computed(() => Boolean(props.description?.trim()));
const hasPosition = computed(() => Boolean(props.studentPosition?.trim()));
</script>
