import { ModelEnum } from './Types/enums';

import { pluralizeModels } from '@/Utils/String';
import { getModelIcon } from '@/Components/icons';

// UI metadata for models - titles in Lithuanian
const modelTitles: Record<keyof typeof ModelEnum, string> = {
  AGENDA_ITEM: 'Susitikimo klausimai',
  BANNER: 'Baneriai',
  CALENDAR: 'Kalendorius',
  CATEGORY: 'Kategorijos',
  COMMENT: 'Komentarai',
  DOCUMENT: 'Archyvo Sharepoint dokumentai',
  DUTIABLE: 'Pareigybės ėjimo laikotarpiai',
  DUTY: 'Pareigybės',
  FILE: 'Failai VU SA puslapyje',
  FORM: 'Formos',
  INSTITUTION: 'Institucijos',
  MEETING: 'Susitikimai',
  MEMBERSHIP: 'Narystės',
  NAVIGATION: 'Navigacija',
  NEWS: 'Naujienos',
  QUICK_LINK: 'Greitieji mygtukai',
  PAGE: 'Puslapiai',
  PERMISSION: 'Leidimai',
  RELATIONSHIP: 'Ryšiai',
  RELATIONSHIPABLE: 'Ryšių objektai',
  RESERVATION: 'Rezervacijos',
  RESERVATION_RESOURCE: 'Rezervacijos ištekliai',
  RESOURCE: 'Ištekliai',
  ROLE: 'Rolės',
  SHAREPOINT_FILE: 'Sharepoint atstovų dokumentai',
  SHAREPOINT_FILEABLE: 'Sharepoint failų objektai',
  STUDY_PROGRAM: 'Studijų programos',
  TAG: 'Žymės',
  TASK: 'Užduotys',
  TENANT: 'Padaliniai',
  TRAINING: 'Mokymai',
  TYPE: 'Tipai',
  USER: 'Naudotojai',
};

// Models that should be shown in the UI (based on original entities.ts)
const uiModels: (keyof typeof ModelEnum)[] = [
  'AGENDA_ITEM',
  'BANNER',
  'CALENDAR',
  'CATEGORY',
  'COMMENT',
  'DOCUMENT',
  'DUTIABLE',
  'DUTY',
  'FILE',
  'FORM',
  'INSTITUTION',
  'MEETING',
  'MEMBERSHIP',
  'NAVIGATION',
  'NEWS',
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
  'TRAINING',
  'TYPE',
  'USER',
];

// Generate entities array dynamically from ModelEnum
export default uiModels.map(modelKey => ({
  title: modelTitles[modelKey],
  icon: getModelIcon(modelKey), // Uses regular variant by default
  key: pluralizeModels(ModelEnum[modelKey]),
}));
