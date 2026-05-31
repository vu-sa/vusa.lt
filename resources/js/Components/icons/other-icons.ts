// Tree-shakable other icons with regular and filled variants

// =============================================================================
// REGULAR ICONS - Import each icon only ONCE
// =============================================================================
import Alert24Regular from '~icons/fluent/alert24-regular';
import Home24Regular from '~icons/fluent/home24-regular';
import Image24Regular from '~icons/fluent/image24-regular';
import Settings24Regular from '~icons/fluent/settings24-regular';

// =============================================================================
// FILLED ICONS - Import each icon only ONCE
// =============================================================================
import Alert24Filled from '~icons/fluent/alert24-filled';
import Home24Filled from '~icons/fluent/home24-filled';
import Image24Filled from '~icons/fluent/image24-filled';
import Settings24Filled from '~icons/fluent/settings24-filled';

// =============================================================================
// TREE-SHAKABLE EXPORTS - Clean, concise naming
// =============================================================================
// FileIcon / FileIconFilled are exported by model-icons.ts — do not re-export here
export const HomeIcon = Home24Regular;
export const ImageIcon = Image24Regular;
export const NotificationIcon = Alert24Regular;
export const SettingIcon = Settings24Regular;

export const HomeIconFilled = Home24Filled;
export const ImageIconFilled = Image24Filled;
export const NotificationIconFilled = Alert24Filled;
export const SettingIconFilled = Settings24Filled;
