<template>
  <OverviewSection
    :title="$t('representatives.attention.title')"
    :icon="UserRoundSearch"
    variant="home"
    :empty="reps.length === 0"
    :empty-text="$t('representatives.attention.empty')"
    data-slot="representative-attention"
  >
    <ul class="divide-y divide-border/60">
      <li v-for="rep in visible" :key="rep.id" class="flex flex-wrap items-center gap-x-4 gap-y-2 py-3" :data-rep="rep.id">
        <UserAvatar :user="{ name: rep.name, profile_photo_path: rep.photo }" :size="36" class="shrink-0" />
        <span class="min-w-0 flex-1 basis-48">
          <Link :href="route('representatives.show', rep.id)" class="block text-pretty font-bold hover:text-brand">
            {{ rep.name }}
          </Link>
          <span :class="['block text-sm', reasonClass(rep)]" data-slot="attention-reason">{{ attentionText(rep) }}</span>
          <span v-if="rep.attention?.reason !== 'institution_overdue' && rep.institutions.length" class="block truncate text-xs text-muted-foreground">
            {{ institutionNames(rep) }}
          </span>
        </span>
        <Button as="a" :href="`mailto:${rep.email}`" variant="outline" size="sm" voice="sentence" class="pointer-coarse:h-11">
          <Mail aria-hidden="true" />
          {{ $t('representatives.attention.write') }}
        </Button>
      </li>
    </ul>
    <Button
      v-if="reps.length > limit"
      variant="ghost"
      size="sm"
      voice="sentence"
      class="-ml-3 self-start pointer-coarse:h-11"
      data-slot="representative-attention-more"
      @click="emit('showAll')"
    >
      <List aria-hidden="true" />
      {{ $t('representatives.attention.more', { count: String(reps.length) }) }}
    </Button>
  </OverviewSection>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { List, Mail, UserRoundSearch } from '@lucide/vue';
import { computed } from 'vue';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { OverviewSection } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';

import { attentionText, institutionNames } from './representativeText';
import type { RepRow } from './types';

const props = withDefaults(defineProps<{
  /** Already ordered by how much each needs a coordinator. */
  reps: RepRow[];
  limit?: number;
}>(), { limit: 5 });

const emit = defineEmits<{
  showAll: [];
}>();

const visible = computed(() => props.reps.slice(0, props.limit));

// Not reaching the system and a late institution are the two a coordinator should act on first.
function reasonClass(rep: RepRow): string {
  return rep.attention?.reason === 'never_logged_in' || rep.attention?.reason === 'institution_overdue'
    ? 'text-status-danger'
    : 'text-status-attention';
}
</script>
