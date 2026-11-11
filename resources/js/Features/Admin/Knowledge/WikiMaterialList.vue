<script setup lang="ts">
import { computed, ref } from 'vue';
import { ChevronDown } from '@lucide/vue';

import { Button } from '@/Components/ui/button';
import type { WikiMaterial } from '@/Types/learning';

import WikiPageList from './WikiPageList.vue';

const props = defineProps<{ materials: WikiMaterial[]; doneIds?: ReadonlySet<string> }>();

// Material from the viewer's own units leads; the rest (e.g. 14 other faculties' regulations) waits behind one button.
const OTHERS_PREVIEW = 3;
const mine = computed(() => props.materials.filter(material => material.mine));
const others = computed(() => props.materials.filter(material => !material.mine));
const showAll = ref(false);
const collapsed = computed(() => mine.value.length > 0 && others.value.length > OTHERS_PREVIEW && !showAll.value);
const unitOf = (page: { id: string }) => props.materials.find(material => material.id === page.id)?.tenant ?? null;
</script>

<template>
  <div class="space-y-4" data-slot="wiki-materials">
    <WikiPageList v-if="mine.length" :pages="mine" :section-of="unitOf" :done-ids="doneIds" />
    <div v-if="others.length" class="space-y-2">
      <h3 v-if="mine.length" class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">{{ $t('learning.wiki.materials_other_units') }}</h3>
      <WikiPageList v-if="!collapsed" :pages="others" :section-of="unitOf" :done-ids="doneIds" dense />
      <Button v-else type="button" variant="ghost" voice="sentence" size="sm" class="-ml-3" data-testid="wiki-materials-more" @click="showAll = true">
        {{ $t('learning.wiki.section_more', { count: String(others.length) }) }}<ChevronDown class="size-4" aria-hidden="true" />
      </Button>
    </div>
  </div>
</template>
