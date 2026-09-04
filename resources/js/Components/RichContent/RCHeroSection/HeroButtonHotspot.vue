<template>
  <Popover :open="hotspots.isPopoverOpen(hotspotId)" @update:open="onOpenChange">
    <div
      ref="rootRef"
      class="relative w-fit"
      data-rc-interactive
      role="button"
      tabindex="0"
      @click.capture.stop.prevent="hotspots.openPopover(hotspotId)"
      @keydown.enter.prevent="hotspots.openPopover(hotspotId)"
      @keydown.space.prevent="hotspots.openPopover(hotspotId)"
    >
      <PopoverAnchor
        :reference="rootRef ?? undefined"
        class="pointer-events-none absolute size-0 overflow-hidden"
      />
      <SmartLink :href="button.link" class="w-fit">
        <Button :variant="button.variant === 'outline' ? 'brand-outline' : 'brand'" size="public" class="w-full sm:w-auto">
          <RCIcon v-if="button.icon" :name="button.icon" class="size-4" />
          {{ button.text || $t('rich-content.button_text') }}
        </Button>
      </SmartLink>
    </div>
    <PopoverContent
      v-if="hotspots.isPopoverOpen(hotspotId)"
      data-surface="public"
      class="w-[min(28rem,calc(100vw-2rem))]"
      @close-auto-focus.prevent
    >
      <div class="flex flex-col gap-3">
        <Field>
          <FieldLabel>{{ $t('rich-content.button_text') }}</FieldLabel>
          <Input
            :model-value="button.text"
            type="text"
            :placeholder="$t('rich-content.enter_button_text')"
            @update:model-value="$emit('update:button', { ...button, text: $event as string })"
          />
        </Field>
        <Field>
          <FieldLabel>{{ $t('rich-content.button_link') }}</FieldLabel>
          <Input
            :model-value="button.link"
            type="text"
            placeholder="https://..."
            @update:model-value="$emit('update:button', { ...button, link: $event as string })"
          />
        </Field>
        <div class="grid grid-cols-2 gap-3">
          <Field>
            <FieldLabel>{{ $t('rich-content.button_variant') }}</FieldLabel>
            <Select :model-value="button.variant || 'default'" @update:model-value="$emit('update:button', { ...button, variant: $event as 'default' | 'outline' })">
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="default">{{ $t('rich-content.default') }}</SelectItem>
                <SelectItem value="outline">{{ $t('rich-content.outline') }}</SelectItem>
              </SelectContent>
            </Select>
          </Field>
          <Field>
            <FieldLabel>{{ $t('rich-content.icon') }}</FieldLabel>
            <RCIconSelect :model-value="button.icon" allow-none @update:model-value="$emit('update:button', { ...button, icon: $event })" />
          </Field>
        </div>
        <div class="border-t border-border pt-3">
          <Button variant="destructive" size="sm" @click="$emit('remove')">
            {{ $t('rich-content.remove_button') }}
          </Button>
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
/**
 * One hero button, rendered with the same markup `HeroButtons.vue` uses (a small,
 * deliberate duplication — HeroButtons.vue is also used by HeroCarouselDisplay.vue and
 * RCSpotifyPromoDisplay.vue and is out of scope here). Clicking it opens a popover with
 * the button's text/link/variant/icon, anchored to the rendered button itself.
 *
 * `@click.capture.stop.prevent` on the wrapper (not `.prevent` alone) is load-bearing:
 * `SmartLink` may render an Inertia `<Link>`, which attaches its own click handler
 * directly to the anchor and calls `router.visit()` itself, independent of the native
 * default action — capturing and stopping propagation before the event ever reaches
 * that handler is the only way to guarantee it never fires.
 */
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { injectActiveHotspot } from '../Editor/Fullscreen/useActiveHotspot';
import RCIconSelect from '../RCIconSelect.vue';
import type { Hero } from '@/Types/contentParts';

import SmartLink from '@/Components/Public/SmartLink.vue';
import RCIcon from '../RCIcon.vue';
import { Button } from '@/Components/ui/button';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Popover, PopoverAnchor, PopoverContent } from '@/Components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

type HeroButton = NonNullable<Hero['json_content']['buttons']>[number];

const props = defineProps<{
  button: HeroButton;
  index: number;
  blockKey: string;
}>();

defineEmits<{
  (e: 'update:button', value: HeroButton): void;
  (e: 'remove'): void;
}>();

const hotspots = injectActiveHotspot();
const hotspotId = computed(() => `${props.blockKey}:buttons:${props.index}`);
const rootRef = ref<HTMLElement | null>(null);

function onOpenChange(open: boolean): void {
  if (open) hotspots.openPopover(hotspotId.value);
  else hotspots.close(hotspotId.value);
}
</script>
