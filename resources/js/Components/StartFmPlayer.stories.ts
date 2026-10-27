import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { expect, userEvent, within } from 'storybook/test';
import { onMounted, ref } from 'vue';

import StartFmPlayer from './StartFmPlayer.vue';
import StartFM from './Public/Nav/StartFM.vue';
import MobileBottomBar from './Layouts/Shell/MobileBottomBar.vue';
import { pradzia } from './Layouts/Shell/__tests__/fixtures';

import { createStartFmProvider, type StartFmStatus } from '@/Composables/useStartFm';

const meta: Meta = {
  title: 'Media/START FM',
  component: StartFmPlayer,
  parameters: { layout: 'fullscreen', a11y: { test: 'error' } },
};
export default meta;
type Story = StoryObj;

function renderPlayer(surface: 'public' | 'admin', status: StartFmStatus = 'playing', saveBar = false) {
  return () => ({
    components: { StartFmPlayer, StartFM, MobileBottomBar },
    setup() {
      const radio = createStartFmProvider();
      const host = ref<HTMLElement | null>(null);
      onMounted(() => {
        const audio = host.value?.querySelector('audio');
        if (audio) {
          // Keep stories independent of the live radio server.
          audio.play = () => Promise.resolve();
          audio.pause = () => undefined;
          audio.load = () => undefined;
        }
        radio.isOpen.value = true;
        radio.status.value = status;
      });
      return { host, radio, surface, saveBar, pradzia };
    },
    template: `
      <div ref="host" class="flex min-h-screen flex-col bg-background text-foreground">
        <main class="flex-1 p-6">
          <h1 class="text-xl font-semibold">START FM</h1>
          <p class="mt-2 text-sm text-muted-foreground">Studentų radijas</p>
          <StartFM icon-only class="mt-4" />
        </main>
        <div :class="surface === 'public' ? 'fixed inset-x-0 bottom-0 md:static' : 'shrink-0'">
          <StartFmPlayer :surface />
          <div v-if="surface === 'admin'" class="[--shell-bottom-bar:calc(3.5rem_+_env(safe-area-inset-bottom,0px))]">
            <div v-if="saveBar" data-slot="story-save-bar" class="flex items-center justify-end border-t border-border bg-background p-3 md:hidden">
              <button type="button" class="h-11 bg-brand-fill px-4 text-brand-foreground">Išsaugoti</button>
            </div>
            <MobileBottomBar v-else :active-workspace="pradzia" can-create />
          </div>
        </div>
      </div>
    `,
  });
}

export const PublicPlaying: Story = { render: renderPlayer('public') };
export const AdminPlaying: Story = { globals: { surface: 'admin' }, render: renderPlayer('admin') };
export const PublicDark: Story = { globals: { theme: 'dark', surface: 'public' }, render: renderPlayer('public') };
export const AdminDark: Story = { globals: { theme: 'dark', surface: 'admin' }, render: renderPlayer('admin') };
export const Connecting: Story = { render: renderPlayer('public', 'connecting') };
export const Paused: Story = { render: renderPlayer('public', 'paused') };
export const ConnectionError: Story = { render: renderPlayer('public', 'error') };
export const AdminForm: Story = { globals: { surface: 'admin' }, render: renderPlayer('admin', 'playing', true) };
export const PlaybackControls: Story = {
  render: renderPlayer('public'),
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    await userEvent.click(await canvas.findByRole('button', { name: 'Pristabdyti START FM' }));
    await expect(canvas.getByRole('status')).toHaveTextContent('Pristabdyta');
    await userEvent.click(canvas.getByRole('button', { name: 'Sustabdyti ir užverti START FM' }));
    await expect(canvas.queryByRole('complementary', { name: 'START FM grotuvas' })).not.toBeInTheDocument();
  },
};
