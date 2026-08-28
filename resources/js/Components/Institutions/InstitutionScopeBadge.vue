<template>
  <!--
    Which world a body belongs to changes what the rest of the page means — whether the
    representatives *are* the organisation, or are delegated into someone else's. It was
    only ever visible as fields quietly appearing and disappearing in the form.
  -->
  <Badge
    data-slot="institution-scope-badge"
    :variant="scope === InstitutionScope.Vusa ? 'secondary' : 'outline'"
    :class="cn('gap-1 text-[10px]', scope !== InstitutionScope.Vusa && 'border-sky-600/40 text-sky-700 dark:border-sky-400/40 dark:text-sky-300', props.class)"
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
