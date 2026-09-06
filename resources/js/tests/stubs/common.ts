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

/**
 * Dropdown menus render their content through a reka-ui portal and only mount it
 * once open, neither of which jsdom handles predictably. The stubs render the
 * content inline and turn `select` into a click so menu items stay assertable.
 */
export const stubDropdownMenu = defineComponent({
  name: 'DropdownMenuStub',
  template: '<div data-testid="dropdown-menu"><slot /></div>',
});

export const stubDropdownMenuTrigger = defineComponent({
  name: 'DropdownMenuTriggerStub',
  template: '<div><slot /></div>',
});

export const stubDropdownMenuContent = defineComponent({
  name: 'DropdownMenuContentStub',
  template: '<div data-testid="dropdown-menu-content"><slot /></div>',
});

export const stubDropdownMenuItem = defineComponent({
  name: 'DropdownMenuItemStub',
  props: ['disabled'],
  emits: ['select'],
  template: '<button type="button" :disabled="disabled" @click="$emit(\'select\', $event)"><slot /></button>',
});

export const stubDropdownMenuSeparator = defineComponent({
  name: 'DropdownMenuSeparatorStub',
  template: '<hr />',
});

export const stubDropdownMenuCheckboxItem = defineComponent({
  name: 'DropdownMenuCheckboxItemStub',
  props: ['modelValue', 'disabled'],
  emits: ['select', 'update:modelValue'],
  template: `
    <button
      type="button"
      role="menuitemcheckbox"
      :disabled="disabled"
      :aria-checked="modelValue ? 'true' : 'false'"
      @click="$emit('select', $event); $emit('update:modelValue', !modelValue)"
    >
      <slot />
    </button>
  `,
});

export const stubDropdownMenuLabel = defineComponent({
  name: 'DropdownMenuLabelStub',
  template: '<div><slot /></div>',
});

export const stubDropdownMenuGroup = defineComponent({
  name: 'DropdownMenuGroupStub',
  template: '<div><slot /></div>',
});

export const stubIcon = (className: string) => defineComponent({
  name: 'IconStub',
  template: `<span class="${className}" />`,
});

/**
 * Popover stubs — NOT part of `commonStubs` (unlike Dialog/Tooltip): most of this
 * codebase already mounts real `Popover`/`PopoverTrigger` successfully in jsdom (date
 * pickers, DutyCard, …), so blanket-stubbing it in the shared registry broke those
 * unrelated tests (a stubbed `Popover` removes the `PopoverRootContext` a real, unstubbed
 * `PopoverTrigger` elsewhere still injects). Opt in locally — `import { stubPopover,
 * stubPopoverAnchor, stubPopoverContent } from '@/tests/stubs'` — only where the test
 * needs a simplified, always-rendered Popover (e.g. asserting open/closed content
 * directly without Floating UI's real positioning).
 */
export const stubPopover = defineComponent({
  name: 'PopoverStub',
  props: ['open'],
  emits: ['update:open'],
  template: '<div><slot /></div>',
});

export const stubPopoverAnchor = defineComponent({
  name: 'PopoverAnchorStub',
  props: ['reference'],
  template: '<div><slot /></div>',
});

export const stubPopoverContent = defineComponent({
  name: 'PopoverContentStub',
  template: '<div class="popover-content"><slot /></div>',
});

/**
 * Pre-built object ready to spread into `global.stubs`.
 * Covers Dialog, Tooltip, DropdownMenu, and common icon families.
 */
export const commonStubs: Record<string, any> = {
  Dialog: stubDialog,
  DialogContent: stubDialogContent,
  DialogDescription: stubDialogDescription,
  DialogFooter: stubDialogFooter,
  DialogHeader: stubDialogHeader,
  DialogTitle: stubDialogTitle,
  DropdownMenu: stubDropdownMenu,
  DropdownMenuCheckboxItem: stubDropdownMenuCheckboxItem,
  DropdownMenuContent: stubDropdownMenuContent,
  DropdownMenuGroup: stubDropdownMenuGroup,
  DropdownMenuItem: stubDropdownMenuItem,
  DropdownMenuLabel: stubDropdownMenuLabel,
  DropdownMenuSeparator: stubDropdownMenuSeparator,
  DropdownMenuTrigger: stubDropdownMenuTrigger,
  Tooltip: stubTooltip,
  TooltipContent: stubTooltipContent,
  TooltipProvider: stubTooltipProvider,
  TooltipTrigger: stubTooltipTrigger,
};
