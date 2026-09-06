<template>
  <div class="flex flex-col gap-5">
    <RCSectionOptions v-model="options" />
    <EventListOptionsFields v-model:options="options" />
  </div>
</template>

<script setup lang="ts">
/**
 * `event-list` editor. The header (title/subtitle/eyebrow/…) is `RCSectionOptions`,
 * shared by every RCSection-based type; the fetch configuration itself lives in
 * `EventListOptionsFields.vue`, shared with `EventListBlockToolbar.vue`'s full-screen
 * options popover so the two surfaces can't drift apart.
 */
import RCSectionOptions from '../Editor/RCSectionOptions.vue';
import EventListOptionsFields from './EventListOptionsFields.vue';

import type { EventList } from '@/Types/contentParts';

defineModel<EventList['json_content']>({ required: true });
const options = defineModel<EventList['options']>('options', { required: true });
</script>
