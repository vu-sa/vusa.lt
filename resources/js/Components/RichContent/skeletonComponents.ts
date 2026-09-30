import { defineComponent, h, markRaw, type Component } from 'vue';

import ContentSkeleton from './ContentSkeleton.vue';

const skeletonCache = new Map<string, Component>();
export function getSkeletonComponent(type: string): Component {
  let component = skeletonCache.get(type);
  if (!component) {
    component = markRaw(defineComponent({ setup: () => () => h(ContentSkeleton, { type }) }));
    skeletonCache.set(type, component);
  }
  return component;
}
