import type { InstallationPlatform } from '@/Components/Onboarding/types';

export function installationPlatform(userAgent: string, platform: string, touchPoints: number): InstallationPlatform {
  if (/iPad|iPhone|iPod/i.test(userAgent) || (platform === 'MacIntel' && touchPoints > 1)) return 'ios';
  return /Android/i.test(userAgent) ? 'android' : 'desktop';
}
