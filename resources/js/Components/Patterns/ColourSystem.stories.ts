import type { Meta, StoryObj } from '@storybook/vue3-vite';

/**
 * The colour system (.ai/rules/css.md) — six status
 * roles + eight categories. `a11y: { test: 'error' }` is the validation mechanism itself: every
 * role renders as real text on its real surface, so axe's `color-contrast` rule asserts the
 * 4.5:1 requirement in every Surface × Theme combination the toolbar offers, in a real browser —
 * no separate contrast script (see designTokens.test.ts for the token-completeness guard
 * instead, which cannot see rendered contrast).
 *
 * Known limits, not fixed by this story:
 * - axe checks text contrast only. "No two categorical hues closer than ~25°" is asserted by
 *   designTokens.test.ts, not by axe — this page is what makes a near-collision visible by eye.
 * - CI's `storybook-tests` job is `continue-on-error: true` (.storybook/CLAUDE.md), so this
 *   gates the local per-phase gate, not CI, until that changes separately.
 */
const meta: Meta = {
  title: 'Patterns/Colour System',
  tags: ['autodocs'],
  parameters: { a11y: { test: 'error' } },
};

export default meta;
type Story = StoryObj;

/**
 * One row per role: tinted badge + icon + word, per the picking list in .ai/rules/css.md. Success and
 * danger deliberately use different icons from each other (✓ / ✕), not colour alone (rule 3).
 */
export const StatusRoles: Story = {
  render: () => ({
    template: `
      <div class="flex flex-col gap-3 bg-background p-6">
        <div class="inline-flex w-fit items-center gap-1.5 border border-status-neutral-border bg-status-neutral-surface px-2 py-1 text-sm font-medium text-status-neutral">
          <span aria-hidden="true">●</span> Juodraštis
        </div>
        <div class="inline-flex w-fit items-center gap-1.5 border border-status-info-border bg-status-info-surface px-2 py-1 text-sm font-medium text-status-info">
          <span aria-hidden="true">●</span> Suplanuota
        </div>
        <div class="inline-flex w-fit items-center gap-1.5 border border-status-progress-border bg-status-progress-surface px-2 py-1 text-sm font-medium text-status-progress">
          <span aria-hidden="true">●</span> Vyksta
        </div>
        <div class="inline-flex w-fit items-center gap-1.5 border border-status-attention-border bg-status-attention-surface px-2 py-1 text-sm font-medium text-status-attention">
          <span aria-hidden="true">▲</span> Laukia veiksmo
        </div>
        <div class="inline-flex w-fit items-center gap-1.5 border border-status-success-border bg-status-success-surface px-2 py-1 text-sm font-medium text-status-success">
          <span aria-hidden="true">✓</span> Atlikta
        </div>
        <div class="inline-flex w-fit items-center gap-1.5 border border-status-danger-border bg-status-danger-surface px-2 py-1 text-sm font-medium text-status-danger">
          <span aria-hidden="true">✕</span> Atmesta
        </div>
      </div>
    `,
  }),
};

/**
 * Small marks, always with a label (never for status). These are the eight primary assignments
 * from the entity-type registry. The label is rendered IN the category colour so axe's text-contrast check —
 * the stricter 4.5:1, not the mark's own 3:1 — covers the mark too.
 */
export const Categories: Story = {
  render: () => ({
    template: `
      <div class="grid grid-cols-2 gap-3 bg-background p-6 sm:grid-cols-4">
        <div class="flex items-center gap-2">
          <span class="h-3 w-3 shrink-0 bg-cat-1" aria-hidden="true" />
          <span class="text-sm font-medium text-cat-1">Posėdis</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="h-3 w-3 shrink-0 bg-cat-2" aria-hidden="true" />
          <span class="text-sm font-medium text-cat-2">Rezervacija</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="h-3 w-3 shrink-0 bg-cat-3" aria-hidden="true" />
          <span class="text-sm font-medium text-cat-3">Narys</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="h-3 w-3 shrink-0 bg-cat-4" aria-hidden="true" />
          <span class="text-sm font-medium text-cat-4">Pareigybė</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="h-3 w-3 shrink-0 bg-cat-5" aria-hidden="true" />
          <span class="text-sm font-medium text-cat-5">Naujiena</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="h-3 w-3 shrink-0 bg-cat-6" aria-hidden="true" />
          <span class="text-sm font-medium text-cat-6">Renginys</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="h-3 w-3 shrink-0 bg-cat-7" aria-hidden="true" />
          <span class="text-sm font-medium text-cat-7">Dokumentas</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="h-3 w-3 shrink-0 bg-cat-8" aria-hidden="true" />
          <span class="text-sm font-medium text-cat-8">Institucija</span>
        </div>
      </div>
    `,
  }),
};

/** Same roles, rendered under the admin surface (PR 2.1) to confirm they read identically there. */
export const StatusRolesOnAdminSurface: Story = {
  globals: { surface: 'admin' },
  render: StatusRoles.render,
};
