<template>
  <NButton tertiary circle @click="showModal = true"
    ><template #icon
      ><NIcon :component="DocumentOnePage24Regular"></NIcon></template
  ></NButton>
  <NModal
    v-model:show="showModal"
    class="prose-sm prose max-w-xl dark:prose-invert"
    title="Įrašo pokyčiai"
    :bordered="false"
    size="large"
    role="card"
    aria-modal="true"
    preset="card"
  >
    <template v-if="activities.length > 0">
      <div v-for="activity in activities" :key="activity.id">
        <div v-if="activity.description === 'created'">
          <p class="my-0">
            <span class="font-bold">{{ activity.causer.name }}</span> sukūrė
            įvykį.
          </p>
          <p class="mt-0 text-sm text-gray-500">
            {{ activity.created_at }}
          </p>
        </div>
      </div>
    </template>
    <p v-else>Jokių pokyčių nerasta.</p>
  </NModal>
</template>

<script setup lang="ts">
import { DocumentOnePage24Regular } from "@vicons/fluent";
import { NButton, NIcon, NModal } from "naive-ui";
import { ref } from "vue";

defineProps<{
  activities: Record<string, any>;
}>();

const showModal = ref(false);
</script>
