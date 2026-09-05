<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <!-- §1 Basics -->
    <FormElement :section-number="1">
      <template #title>
        {{ $t('navigation.form.section_basics') }}
      </template>

      <FormFieldWrapper id="name" :label="$t('navigation.form.name')" :required="!isNameless" :error="form.errors.name">
        <Input id="name" v-model="form.name" type="text" :disabled="isNameless" />
      </FormFieldWrapper>

      <template v-if="!isNameless">
        <FormFieldWrapper id="link_target" :label="$t('navigation.form.link_target')">
          <div class="flex flex-wrap items-center gap-2">
            <MultiCollectionSelectDialog
              v-model:open="pickerOpen"
              :collections="['pages', 'news', 'calendar', 'institutions', 'documents']"
              :title="$t('navigation.form.link_target')"
              :confirm-label="$t('Pasirinkti')"
              :search-placeholder="$t('navigation.form.link_target_search')"
              @confirm="onTargetConfirm"
            >
              <template #trigger>
                <Button type="button" variant="outline" class="justify-between font-normal">
                  <span class="truncate" :class="{ 'text-muted-foreground': !lastPickedLabel }">
                    {{ lastPickedLabel ?? $t('navigation.form.link_target_placeholder') }}
                  </span>
                  <IFluentChevronDown24Regular class="ml-2 size-4 opacity-50" />
                </Button>
              </template>
            </MultiCollectionSelectDialog>

            <span class="text-xs text-muted-foreground">{{ $t('navigation.form.or') }}</span>

            <Select :model-value="categorySelectValue" @update:model-value="onCategorySelected">
              <SelectTrigger class="w-auto min-w-40">
                <SelectValue :placeholder="$t('navigation.form.link_target_category')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="category in categoryOptions" :key="category.id" :value="String(category.id)">
                  {{ category.name }}
                </SelectItem>
              </SelectContent>
            </Select>

            <Loader2 v-if="isResolvingUrl" class="size-4 animate-spin text-muted-foreground" />
          </div>
        </FormFieldWrapper>

        <FormFieldWrapper id="url" :label="$t('navigation.form.url')" :required="!isFooterRoot" :error="form.errors.url"
          :helper-text="isFooterRoot ? $t('navigation.form.footer_category_url_hint') : $t('navigation.form.link_target_manual')">
          <div class="flex gap-1">
            <Input id="url" v-model="form.url" type="text" />
            <Button variant="outline" size="icon" as="a" :href="form.url" target="_blank">
              <IFluentOpen24Regular />
            </Button>
          </div>
        </FormFieldWrapper>
      </template>
    </FormElement>

    <!-- §2 Appearance — footer links have exactly one look, so there is nothing to pick -->
    <FormElement v-if="!isDivider && !isFooter" :section-number="2">
      <template #title>
        {{ $t('navigation.form.section_appearance') }}
      </template>

      <FormFieldWrapper id="type" :label="$t('navigation.form.type')" required>
        <VisualOptionSelect v-model="linkType" :options="linkStyleOptions" :columns="3" icon-class="h-8 w-14" />
      </FormFieldWrapper>

      <template v-if="!isHeading">
        <FormFieldWrapper id="icon" :label="$t('navigation.form.icon')">
          <FluentIconSelect :icon="form.extra_attributes.icon ?? null"
            @update:icon="(value) => form.extra_attributes.icon = value" />
        </FormFieldWrapper>

        <FormFieldWrapper id="description" :label="$t('navigation.form.description')">
          <Textarea id="description" v-model="form.extra_attributes.description" />
        </FormFieldWrapper>

        <div class="grid gap-3 lg:grid-cols-2">
          <FormFieldWrapper id="small_text" :label="$t('navigation.form.small_text')">
            <Input id="small_text" v-model="form.extra_attributes.small_text" type="text" />
          </FormFieldWrapper>
          <FormFieldWrapper id="badge_variant" :label="$t('navigation.form.badge_variant')">
            <ToggleGroup v-model="badgeVariant" type="single" class="justify-start">
              <ToggleGroupItem v-for="variant in badgeVariantOptions" :key="variant" :value="variant">
                <Badge :variant="variant" size="tiny">{{ form.extra_attributes.small_text || variant }}</Badge>
              </ToggleGroupItem>
            </ToggleGroup>
          </FormFieldWrapper>
        </div>
      </template>
    </FormElement>

    <!-- §3 Image (collapsible, auto-open when an image is already set) — not supported on
         footer links, which are text-only by design (see AGENTS.md) -->
    <FormElement v-if="!isNameless && !isFooter" :section-number="3">
      <template #title>
        {{ $t('navigation.form.section_image') }}
      </template>

      <Collapsible v-model:open="imageSectionOpen" class="w-full">
        <CollapsibleTrigger as-child>
          <Button variant="ghost" class="h-auto w-full justify-between p-0 hover:bg-transparent">
            <span class="text-sm text-muted-foreground">
              {{ imageSectionOpen ? $t('navigation.form.hide_image') : $t('navigation.form.show_image') }}
            </span>
            <IFluentChevronDown24Regular class="size-4 shrink-0 text-muted-foreground transition-transform duration-200" :class="{ 'rotate-180': imageSectionOpen }" />
          </Button>
        </CollapsibleTrigger>
        <CollapsibleContent class="space-y-4 pt-4">
          <FormFieldWrapper id="image" :label="$t('navigation.form.image')">
            <div class="flex items-start gap-4">
              <img v-if="form.extra_attributes.image" class="size-24 shrink-0 rounded-md object-cover" :src="form.extra_attributes.image" alt="">
              <div class="flex flex-col gap-2">
                <ButtonGroup>
                  <TiptapImageButton as-child @submit="form.extra_attributes.image = $event">
                    <Button variant="outline" size="sm" type="button">
                      {{ $t('navigation.form.image_upload') }}
                    </Button>
                  </TiptapImageButton>
                  <Button v-if="form.extra_attributes.image" variant="destructive" size="sm" type="button"
                    @click="form.extra_attributes.image = null">
                    {{ $t('navigation.form.image_remove') }}
                  </Button>
                </ButtonGroup>

                <FocalPointPicker v-if="form.extra_attributes.image" :image-url="form.extra_attributes.image"
                  v-model="focalPoint" />
              </div>
            </div>
          </FormFieldWrapper>

          <template v-if="form.extra_attributes.image">
            <!-- Card copy. Only an image card shows these — a thumbnail link has no room for an
                 eyebrow or a call to action, and a link with no image has no card at all. -->
            <template v-if="imageRender === 'card'">
              <FormFieldWrapper id="eyebrow" :label="$t('navigation.form.eyebrow')" :hint="$t('navigation.form.eyebrow_hint')">
                <Input id="eyebrow" v-model="form.extra_attributes.eyebrow" type="text" />
              </FormFieldWrapper>

              <FormFieldWrapper id="cta" :label="$t('navigation.form.cta')" :hint="$t('navigation.form.cta_hint')">
                <Input id="cta" v-model="form.extra_attributes.cta" type="text" />
              </FormFieldWrapper>

              <FormFieldWrapper id="image_height" :label="$t('navigation.form.image_height')" :hint="$t('navigation.form.image_height_hint')">
                <ToggleGroup v-model="imageHeight" type="single" class="justify-start">
                  <ToggleGroupItem value="short">{{ $t('navigation.form.image_height_short') }}</ToggleGroupItem>
                  <ToggleGroupItem value="tall">{{ $t('navigation.form.image_height_tall') }}</ToggleGroupItem>
                </ToggleGroup>
              </FormFieldWrapper>
            </template>

            <FormFieldWrapper id="image_render" :label="$t('navigation.form.image_render')">
              <ToggleGroup v-model="imageRender" type="single" class="justify-start">
                <ToggleGroupItem value="card">{{ $t('navigation.form.image_render_card') }}</ToggleGroupItem>
                <ToggleGroupItem value="thumbnail">{{ $t('navigation.form.image_render_thumbnail') }}</ToggleGroupItem>
              </ToggleGroup>
            </FormFieldWrapper>

            <FormFieldWrapper id="image_overlay" :label="$t('navigation.form.image_overlay')">
              <ToggleGroup v-model="imageOverlay" type="single" class="justify-start">
                <ToggleGroupItem value="none">{{ $t('navigation.form.image_overlay_none') }}</ToggleGroupItem>
                <ToggleGroupItem value="light">{{ $t('navigation.form.image_overlay_light') }}</ToggleGroupItem>
                <ToggleGroupItem value="medium">{{ $t('navigation.form.image_overlay_medium') }}</ToggleGroupItem>
                <ToggleGroupItem value="heavy">{{ $t('navigation.form.image_overlay_heavy') }}</ToggleGroupItem>
              </ToggleGroup>
            </FormFieldWrapper>

            <FormFieldWrapper id="image_blur" :label="$t('navigation.form.image_blur')">
              <ToggleGroup v-model="imageBlur" type="single" class="justify-start">
                <ToggleGroupItem v-for="blur in [0, 2, 4, 8]" :key="blur" :value="String(blur)">
                  {{ blur }}px
                </ToggleGroupItem>
              </ToggleGroup>
            </FormFieldWrapper>

            <FormFieldWrapper id="image_gradient" :label="$t('navigation.form.image_gradient')">
              <ToggleGroup v-model="imageGradient" type="single" class="justify-start">
                <ToggleGroupItem value="none">{{ $t('navigation.form.image_gradient_none') }}</ToggleGroupItem>
                <ToggleGroupItem value="bottom">{{ $t('navigation.form.image_gradient_bottom') }}</ToggleGroupItem>
                <ToggleGroupItem value="full">{{ $t('navigation.form.image_gradient_full') }}</ToggleGroupItem>
              </ToggleGroup>
            </FormFieldWrapper>
          </template>
        </CollapsibleContent>
      </Collapsible>
    </FormElement>

    <!-- §4 Advanced (collapsible, closed by default) -->
    <FormElement :section-number="4" no-divider>
      <template #title>
        {{ $t('navigation.form.section_advanced') }}
      </template>

      <Collapsible v-model:open="advancedOpen" class="w-full">
        <CollapsibleTrigger as-child>
          <Button variant="ghost" class="h-auto w-full justify-between p-0 hover:bg-transparent">
            <span class="text-sm text-muted-foreground">
              {{ advancedOpen ? $t('navigation.form.hide_advanced') : $t('navigation.form.show_advanced') }}
            </span>
            <IFluentChevronDown24Regular class="size-4 shrink-0 text-muted-foreground transition-transform duration-200" :class="{ 'rotate-180': advancedOpen }" />
          </Button>
        </CollapsibleTrigger>
        <CollapsibleContent class="space-y-4 pt-4">
          <div class="flex items-center justify-between rounded-md border p-3">
            <Label for="is_active" class="cursor-pointer">{{ $t('navigation.form.is_active') }}</Label>
            <Switch id="is_active" :model-value="form.is_active" @update:model-value="val => form.is_active = val" />
          </div>

          <div v-if="!isNameless && !isFooter" class="flex items-center justify-between rounded-md border p-3">
            <Label for="featured" class="cursor-pointer">{{ $t('navigation.form.featured') }}</Label>
            <Switch id="featured" :model-value="!!form.extra_attributes.featured"
              @update:model-value="val => form.extra_attributes.featured = val" />
          </div>

          <div v-if="!isNameless && !isFooterRoot" class="flex items-center justify-between rounded-md border p-3">
            <Label for="new_tab" class="cursor-pointer">{{ $t('navigation.form.new_tab') }}</Label>
            <Switch id="new_tab" :model-value="!!form.extra_attributes.new_tab"
              @update:model-value="val => form.extra_attributes.new_tab = val" />
          </div>

          <!-- Column/col-span pick a header link's spot inside its dropdown; a footer column
               IS a root, so neither concept applies there (see FooterNavigationManager.vue). -->
          <div v-if="!isFooter" class="grid gap-3 lg:grid-cols-2">
            <FormFieldWrapper id="column" :label="$t('navigation.form.column')">
              <Select
                :model-value="form.extra_attributes.column != null ? String(form.extra_attributes.column) : undefined"
                @update:model-value="val => form.extra_attributes.column = Number(val)"
              >
                <SelectTrigger id="column">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="opt in [1, 2, 3]" :key="opt" :value="String(opt)">{{ opt }}</SelectItem>
                </SelectContent>
              </Select>
            </FormFieldWrapper>

            <FormFieldWrapper v-if="!isNameless" id="col_span" :label="$t('navigation.form.col_span')">
              <Select
                :model-value="form.extra_attributes.col_span != null ? String(form.extra_attributes.col_span) : '1'"
                @update:model-value="val => form.extra_attributes.col_span = Number(val)"
              >
                <SelectTrigger id="col_span">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="opt in [1, 2, 3]" :key="opt" :value="String(opt)">{{ opt }}</SelectItem>
                </SelectContent>
              </Select>
            </FormFieldWrapper>
          </div>

          <!-- A footer column is itself a root — it has no parent to reassign. -->
          <FormFieldWrapper v-if="!isFooterRoot" id="parent_id" :label="$t('navigation.form.parent')">
            <SingleSelect
              v-model="selectedParent"
              :options="parentOptions"
              label-field="label"
              value-field="value"
            />
          </FormFieldWrapper>
        </CollapsibleContent>
      </Collapsible>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { computed, h, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Loader2 } from 'lucide-vue-next';

