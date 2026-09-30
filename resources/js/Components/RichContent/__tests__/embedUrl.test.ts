import { describe, expect, it } from 'vitest';

import {
  detectSocialPlatform,
  isMixcloudUrl,
  toInstagramEmbedUrl,
  toMixcloudEmbedUrl,
  toSpotifyEmbedUrl,
} from '../embedUrl';

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

describe('detectSocialPlatform', () => {
  it('detects standard and mobile Facebook URLs', () => {
    expect(detectSocialPlatform('https://www.facebook.com/vustudentusatstovybe/posts/123456')).toBe('facebook');
    expect(detectSocialPlatform('https://facebook.com/photo/?fbid=123456')).toBe('facebook');
    expect(detectSocialPlatform('https://m.facebook.com/permalink.php?story_fbid=123&id=456')).toBe('facebook');
    expect(detectSocialPlatform('https://web.facebook.com/watch/?v=123456')).toBe('facebook');
    expect(detectSocialPlatform('https://facebook.com/reel/123456')).toBe('facebook');
    expect(detectSocialPlatform('https://facebook.com/share/p/123456/')).toBe('facebook');
    expect(detectSocialPlatform('https://fb.watch/xyz123/')).toBe('facebook');
    expect(detectSocialPlatform('http://www.facebook.com/posts/123')).toBe('facebook');
  });

  it('detects Instagram post, reel, reels, tv, and short URLs', () => {
    expect(detectSocialPlatform('https://www.instagram.com/p/C_abc123/')).toBe('instagram');
    expect(detectSocialPlatform('https://instagram.com/reel/C_abc123/')).toBe('instagram');
    expect(detectSocialPlatform('https://www.instagram.com/reels/C_abc123/')).toBe('instagram');
    expect(detectSocialPlatform('https://www.instagram.com/tv/C_abc123/')).toBe('instagram');
    expect(detectSocialPlatform('https://instagr.am/p/C_abc123/')).toBe('instagram');
    expect(detectSocialPlatform('https://m.instagram.com/p/C_abc123/')).toBe('instagram');
    expect(detectSocialPlatform('https://www.instagram.com/p/C_abc123/?igsh=abc123xyz#details')).toBe('instagram');
  });

  it('rejects lookalike, spoofed, and credential-bearing domains', () => {
    expect(detectSocialPlatform('https://notfacebook.com/posts/123')).toBeNull();
    expect(detectSocialPlatform('https://facebook.com.evil.com/posts/123')).toBeNull();
    expect(detectSocialPlatform('https://evil-facebook.com/posts/123')).toBeNull();
    expect(detectSocialPlatform('https://facebook.com@evil.com/posts/123')).toBeNull();
    expect(detectSocialPlatform('https://evil.com/facebook.com/posts/123')).toBeNull();
    expect(detectSocialPlatform('https://evil.com/?target=facebook.com')).toBeNull();
    expect(detectSocialPlatform('https://notinstagram.com/p/123')).toBeNull();
    expect(detectSocialPlatform('https://instagram.com.attacker.com/p/123')).toBeNull();
    expect(detectSocialPlatform('https://fakeinstagram.com/p/123')).toBeNull();
    expect(detectSocialPlatform('https://instagr.am.evil.com/p/123')).toBeNull();
    expect(detectSocialPlatform('https://evil.com/?instagram.com')).toBeNull();
  });

  it('rejects empty root URLs and URLs without valid post targets', () => {
    expect(detectSocialPlatform('https://www.facebook.com/')).toBeNull();
    expect(detectSocialPlatform('https://facebook.com')).toBeNull();
    expect(detectSocialPlatform('https://www.instagram.com/')).toBeNull();
    expect(detectSocialPlatform('https://instagram.com')).toBeNull();
    expect(detectSocialPlatform('https://www.instagram.com/p/')).toBeNull();
    expect(detectSocialPlatform('https://www.instagram.com/stories/username/123')).toBeNull();
  });

  it('rejects non-http protocols and malformed strings', () => {
    expect(detectSocialPlatform('javascript:alert(1)')).toBeNull();
    expect(detectSocialPlatform('ftp://facebook.com/posts/123')).toBeNull();
    expect(detectSocialPlatform('not-a-valid-url')).toBeNull();
    expect(detectSocialPlatform('')).toBeNull();
    expect(detectSocialPlatform('   ')).toBeNull();
    expect(detectSocialPlatform(null)).toBeNull();
    expect(detectSocialPlatform(undefined)).toBeNull();
  });
});

describe('toInstagramEmbedUrl', () => {
  it('strips query parameters and hash while preserving trailing slash', () => {
    expect(toInstagramEmbedUrl('https://www.instagram.com/p/C_abc123/?igsh=tracking123#fragment'))
      .toBe('https://www.instagram.com/p/C_abc123/');
  });

  it('adds trailing slash if missing', () => {
    expect(toInstagramEmbedUrl('https://www.instagram.com/p/C_abc123'))
      .toBe('https://www.instagram.com/p/C_abc123/');
  });

  it('returns empty string for falsy input', () => {
    expect(toInstagramEmbedUrl('')).toBe('');
    expect(toInstagramEmbedUrl(null)).toBe('');
    expect(toInstagramEmbedUrl(undefined)).toBe('');
  });
});
