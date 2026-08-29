<template>
  <ActionWindowScreen centered :title="$t('action_window.personas.title')">
    <ActionChoiceList>
      <ActionChoiceButton
        v-for="persona in personas"
        :key="persona.key"
        :title="persona.title"
        :description="persona.description"
        :icon="persona.icon"
        :gradient="persona.gradient"
        @click="goTo('persona.actions', { persona: persona.key })"
      />
    </ActionChoiceList>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';

import ActionChoiceButton from '../ActionChoiceButton.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';

import { useActionWindow } from '@/Composables/useActionWindow';
import { useActionWindowCatalog } from '@/Composables/useActionWindowCatalog';

const { goTo, replace } = useActionWindow();
const { personas } = useActionWindowCatalog();

// A menu with one entry is a wasted tap. Replace rather than push, so the
// actions screen becomes the root and its back arrow stays hidden.
onMounted(() => {
  if (personas.value.length === 1) {
    replace('persona.actions', { persona: personas.value[0]!.key });
  }
});
</script>
