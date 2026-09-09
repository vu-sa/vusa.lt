import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { expect, screen, userEvent, within } from 'storybook/test';

import FeedbackPopover from './FeedbackPopover.vue';

/**
 * A "report an issue" button that surfaces next to text a reader selects, so feedback can be tied
 * to the exact passage it's about. Select any part of the sample paragraph below to see it appear.
 *
 * The button and dialog teleport to `<body>`, outside the story's canvas — the interactive stories
 * below query `document.body` via `screen` rather than the scoped `canvas`.
 */
const meta: Meta<typeof FeedbackPopover> = {
  title: 'Public/FeedbackPopover',
  component: FeedbackPopover,
  tags: ['autodocs'],
  render: () => ({
    components: { FeedbackPopover },
    template: `
      <div class="max-w-prose p-12" style="padding: 3rem;">
        <p data-testid="sample-text">
          VU SA atstovauja Vilniaus universiteto studentams — pažymėkite šį sakinį pele, kad
          pamatytumėte grįžtamojo ryšio mygtuką.
        </p>
        <FeedbackPopover />
      </div>
    `,
  }),
};

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

async function wait(ms: number) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Selects the sample paragraph, then fires an untrusted mousedown → mouseup pair.
 *
 * `useMousePressed` only flips the popover on the press → release *transition*, and a real
 * `userEvent.pointer` click (a trusted, OS-level event under Playwright) collapses the selection
 * on mousedown before release — exactly like a real browser does for a plain click. Dispatching
 * synthetic events directly avoids that default action while still reaching vueuse's listeners.
 */
async function selectAndRelease(canvasElement: HTMLElement) {
  const paragraph = within(canvasElement).getByTestId('sample-text');
  const range = document.createRange();
  range.selectNodeContents(paragraph);

  const selection = window.getSelection();
  selection?.removeAllRanges();
  selection?.addRange(range);
  await wait(50); // let the native `selectionchange` event reach useTextSelection

  document.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
  await wait(20);
  document.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
  await wait(20);
}

export const PopoverOnSelection: Story = {
  play: async ({ canvasElement }) => {
    await selectAndRelease(canvasElement);

    await expect(await screen.findByRole('button', { name: 'Pranešk apie klaidą!' })).toBeVisible();
  },
};

export const FeedbackDialog: Story = {
  play: async ({ canvasElement }) => {
    await selectAndRelease(canvasElement);

    const trigger = await screen.findByRole('button', { name: 'Pranešk apie klaidą!' });
    await userEvent.click(trigger);

    const dialog = within(await screen.findByRole('dialog'));
    await expect(dialog.getByRole('heading', { name: 'Pranešk apie klaidą!' })).toBeVisible();
    await expect(dialog.getByText('VU SA atstovauja Vilniaus universiteto studentams', { exact: false })).toBeVisible();
  },
};
