<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Mail, Monitor, Smartphone } from '@lucide/vue';
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import FormSegmentedControl from '@/Components/Patterns/FormSegmentedControl.vue';
import { EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

interface Preview {
  key: string;
  label: string;
  group: string;
  subjects: Record<'lt' | 'en', string>;
}

const props = defineProps<{ previews: Preview[] }>();
const selectedKey = ref(props.previews[0]?.key ?? '');
const search = ref('');
const width = ref<390 | 640>(640);
const format = ref<'html' | 'text'>('html');
const locale = ref<'lt' | 'en'>('lt');
const selected = computed(() => props.previews.find(preview => preview.key === selectedKey.value));
const src = computed(() => selected.value ? route('mailPreviews.render', {
  preview: selected.value.key, format: format.value, locale: locale.value,
}) : undefined);
const groups = computed(() => {
  const result = new Map<string, Preview[]>();
  const query = search.value.trim().toLocaleLowerCase();
  for (const preview of props.previews) {
    if (query && ![$t(preview.label), $t(preview.group), preview.subjects[locale.value]]
      .some(value => value.toLocaleLowerCase().includes(query))) continue;
    result.set(preview.group, [...(result.get(preview.group) ?? []), preview]);
  }
  return result;
});
</script>

<template>
  <div class="flex min-w-0 flex-col" data-slot="workbench">
    <Head :title="$t('mail_previews.title')" />
    <header class="flex flex-wrap items-center justify-between gap-4 border-b border-border py-5">
      <div>
        <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-brand">
          <Mail class="size-4" aria-hidden="true" />{{ $t('shell.workspaces.sistema.title') }}
        </p>
        <h1 class="u-display mt-2 text-3xl text-foreground">{{ $t('mail_previews.title') }}</h1>
        <p class="mt-2 text-sm text-muted-foreground">{{ $t('mail_previews.lead') }}</p>
      </div>
      <Button variant="outline" voice="sentence" as-child>
        <Link :href="route('mailQueue')">{{ $t('shell.sections.laisku_eile') }}</Link>
      </Button>
    </header>
    <div class="grid min-w-0 lg:grid-cols-[17rem_minmax(0,1fr)]">
      <aside class="min-w-0 border-b border-border py-4 lg:border-r lg:border-b-0 lg:pr-4">
        <Input v-model="search" :aria-label="$t('mail_previews.search')" :placeholder="$t('mail_previews.search')" type="search" />
        <div class="mt-4 max-h-72 overflow-y-auto lg:max-h-[calc(100vh-18rem)]">
          <section v-for="[group, items] in groups" :key="group" class="mb-4">
            <h2 class="px-2 py-2 text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ $t(group) }}</h2>
            <button
              v-for="preview in items" :key="preview.key" type="button"
              :data-preview="preview.key" :aria-pressed="selectedKey === preview.key"
              :class="['min-h-11 w-full border-l-2 px-3 py-2 text-left text-sm hover:bg-accent focus-visible:outline-2 focus-visible:outline-brand', selectedKey === preview.key ? 'border-brand bg-accent font-semibold' : 'border-transparent']"
              @click="selectedKey = preview.key"
            >{{ $t(preview.label) }}</button>
          </section>
          <EmptyState v-if="groups.size === 0" :title="$t('mail_previews.no_results')" />
        </div>
      </aside>
      <section class="min-w-0 py-4 lg:pl-5" :aria-label="$t('mail_previews.preview')">
        <div class="flex flex-wrap gap-3 border-b border-border pb-4">
          <FormSegmentedControl v-model="width" class="w-full sm:w-auto" :aria-label="$t('mail_previews.width')" test-id-prefix="width" :options="[
            { value: 390, label: $t('mail_previews.phone'), icon: Smartphone },
            { value: 640, label: $t('mail_previews.desktop'), icon: Monitor },
          ]" />
          <FormSegmentedControl v-model="format" class="w-full sm:w-auto" :aria-label="$t('mail_previews.format')" test-id-prefix="format" :options="[
            { value: 'html', label: $t('mail_previews.html') }, { value: 'text', label: $t('mail_previews.text') },
          ]" />
          <FormSegmentedControl v-model="locale" class="w-full sm:w-auto" :aria-label="$t('mail_previews.locale')" test-id-prefix="locale" :options="[
            { value: 'lt', label: $t('mail_previews.lt') }, { value: 'en', label: $t('mail_previews.en') },
          ]" />
        </div>
        <template v-if="selected">
          <p class="break-words py-4 text-sm font-semibold" data-testid="subject">{{ selected.subjects[locale] }}</p>
          <div class="max-w-full overflow-x-auto border border-border bg-secondary p-2">
            <iframe :src="src" :title="$t('mail_previews.preview')" sandbox="" referrerpolicy="no-referrer" :style="{ width: `${width}px` }" class="mx-auto block h-[70vh] min-h-96 border-0 bg-background" />
          </div>
        </template>
        <EmptyState v-else :title="$t('mail_previews.select')" />
      </section>
    </div>
  </div>
</template>
