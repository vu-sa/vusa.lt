// @vitest-environment node
import { describe, expect, it } from 'vitest';

import { isMixcloudUrl, toSpotifyEmbedUrl } from '../embedUrl';

// The SSR server has no `window`; the player choice and iframe src must match the browser's.
describe('embed URLs without a window', () => {
  it('still recognises Mixcloud, so the server renders the Mixcloud player', () => {
    expect(typeof window).toBe('undefined');
    expect(isMixcloudUrl('https://www.mixcloud.com/startfm/tiesiogiai-is-vu-sa/')).toBe(true);
  });

  it('sets the Spotify theme param the same way as in the browser', () => {
    expect(toSpotifyEmbedUrl('https://open.spotify.com/embed/show/abc?theme=1', true))
      .toBe('https://open.spotify.com/embed/show/abc?theme=0');
  });
});
