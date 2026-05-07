import { defineComponent } from 'vue';

/**
 * Shared component stubs for Vue Test Utils component tests.
 *
 * Use these in your `global.stubs` mounting option to avoid redefining
 * the same stubs in every test file. Only stub components that are known
 * to cause issues in jsdom (Dialog, Tooltip, icons). Everything else
 * should render as the real component.
 *
 * @example
 * import { commonStubs } from '@/tests/stubs';
 *
 * mount(MyComponent, {
 *   global: { stubs: { ...commonStubs, MyCustomStub: true } },
 * });
 */

export const stubDialog = defineComponent({
  name: 'DialogStub',
  props: ['open'],
  template: '<div v-if="open" class="dialog" role="dialog"><slot /></div>',
});

export const stubDialogContent = defineComponent({
  name: 'DialogContentStub',
  template: '<div class="dialog-content"><slot /></div>',
});

export const stubDialogDescription = defineComponent({
  name: 'DialogDescriptionStub',
  template: '<div class="dialog-description"><slot /></div>',
});

export const stubDialogFooter = defineComponent({
  name: 'DialogFooterStub',
  template: '<div class="dialog-footer"><slot /></div>',
});

export const stubDialogHeader = defineComponent({
  name: 'DialogHeaderStub',
  template: '<div class="dialog-header"><slot /></div>',
});

export const stubDialogTitle = defineComponent({
  name: 'DialogTitleStub',
  template: '<div class="dialog-title"><slot /></div>',
});

export const stubTooltip = defineComponent({
  name: 'TooltipStub',
  template: '<div><slot /></div>',
});

export const stubTooltipContent = defineComponent({
  name: 'TooltipContentStub',
  template: '<div class="tooltip-content"><slot /></div>',
});

export const stubTooltipProvider = defineComponent({
  name: 'TooltipProviderStub',
  template: '<div><slot /></div>',
});

export const stubTooltipTrigger = defineComponent({
  name: 'TooltipTriggerStub',
  template: '<div><slot /></div>',
});

export const stubIcon = (className: string) => defineComponent({
  name: 'IconStub',
  template: `<span class="${className}" />`,
});

/**
 * Pre-built object ready to spread into `global.stubs`.
 * Covers Dialog, Tooltip, and common icon families.
 */
export const commonStubs: Record<string, any> = {
  Dialog: stubDialog,
  DialogContent: stubDialogContent,
  DialogDescription: stubDialogDescription,
  DialogFooter: stubDialogFooter,
  DialogHeader: stubDialogHeader,
  DialogTitle: stubDialogTitle,
  Tooltip: stubTooltip,
  TooltipContent: stubTooltipContent,
  TooltipProvider: stubTooltipProvider,
  TooltipTrigger: stubTooltipTrigger,
};
