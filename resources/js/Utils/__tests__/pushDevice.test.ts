import { describe, expect, it } from 'vitest';

import { describeDevice, deviceLabel } from '../pushDevice';

describe('describeDevice', () => {
  it.each([
    ['Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'Chrome · Android'],
    ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'Chrome · Mac'],
    ['Mozilla/5.0 (X11; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0', 'Firefox · Linux'],
    ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'Edge · Windows'],
    ['Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1', 'Safari · iPhone'],
    ['Mozilla/5.0 (Linux; Android 14; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/26.0 Chrome/122.0.0.0 Mobile Safari/537.36', 'Samsung Internet · Android'],
  ])('names %s', (userAgent, expected) => {
    expect(describeDevice(userAgent)).toBe(expected);
  });
});

describe('deviceLabel', () => {
  it('keeps a name that already carries the browser', () => {
    expect(deviceLabel('Chrome · Mac', 'https://fcm.googleapis.com/fcm/send/x')).toBe('Chrome · Mac');
  });

  it('adds the browser from the push service for names saved before it was recorded', () => {
    expect(deviceLabel('Mac', 'https://fcm.googleapis.com/fcm/send/x')).toBe('Chrome · Mac');
    expect(deviceLabel('Linux PC', 'https://updates.push.services.mozilla.com/wpush/v2/x')).toBe('Firefox · Linux PC');
    expect(deviceLabel('Linux PC', 'https://push.mozaws.net/wpush/v2/x')).toBe('Firefox · Linux PC');
    expect(deviceLabel('iPhone', 'https://web.push.apple.com/x')).toBe('Safari · iPhone');
    expect(deviceLabel('Windows', 'https://notify.windows.com/x')).toBe('Edge · Windows');
  });

  it.each([
    'https://fcm.googleapis.com.evil.com/x',
    'https://evil.com/fcm.googleapis.com/x',
    'https://fcm.googleapis.com@evil.com/x',
    'https://evil.com/?next=https://push.apple.com/x',
    'not a URL with mozilla.com',
    'javascript://notify.windows.com/x',
  ])('does not infer a browser from a foreign or invalid endpoint: %s', (endpoint) => {
    expect(deviceLabel('Device', endpoint)).toBe('Device');
  });
});
