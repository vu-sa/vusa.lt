import { camelCase } from 'lodash-es';

import { ModelEnum } from './Types/enums';

import { pluralizeModels } from '@/Utils/String';
import { entityTypeRegistry } from '@/Constants/entityTypes';

// Models that should be shown in the UI (based on original entities.ts)
const uiModels: (keyof typeof ModelEnum)[] = [
  'AGENDA_ITEM',
  'BANNER',
  'CALENDAR',
  'COMMENT',
  'DOCUMENT',
  'DUTIABLE',
  'DUTY',
  'FILE',
  'FORM',
  'INSTITUTION',
  'MEETING',
  'NAVIGATION',
  'NEWS',
  'PROBLEM',
  'QUICK_LINK',
  'PAGE',
  'PERMISSION',
  'RELATIONSHIP',
  'RESERVATION',
  'RESOURCE',
  'ROLE',
  'SHAREPOINT_FILE',
  'STUDY_PROGRAM',
  'TAG',
  'TASK',
  'TENANT',
  'TYPE',
  'USER',
];

// Generate entities array dynamically from ModelEnum
export default uiModels.map(modelKey => ({
  title: entityTypeRegistry[ModelEnum[modelKey]].pluralLabel,
  icon: entityTypeRegistry[ModelEnum[modelKey]].icon,
  key: pluralizeModels(camelCase(ModelEnum[modelKey])),
}));
