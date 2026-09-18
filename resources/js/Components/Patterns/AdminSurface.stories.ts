import type { Meta, StoryObj } from '@storybook/vue3-vite';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

/**
 * The admin surface (.ai/redesign/admin, PR 2.1) — `[data-surface="admin"]` in
 * resources/css/admin/surface.css, opted into per-user via `appearance.new_shell`. This is how a
 * reviewer sees the token scope without logging in and flipping the AdminLayout toggle: switch
 * the toolbar's Surface to "Admin" (pinned here via `globals`) and Theme to compare both modes.
 *
 * Legacy (non-opted-in) admin is not shown here — it is simply Tailwind's default zinc palette,
 * unaffected by this surface, and stays that way until PR 8.1 removes the opt-in.
 */
const meta: Meta = {
  title: 'Patterns/Admin Surface',
  tags: ['autodocs'],
  globals: { surface: 'admin' },
  parameters: { a11y: { test: 'error' } },
};

export default meta;
type Story = StoryObj;

export const Canvas: Story = {
  render: () => ({
    template: `
      <div class="space-y-6 bg-background p-6 text-foreground">
        <div class="space-y-1">
          <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Canvas · Ink</p>
          <p class="text-sm text-muted-foreground">
            bg-background / text-foreground — the warm paper (light) / near-black (dark) ground every
            admin page sits on once opted in.
          </p>
        </div>
        <div class="border border-border p-4">
          <p class="text-sm">A hairline box: border-border on bg-background.</p>
        </div>
      </div>
    `,
  }),
};

export const Card: Story = {
  render: () => ({
    template: `
      <div class="bg-background p-6">
        <div class="max-w-sm border border-border bg-card p-4 text-card-foreground">
          <p class="text-sm font-medium">bg-card</p>
          <p class="text-sm text-muted-foreground">A step off the canvas — square, hairline-bordered (D9).</p>
        </div>
      </div>
    `,
  }),
};

export const Popover: Story = {
  render: () => ({
    template: `
      <div class="bg-background p-6">
        <div class="max-w-xs border border-border bg-popover p-3 text-popover-foreground shadow-none">
          <p class="text-sm">bg-popover — dropdowns, dialogs and the command palette teleport here.</p>
        </div>
      </div>
    `,
  }),
};

/** The tinted edit-mode canvas (rules/visual.md — Wayfinding and distinctness, rule 5). */
export const EditModeCanvas: Story = {
  render: () => ({
    template: `
      <div class="space-y-4 bg-background p-6">
        <div class="max-w-md space-y-2 border border-border bg-secondary p-4 text-secondary-foreground">
          <p class="text-xs font-medium uppercase tracking-wide">Redaguoji</p>
          <p class="text-sm">
            bg-secondary — forms and focused editors sit on this tint, so editing never looks like
            viewing.
          </p>
        </div>
      </div>
    `,
  }),
};

export const Hairlines: Story = {
  render: () => ({
    template: `
      <div class="divide-y divide-border border-y border-border bg-background">
        <div class="p-3 text-sm">Row one</div>
        <div class="p-3 text-sm">Row two</div>
        <div class="p-3 text-sm">Row three</div>
      </div>
    `,
  }),
};

export const Inputs: Story = {
  render: () => ({
    components: { Button, Input },
    template: `
      <div class="flex max-w-sm flex-col gap-3 bg-background p-6">
        <Input placeholder="Pavadinimas" />
        <Button variant="brand">Išsaugoti</Button>
        <Button variant="outline">Atšaukti</Button>
      </div>
    `,
  }),
};

/** D9 — square everywhere. `rounded-xl` must resolve to 0 under the scope. */
export const RadiusProbe: Story = {
  render: () => ({
    template: `
      <div class="flex items-center gap-4 bg-background p-6">
        <div class="h-16 w-16 rounded-xl border border-border bg-card" />
        <p class="text-sm text-muted-foreground">rounded-xl → 0px under this surface (radius zeroed).</p>
      </div>
    `,
  }),
};

/** D10 — the admin surface takes the public typeface. */
export const Typeface: Story = {
  render: () => ({
    template: `
      <div class="bg-background p-6 text-foreground">
        <p class="text-2xl font-bold">Atkinson Hyperlegible Next</p>
        <p class="text-sm text-muted-foreground">Set on the scope itself, so teleported content (dialogs, the command palette, toasts) inherits it too.</p>
      </div>
    `,
  }),
};
