<template>
  <div class="grid gap-10 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] xl:gap-16" data-slot="mailing-list-sources">
    <OverviewSection variant="home" :title="$t('mailing_lists.sections.sources')" :icon="Layers">
      <template v-if="canUpdate" #actions>
        <Button size="sm" variant="outline" voice="sentence" class="u-touch" data-testid="source-add" @click="openSheet">
          <Plus class="size-4" aria-hidden="true" />
          {{ $t('mailing_lists.sources.add') }}
        </Button>
      </template>

      <p class="text-xs text-muted-foreground">
        {{ $t('mailing_lists.sources.intro') }}
      </p>
      <ul v-if="sources.length" class="divide-y divide-border">
        <li v-for="source in sources" :key="source.id" class="flex min-h-11 items-center justify-between gap-3 py-2" data-testid="source-row">
          <span class="min-w-0">
            <span :class="['block text-sm font-medium', source.is_expired ? 'text-muted-foreground line-through' : 'text-foreground']">
              {{ source.label }}
            </span>
            <span class="block text-xs text-muted-foreground">{{ describe(source) }}</span>
          </span>
          <Button
            v-if="canUpdate"
            variant="ghost"
            size="sm"
            voice="sentence"
            class="pointer-coarse:h-11"
            @click="removeTarget = source"
          >
            {{ $t('mailing_lists.sources.remove') }}
          </Button>
        </li>
      </ul>
      <EmptyState
        v-else
        :icon="Layers"
        :title="$t('mailing_lists.sources.empty_title')"
        :description="$t('mailing_lists.sources.empty_description')"
        :action-label="canUpdate ? $t('mailing_lists.sources.add') : undefined"
        @action="openSheet"
      />
    </OverviewSection>

    <OverviewSection variant="home" :title="$t('mailing_lists.sections.exclusions')" :icon="UserX">
      <p class="text-xs text-muted-foreground">
        {{ $t('mailing_lists.exclusions.intro') }}
      </p>
      <ul v-if="exclusions.length" class="divide-y divide-border">
        <li v-for="exclusion in exclusions" :key="exclusion.id" class="flex min-h-11 items-center justify-between gap-3 py-2">
          <span class="min-w-0">
            <span class="block truncate text-sm">{{ exclusion.email }}</span>
            <span v-if="exclusion.note" class="block text-xs text-muted-foreground">{{ exclusion.note }}</span>
          </span>
          <Button
            v-if="canUpdate"
            variant="ghost"
            size="sm"
            voice="sentence"
            class="pointer-coarse:h-11"
            @click="removeExclusion(exclusion.id)"
          >
            {{ $t('mailing_lists.exclusions.remove') }}
          </Button>
        </li>
      </ul>
      <p v-else class="text-sm text-muted-foreground">
        {{ $t('mailing_lists.exclusions.empty') }}
      </p>
      <form v-if="canUpdate" class="flex items-end gap-2" @submit.prevent="addExclusion">
        <FormFieldWrapper
          id="mailing-list-exclusion" :label="$t('mailing_lists.sheet.email')" :error="exclusionForm.errors.email" class="min-w-0 flex-1"
        >
          <Input id="mailing-list-exclusion" v-model="exclusionForm.email" type="email" />
        </FormFieldWrapper>
        <Button type="submit" variant="outline" voice="sentence" class="u-touch" :disabled="!exclusionForm.email || exclusionForm.processing">
          {{ $t('mailing_lists.exclusions.add') }}
        </Button>
      </form>
    </OverviewSection>

    <MailingListSourceSheet v-model:open="sheetOpen" :mailing-list-id :options="sourceOptions" :load-failed="sourceOptionsFailed" @retry="loadOptions" />

    <ConfirmDialog
      :open="removeTarget !== null"
      :title="$t('mailing_lists.sources.remove_confirm', { name: removeTarget?.label ?? '' })"
      :confirm-label="$t('mailing_lists.sources.remove')"
      destructive
      @update:open="!$event && (removeTarget = null)"
      @confirm="removeSource"
    />
  </div>
</template>

<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Layers, Plus, UserX } from '@lucide/vue';
import { ref } from 'vue';

import MailingListSourceSheet from './MailingListSourceSheet.vue';
import type { MailingListExclusionItem, MailingListSourceItem, MailingListSourceOptions } from './types';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { ConfirmDialog, EmptyState, OverviewSection } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { formatDate } from '@/Utils/dateTime';

const props = defineProps<{
  mailingListId: string;
  sources: MailingListSourceItem[];
  exclusions: MailingListExclusionItem[];
  canUpdate: boolean;
  sourceOptions?: MailingListSourceOptions | null;
}>();

const sheetOpen = ref(false);
const sourceOptionsFailed = ref(false);
const removeTarget = ref<MailingListSourceItem | null>(null);
const exclusionForm = useForm({ email: '' });
const reload = { preserveScroll: true, except: ['messages'] };

function describe(source: MailingListSourceItem): string {
  const parts = [$t(`mailing_lists.sources.types.${source.type}`)];

  if (source.address_mode) {
    parts.push($t(`mailing_lists.sources.address_modes.${source.address_mode}`));
  }
  if (source.tenant_scope !== 'all' && source.tenant_scope !== 'selected') {
    parts.push($t(`mailing_lists.sources.tenant_scopes.${source.tenant_scope}`));
  }
  if (source.tenants.length) {
    parts.push(source.tenants.join(', '));
  }
  if (source.excluded_types.length) {
    parts.push(`${$t('mailing_lists.sources.excluded_types')}: ${source.excluded_types.join(', ')}`);
  }
  if (source.duty_types.length) {
    parts.push(`${$t('mailing_lists.sources.duty_types')}: ${source.duty_types.join(', ')}`);
  }
  if (source.is_expired) {
    parts.push($t('mailing_lists.sources.expired'));
  }
  else if (source.expires_on) {
    parts.push($t('mailing_lists.sources.expires', { date: formatDate(source.expires_on) }));
  }
  if (source.note) {
    parts.push(source.note);
  }

  return parts.join(' · ');
}

function openSheet(): void {
  sheetOpen.value = true;
  if (!props.sourceOptions) loadOptions();
}

function loadOptions(): void {
  sourceOptionsFailed.value = false;
  router.reload({
    only: ['sourceOptions'],
    onFinish: () => {
      sourceOptionsFailed.value = !props.sourceOptions;
    },
  });
}

function removeSource(): void {
  const target = removeTarget.value;
  removeTarget.value = null;

  if (target) {
    router.delete(route('mailingLists.sources.destroy', { mailingList: props.mailingListId, source: target.id }), reload);
  }
}

function addExclusion(): void {
  exclusionForm.post(route('mailingLists.exclusions.store', props.mailingListId), {
    ...reload,
    onSuccess: () => exclusionForm.reset(),
  });
}

function removeExclusion(id: string): void {
  router.delete(route('mailingLists.exclusions.destroy', { mailingList: props.mailingListId, exclusion: id }), reload);
}
</script>
