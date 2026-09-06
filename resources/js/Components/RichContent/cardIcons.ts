import type { Component } from 'vue';

import IconUsers from '~icons/fluent/people24-regular';
import IconTrendingUp from '~icons/fluent/arrow-trending-lines24-regular';
import IconBookOpen from '~icons/fluent/book-open24-regular';
import IconAward from '~icons/fluent/trophy24-regular';
import IconDollarSign from '~icons/fluent/money24-regular';
import IconExternalLink from '~icons/fluent/open24-regular';
import IconPalette from '~icons/fluent/paint-brush24-regular';
import IconInfo from '~icons/fluent/info24-regular';
import IconLightbulb from '~icons/fluent/lightbulb24-regular';
import IconStar from '~icons/fluent/star24-regular';
import IconHeart from '~icons/fluent/heart24-regular';
import IconMessageCircle from '~icons/fluent/chat24-regular';
import IconShare from '~icons/fluent/share24-regular';
import IconDownload from '~icons/fluent/arrow-download24-regular';
import IconUpload from '~icons/fluent/arrow-upload24-regular';
import IconSettings from '~icons/fluent/settings24-regular';
import IconShield from '~icons/fluent/shield24-regular';
import IconZap from '~icons/fluent/flash24-regular';
import IconTarget from '~icons/fluent/target-arrow24-regular';
import IconRocket from '~icons/fluent/rocket24-regular';
import IconGlobe from '~icons/fluent/globe24-regular';
import IconPhone from '~icons/fluent/call24-regular';
import IconMail from '~icons/fluent/mail24-regular';
import IconMapPin from '~icons/fluent/location24-regular';
import IconCalendar from '~icons/fluent/calendar-ltr24-regular';
import IconClock from '~icons/fluent/clock24-regular';
import IconUser from '~icons/fluent/person24-regular';
import IconBriefcase from '~icons/fluent/briefcase24-regular';
import IconGraduationCap from '~icons/fluent/hat-graduation24-regular';
import IconChevronDown from '~icons/fluent/chevron-down24-regular';
import IconChevronRight from '~icons/fluent/chevron-right24-regular';
import IconArrowRight from '~icons/fluent/arrow-right24-regular';
import IconPlay from '~icons/fluent/play24-regular';
import IconRss from '~icons/fluent/rss24-regular';

/**
 * CMS-stored icon names (card-stack / carousel-slide-deck) resolved to bundled Fluent
 * icons. Keep the `value` set stable — it's persisted in `content_parts.json_content`.
 * Each `labelKey` resolves under `lang/admin/{lt,en}/rich-content.php`.
 */
export const CARD_ICON_OPTIONS: { value: string; labelKey: string; icon: Component }[] = [
  { value: 'users', labelKey: 'rich-content.users', icon: IconUsers },
  { value: 'trending-up', labelKey: 'rich-content.trending_up', icon: IconTrendingUp },
  { value: 'book-open', labelKey: 'rich-content.book_open', icon: IconBookOpen },
  { value: 'award', labelKey: 'rich-content.award', icon: IconAward },
  { value: 'dollar-sign', labelKey: 'rich-content.dollar_sign', icon: IconDollarSign },
  { value: 'external-link', labelKey: 'rich-content.external_link', icon: IconExternalLink },
  { value: 'palette', labelKey: 'rich-content.palette', icon: IconPalette },
  { value: 'info', labelKey: 'rich-content.info', icon: IconInfo },
  { value: 'lightbulb', labelKey: 'rich-content.lightbulb', icon: IconLightbulb },
  { value: 'star', labelKey: 'rich-content.star', icon: IconStar },
  { value: 'heart', labelKey: 'rich-content.heart', icon: IconHeart },
  { value: 'message-circle', labelKey: 'rich-content.message_circle', icon: IconMessageCircle },
  { value: 'share-2', labelKey: 'rich-content.share', icon: IconShare },
  { value: 'download', labelKey: 'rich-content.download', icon: IconDownload },
  { value: 'upload', labelKey: 'rich-content.upload', icon: IconUpload },
  { value: 'settings', labelKey: 'rich-content.settings', icon: IconSettings },
  { value: 'shield', labelKey: 'rich-content.shield', icon: IconShield },
  { value: 'zap', labelKey: 'rich-content.zap', icon: IconZap },
  { value: 'target', labelKey: 'rich-content.target', icon: IconTarget },
  { value: 'rocket', labelKey: 'rich-content.rocket', icon: IconRocket },
  { value: 'globe', labelKey: 'rich-content.globe', icon: IconGlobe },
  { value: 'phone', labelKey: 'rich-content.phone', icon: IconPhone },
  { value: 'mail', labelKey: 'rich-content.mail', icon: IconMail },
  { value: 'map-pin', labelKey: 'rich-content.map_pin', icon: IconMapPin },
  { value: 'calendar', labelKey: 'rich-content.calendar', icon: IconCalendar },
  { value: 'clock', labelKey: 'rich-content.clock', icon: IconClock },
  { value: 'user', labelKey: 'rich-content.user', icon: IconUser },
  { value: 'briefcase', labelKey: 'rich-content.briefcase', icon: IconBriefcase },
  { value: 'graduation-cap', labelKey: 'rich-content.graduation_cap', icon: IconGraduationCap },
  { value: 'chevron-down', labelKey: 'rich-content.chevron_down', icon: IconChevronDown },
  { value: 'chevron-right', labelKey: 'rich-content.chevron_right', icon: IconChevronRight },
  { value: 'arrow-right', labelKey: 'rich-content.arrow_right', icon: IconArrowRight },
  { value: 'play', labelKey: 'rich-content.play', icon: IconPlay },
  { value: 'rss', labelKey: 'rich-content.rss', icon: IconRss },
];

export const CARD_ICON_MAP: Record<string, Component> = Object.fromEntries(
  CARD_ICON_OPTIONS.map(option => [option.value, option.icon]),
);

export const DEFAULT_CARD_ICON = IconInfo;
