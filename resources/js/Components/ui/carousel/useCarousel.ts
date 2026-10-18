import { createInjectionState } from '@vueuse/core';
import emblaCarouselVue from 'embla-carousel-vue';
import { computed, onMounted, ref, toValue, watch } from 'vue';

import type { UnwrapRefCarouselApi as CarouselApi, CarouselEmits, CarouselProps } from './interface';

import { useAccessibilityPreferences } from '@/Composables/useAccessibilityPreferences';

const [useProvideCarousel, useInjectCarousel] = createInjectionState(
  (props: CarouselProps, emits: CarouselEmits) => {
    const { isReducedMotion } = useAccessibilityPreferences();
    const orientation = computed(() => props.orientation);
    const options = computed(() => ({
      ...toValue(props.opts),
      axis: props.orientation === 'horizontal' ? 'x' as const : 'y' as const,
      ...(isReducedMotion.value ? { duration: 0, dragFree: false } : {}),
    }));
    const plugins = computed(() => toValue(props.plugins) ?? []);
    const [emblaNode, emblaApi] = emblaCarouselVue(options, plugins);

    watch(isReducedMotion, (reduced) => {
      if (reduced) emblaApi.value?.scrollTo(emblaApi.value.selectedScrollSnap(), true);
    }, { flush: 'post' });

    function scrollPrev() {
      emblaApi.value?.scrollPrev(isReducedMotion.value);
    }
    function scrollNext() {
      emblaApi.value?.scrollNext(isReducedMotion.value);
    }

    const canScrollNext = ref(false);
    const canScrollPrev = ref(false);

    function onSelect(api: CarouselApi) {
      canScrollNext.value = api?.canScrollNext() || false;
      canScrollPrev.value = api?.canScrollPrev() || false;
    }

    onMounted(() => {
      if (!emblaApi.value)
        return;

      emblaApi.value?.on('init', onSelect);
      emblaApi.value?.on('reInit', onSelect);
      emblaApi.value?.on('select', onSelect);

      onSelect(emblaApi.value);

      emits('init-api', emblaApi.value);
    });

    return { carouselRef: emblaNode, carouselApi: emblaApi, canScrollPrev, canScrollNext, scrollPrev, scrollNext, orientation };
  },
);

function useCarousel() {
  const carouselState = useInjectCarousel();

  if (!carouselState)
    throw new Error('useCarousel must be used within a <Carousel />');

  return carouselState;
}

export { useCarousel, useProvideCarousel };
