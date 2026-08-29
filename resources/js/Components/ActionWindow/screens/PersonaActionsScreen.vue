<template>
  <ActionWindowScreen :title="persona?.title ?? ''" :subtitle="persona?.description">
    <ActionChoiceList>
      <ActionChoiceButton
        v-for="action in persona?.actions ?? []"
        :key="action.key"
        :title="action.title"
        :description="action.description"
        :icon="action.icon"
        :gradient="action.gradient"
        @click="run(action)"
      />
    </ActionChoiceList>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

import ActionChoiceButton from '../ActionChoiceButton.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';

import { useActionWindow } from '@/Composables/useActionWindow';
import { useActionWindowCatalog, type ActionWindowAction, type PersonaKey } from '@/Composables/useActionWindowCatalog';

const props = defineProps<{ params?: Record<string, unknown> }>();

const { goTo, close } = useActionWindow();
const { findPersona } = useActionWindowCatalog();

const persona = computed(() => findPersona(props.params?.persona as PersonaKey));

const run = (action: ActionWindowAction) => {
  if (action.target.kind === 'screen') {
    goTo(action.target.screen);
    return;
  }

  close();
  router.visit(route(action.target.route));
};
</script>