import FluentIconSelect from '../FormItems/FluentIconSelect.vue';
import VisualOptionSelect from '../FormItems/VisualOptionSelect.vue';

import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import AdminForm from './AdminForm.vue';

import { useApiMutation } from '@/Composables/useApi';
import { MultiCollectionSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import TiptapImageButton from '@/Components/TipTap/TiptapImageButton.vue';
import FocalPointPicker from '@/Components/ui/upload/FocalPointPicker.vue';
import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Badge } from '@/Components/ui/badge';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import { SingleSelect } from '@/Components/ui/single-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

interface CategoryOption {
  id: number;
  name: string;
  alias: string | null;
}

const props = defineProps<{
  navigation: App.Entities.Navigation;
  parentElements: App.Entities.Navigation[];
  categoryOptions?: CategoryOption[];
  rememberKey?: 'CreateNavigation';
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const form = props.rememberKey
  ? useForm(props.rememberKey, props.navigation)
  : useForm(props.navigation);

if (!form.extra_attributes) {
  form.extra_attributes = {};
}

// Footer links only ever take two fixed shapes — see NavigationRequest, which is the
// authoritative source for this. Forced here too so the rest of the form (icons, type
// name in the type picker it never renders, etc.) never observes a stale/mismatched type.
const isFooter = computed(() => form.extra_attributes.location === 'footer');
const isFooterRoot = computed(() => isFooter.value && (form.parent_id === 0 || form.parent_id == null));

if (isFooter.value) {
  form.extra_attributes.type = isFooterRoot.value ? 'category-link' : 'link';
}

// Type preview icons — a small skeleton of each element style, mirroring the
// pattern in NewsForm/PageForm's layout pickers.
const LinkTypeIcon = () => h('svg', { viewBox: '0 0 80 48', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 10, y: 22, width: 60, height: 4, rx: 1 }),
  h('rect', { x: 10, y: 30, width: 36, height: 2, rx: 1 }),
]);

