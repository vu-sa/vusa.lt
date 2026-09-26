const BROWSERS: [RegExp, string][] = [
  [/Edg(e|A|iOS)?\//, 'Edge'],
  [/OPR\/|Opera/, 'Opera'],
  [/SamsungBrowser\//, 'Samsung Internet'],
  [/Firefox\/|FxiOS\//, 'Firefox'],
  [/Chrome\/|CriOS\//, 'Chrome'],
  [/Safari\//, 'Safari'],
];

const SYSTEMS: [RegExp, string][] = [
  [/iPhone/, 'iPhone'],
  [/iPad/, 'iPad'],
  [/Android/, 'Android'],
  [/CrOS/, 'ChromeOS'],
  [/Windows/, 'Windows'],
  [/Macintosh|Mac OS X/, 'Mac'],
  [/Linux/, 'Linux'],
];

const PUSH_SERVICES: [string, string][] = [
  ['fcm.googleapis.com', 'Chrome'],
  ['mozilla.com', 'Firefox'],
  ['mozaws.net', 'Firefox'],
  ['push.apple.com', 'Safari'],
  ['notify.windows.com', 'Edge'],
];

const firstMatch = (value: string, patterns: [RegExp, string][]): string | null =>
  patterns.find(([pattern]) => pattern.test(value))?.[1] ?? null;

const pushService = (endpoint: string): string | null => {
  try {
    const url = new URL(endpoint);

    if (url.protocol !== 'https:' && url.protocol !== 'http:') {
      return null;
    }

    return PUSH_SERVICES.find(([domain]) =>
      url.hostname === domain || url.hostname.endsWith(`.${domain}`),
    )?.[1] ?? null;
  }
  catch {
    return null;
  }
};

/**
 * "Chrome · Android" from a user agent. Android's reduced UA names the model "K", so the model is
 * never used; the OS alone tells devices apart well enough.
 */
export function describeDevice(userAgent: string): string {
  const browser = firstMatch(userAgent, BROWSERS);
  const system = firstMatch(userAgent, SYSTEMS);

  return [browser, system].filter(Boolean).join(' · ') || 'Unknown Device';
}

/**
 * The name to show for a stored subscription. Names saved before the browser was recorded get it
 * from the push service in the endpoint (Opera, Brave and Samsung Internet also use FCM).
 */
export function deviceLabel(deviceName: string | null, endpoint: string): string | null {
  if (deviceName?.includes('·')) {
    return deviceName;
  }

  const browser = pushService(endpoint);

  return [browser, deviceName].filter(Boolean).join(' · ') || null;
}
