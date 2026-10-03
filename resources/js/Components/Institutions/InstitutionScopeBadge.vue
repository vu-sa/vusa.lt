<template>
  <!--
    Which world a body belongs to changes what the rest of the page means — whether the
    representatives *are* the organisation, or are delegated into someone else's. It was
    only ever visible as fields quietly appearing and disappearing in the form.
  -->
  <Badge
    data-slot="institution-scope-badge"
    variant="outline"
    :class="cn('gap-1 text-xs', scope === InstitutionScope.Vusa
      ? 'border-brand/40 bg-brand/5 text-brand'
      : 'border-[#78003F]/40 bg-[#78003F]/10 text-[#78003F] dark:border-[#d99fbd]/40 dark:bg-[#78003F]/25 dark:text-[#d99fbd]', props.class)"
    :title="$t('forms.helpers.governance_scope_hint')"
  >
    <component :is="scope === InstitutionScope.Vusa ? Home : Landmark" class="size-3 shrink-0" />
    {{ label }}
  </Badge>
</template>

<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Home, Landmark } from 'lucide-vue-next';

import { Badge } from '@/Components/ui/badge';
import { InstitutionScope } from '@/Types/enums';
import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<{
  scope: InstitutionScope | string;
  class?: HTMLAttributes['class'];
}>();

/** Reuses the type form's option strings, so the badge and the editor never disagree. */
const label = computed(() => $t(`forms.options.governance_scope_${props.scope}`));
</script>