const BlockLinkTypeIcon = () => h('svg', { viewBox: '0 0 80 48', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 6, y: 6, width: 68, height: 36, rx: 3 }),
  h('rect', { x: 14, y: 16, width: 40, height: 4, rx: 1 }),
  h('rect', { x: 14, y: 26, width: 52, height: 2, rx: 1 }),
  h('rect', { x: 14, y: 32, width: 30, height: 2, rx: 1 }),
]);

const CategoryLinkTypeIcon = () => h('svg', { viewBox: '0 0 80 48', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 6, y: 6, width: 68, height: 36, rx: 3 }),
  h('rect', { x: 16, y: 16, width: 12, height: 12, rx: 2 }),
  h('rect', { x: 34, y: 18, width: 30, height: 3, rx: 1 }),
  h('rect', { x: 34, y: 26, width: 22, height: 2, rx: 1 }),
]);

const FullHeightBackgroundLinkTypeIcon = () => h('svg', { viewBox: '0 0 80 48', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 6, y: 6, width: 68, height: 36, rx: 3, fill: 'currentColor', opacity: 0.15 }),
  h('rect', { x: 14, y: 30, width: 40, height: 4, rx: 1 }),
]);

const HeadingTypeIcon = () => h('svg', { viewBox: '0 0 80 48', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 10, y: 16, width: 44, height: 6, rx: 1 }),
  h('rect', { x: 10, y: 30, width: 60, height: 2, rx: 1 }),
]);

