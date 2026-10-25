import { computed, inject, provide, toValue, type ComputedRef, type InjectionKey, type MaybeRefOrGetter } from 'vue';

const pageHasHeadingKey: InjectionKey<ComputedRef<boolean>> = Symbol('pageHasHeading');

/** Declare that the page renders its own <h1>, so content blocks don't add a second one. */
export function providePageHeading(hasHeading: MaybeRefOrGetter<boolean> = true): void {
  provide(pageHasHeadingKey, computed(() => toValue(hasHeading)));
}

/** Level 1 unless the page above already rendered an <h1>. */
export function useBlockHeadingLevel(): ComputedRef<1 | 2> {
  const pageHasHeading = inject(pageHasHeadingKey, null);

  return computed(() => (pageHasHeading?.value ? 2 : 1));
}
