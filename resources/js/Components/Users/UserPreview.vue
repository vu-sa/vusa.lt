<template>
  <PreviewPane
    entity-type="user"
    :eyebrow="$t('Narys')"
    :title="user.name || $t('Be pavadinimo')"
    :href="user.isDeleted ? undefined : showHref"
    :subtitle="user.email ?? undefined"
  >
    <template v-if="user.isDeleted" #badges>
      <StatusBadge :status="DELETED" />
    </template>

    <template v-if="$slots.media" #media>
      <slot name="media" />
    </template>

    <template #actions>
      <slot name="actions">
        <Button as-child variant="brand" size="sm">
          <Link :href="showHref">
            {{ $t('Atidaryti narį') }}
          </Link>
        </Button>
      </slot>
    </template>

    <PreviewFacts>
      <PreviewFact :label="$t('El. paštas')">
        <a v-if="user.email" :href="`mailto:${user.email}`" class="hover:text-brand">{{ user.email }}</a>
        <template v-else>
          —
        </template>
      </PreviewFact>
      <PreviewFact v-if="user.phone" :label="$t('Telefonas')" :value="user.phone" />
      <PreviewFact v-if="user.tenantShortname" :label="$t('Padalinys')" :value="user.tenantShortname" />
    </PreviewFacts>

    <PreviewSection v-if="user.currentDuties?.length" :title="$t('Pareigos')">
      <PreviewChipList :items="dutyChips(user.currentDuties)" :max="DUTY_LIMIT" :more-href="showHref" />
    </PreviewSection>

    <PreviewSection v-if="user.previousDuties?.length" :title="$t('Buvusios pareigos')">
      <PreviewChipList :items="dutyChips(user.previousDuties)" :max="DUTY_LIMIT" :more-href="showHref" />
    </PreviewSection>

    <slot />
  </PreviewPane>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { UserX } from '@lucide/vue';
import { computed } from 'vue';

import type { UserPreviewModel } from './userPreview';

import { PreviewChipList, PreviewFact, PreviewFacts, PreviewPane, PreviewSection, StatusBadge, type PreviewChipItem } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import type { StatusPresentation } from '@/Constants/statuses';

const DUTY_LIMIT = 8;
const DELETED: StatusPresentation = { label: 'Ištrintas', role: 'neutral', icon: UserX };

const props = defineProps<{
  user: UserPreviewModel;
}>();

const showHref = computed(() => route('users.show', props.user.id));

function dutyChips(duties: { id?: string; name: string }[]): PreviewChipItem[] {
  return duties.map(duty => ({ id: duty.id, name: duty.name, href: duty.id ? route('duties.show', duty.id) : undefined }));
}
</script>