const DividerTypeIcon = () => h('svg', { viewBox: '0 0 80 48', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 10, y: 22, width: 60, height: 3, rx: 1.5 }),
]);

const linkStyleOptions = computed(() => [
  { value: 'link', label: $t('navigation.form.type_link'), icon: LinkTypeIcon, disabled: false },
  {
    value: 'block-link',
    label: $t('navigation.form.type_block_link'),
    description: $t('navigation.form.type_block_link_hint'),
    icon: BlockLinkTypeIcon,
    disabled: false,
  },
  { value: 'category-link', label: $t('navigation.form.type_category_link'), icon: CategoryLinkTypeIcon, disabled: false },
  {
    value: 'full-height-background-link',
    label: $t('navigation.form.type_full_height_background_link'),
    description: !form.extra_attributes?.image ? $t('navigation.form.type_full_height_background_link_hint') : undefined,
    icon: FullHeightBackgroundLinkTypeIcon,
    disabled: !form.extra_attributes?.image,
  },
  { value: 'heading', label: $t('navigation.form.type_heading'), icon: HeadingTypeIcon, disabled: false },
  { value: 'divider', label: $t('navigation.form.type_divider'), icon: DividerTypeIcon, disabled: false },
]);

const linkType = computed({
  get: () => form.extra_attributes.type ?? 'link',
  set: (val: string) => { form.extra_attributes.type = val; },
});

