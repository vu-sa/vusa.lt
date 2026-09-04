import { describe, expect, it } from 'vitest';

import { isMixcloudUrl, toMixcloudEmbedUrl, toSpotifyEmbedUrl } from '../embedUrl';

describe('isMixcloudUrl', () => {
  it('detects mixcloud.com and player-widget hosts', () => {
    expect(isMixcloudUrl('https://www.mixcloud.com/startfm/tiesiogiai-is-vu-sa/')).toBe(true);
    expect(isMixcloudUrl('https://player-widget.mixcloud.com/widget/iframe/?feed=%2Fstartfm%2F')).toBe(true);
  });

  it('rejects a Spotify URL', () => {
    expect(isMixcloudUrl('https://open.spotify.com/show/abc')).toBe(false);
  });

  it('returns false instead of throwing for a malformed URL', () => {
    expect(isMixcloudUrl('not-a-url')).toBe(false);
  });
});

describe('toMixcloudEmbedUrl', () => {
  it('converts a page URL to the widget iframe URL', () => {
    const url = toMixcloudEmbedUrl('https://www.mixcloud.com/startfm/tiesiogiai-is-vu-sa/', false);
    expect(url).toBe('https://player-widget.mixcloud.com/widget/iframe/?hide_cover=1&light=1&feed=%2Fstartfm%2Ftiesiogiai-is-vu-sa%2F');
  });

  it('sets light=0 in dark mode', () => {
    const url = toMixcloudEmbedUrl('https://www.mixcloud.com/startfm/tiesiogiai-is-vu-sa/', true);
    expect(url).toContain('light=0');
  });

  it('updates the light param on an already-widget URL instead of re-wrapping it', () => {
    const url = toMixcloudEmbedUrl('https://player-widget.mixcloud.com/widget/iframe/?hide_cover=1&light=1&feed=%2Fx%2F', true);
    expect(url).toBe('https://player-widget.mixcloud.com/widget/iframe/?hide_cover=1&light=0&feed=%2Fx%2F');
  });
});

describe('toSpotifyEmbedUrl', () => {
  it('appends the theme param for a Spotify URL', () => {
    expect(toSpotifyEmbedUrl('https://open.spotify.com/show/abc', false)).toBe('https://open.spotify.com/show/abc?theme=1');
    expect(toSpotifyEmbedUrl('https://open.spotify.com/show/abc', true)).toBe('https://open.spotify.com/show/abc?theme=0');
  });

  it('passes a non-Spotify URL through untouched', () => {
    expect(toSpotifyEmbedUrl('https://www.mixcloud.com/startfm/episode/', true)).toBe('https://www.mixcloud.com/startfm/episode/');
  });
});
