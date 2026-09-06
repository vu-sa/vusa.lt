<template>
  <div class="flex flex-col gap-5">
    <RCSectionOptions v-model="options" />
    <LinkListOptionsFields v-model="content" v-model:options="options" />
  </div>
</template>

<script setup lang="ts">
/**
 * `link-list` editor. The header (title/subtitle/eyebrow/…) is `RCSectionOptions`,
 * shared by every RCSection-based type; the fetch configuration itself lives in
 * `LinkListOptionsFields.vue`, shared with `LinkListBlockToolbar.vue`'s full-screen
 * options popover so the two surfaces can't drift apart.
 */
import RCSectionOptions from '../Editor/RCSectionOptions.vue';
import LinkListOptionsFields from './LinkListOptionsFields.vue';

import type { LinkList } from '@/Types/contentParts';

const content = defineModel<LinkList['json_content']>({ required: true });
const options = defineModel<LinkList['options']>('options', { required: true });
</script>