const isDivider = computed(() => linkType.value === 'divider');
const isHeading = computed(() => linkType.value === 'heading');
const isNameless = computed(() => isDivider.value || isHeading.value);

const badgeVariantOptions = ['rose', 'emerald', 'amber', 'sky', 'zinc'] as const;
const badgeVariant = computed({
  get: () => form.extra_attributes.badge_variant ?? 'rose',
  set: (val: string) => { form.extra_attributes.badge_variant = val; },
});

const imageSectionOpen = ref(!!form.extra_attributes.image);
const imageRender = computed({
  get: () => form.extra_attributes.image_render ?? 'card',
  set: (val: string) => { form.extra_attributes.image_render = val; },
});
const imageOverlay = computed({
  get: () => form.extra_attributes.image_overlay ?? 'medium',
  set: (val: string) => { form.extra_attributes.image_overlay = val; },
});
const imageBlur = computed({
  get: () => String(form.extra_attributes.image_blur ?? 0),
  set: (val: string) => { form.extra_attributes.image_blur = Number(val); },
});
const imageHeight = computed({
  get: () => form.extra_attributes.image_height ?? 'short',
  set: (val: string) => { form.extra_attributes.image_height = val; },
});

const imageGradient = computed({
  get: () => form.extra_attributes.image_gradient ?? 'bottom',
  set: (val: string) => { form.extra_attributes.image_gradient = val; },
});
const focalPoint = computed({
  get: () => form.extra_attributes.image_focal ?? '50% 50%',
  set: (val: string) => { form.extra_attributes.image_focal = val; },
});

const advancedOpen = ref(false);

const parentOptions = computed(() => [
  { value: '__none__', label: '-- Nėra --' },
  ...props.parentElements.map(e => ({ value: String(e.id), label: e.name })),
]);

const selectedParent = computed({
  get: () => parentOptions.value.find(p => p.value === String(form.parent_id ?? '__none__')) ?? parentOptions.value[0],
  set: (val: { value: string; label: string } | null) => {
    form.parent_id = val?.value === '__none__' ? null : val?.value ?? null;
  },
});

// --- Link target picker -----------------------------------------------------
// The picker (and category select) are convenience fillers for `url` — the field
// itself always stays editable as a manual override. Neither the collection nor the
// record id is persisted, so on edit there is nothing to pre-select the picker with.

const pickerOpen = ref(false);
const lastPickedLabel = ref<string | null>(null);

const resolveUrlBody = ref<{ collection: string; id: string | number } | null>(null);
const { execute: executeResolveUrl, data: resolveUrlData, isFetching: isResolvingUrl } = useApiMutation<{ url: string }, { collection: string; id: string | number }>(
  route('api.v1.admin.navigation.resolveUrl'),
  'POST',
  resolveUrlBody,
  { showSuccessToast: false },
);

const resolveAndFillUrl = async (collection: string, id: string | number, label: string) => {
  resolveUrlBody.value = { collection, id };
  await executeResolveUrl();
  if (resolveUrlData.value?.url) {
    form.url = resolveUrlData.value.url;
    lastPickedLabel.value = label;
  }
};

const onTargetConfirm = (hits: NormalizedSearchHit[]) => {
  const hit = hits[0];
  if (!hit) {
    return;
  }
  resolveAndFillUrl(hit.collection, hit.recordId, hit.title);
};

const categorySelectValue = ref<string | undefined>(undefined);
const onCategorySelected = (val: unknown) => {
  const value = val as string | undefined;
  categorySelectValue.value = value;
  const category = props.categoryOptions?.find(c => String(c.id) === value);
  if (category) {
    resolveAndFillUrl('categories', category.id, category.name);
  }
};
</script>
