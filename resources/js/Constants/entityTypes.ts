import {
  Bell,
  Bookmark,
  BookOpen,
  Box,
  Building2,
  Calendar,
  ClipboardList,
  Files,
  FileText,
  GraduationCap,
  Image,
  LayoutGrid,
  Library,
  List,
  ListChecks,
  Mails,
  Menu,
  MessageSquare,
  Newspaper,
  NotebookPen,
  Presentation,
  Puzzle,
  ShieldCheck,
  SquareUser,
  Tag,
  Tags,
  Target,
  User,
  UserRoundCheck,
  UserSearch,
  UsersRound,
  Workflow,
} from '@lucide/vue';
import type { Component } from 'vue';

import { ModelEnum } from '@/Types/enums';

export type EntityCategory = 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8;

export interface EntityTypeDefinition {
  type: ModelEnum;
  label: string;
  pluralLabel: string;
  icon: Component;
  category: EntityCategory;
}

export const entityTypeRegistry: Record<ModelEnum, EntityTypeDefinition> = {
  [ModelEnum.LEARNING_STEP]: entry(ModelEnum.LEARNING_STEP, 'learning.entity.singular', 'learning.entity.plural', BookOpen, 6),
  [ModelEnum.KNOWLEDGE_ITEM]: entry(ModelEnum.KNOWLEDGE_ITEM, 'learning.pages.knowledge_item', 'learning.wiki.articles', Library, 6),
  [ModelEnum.AGENDA_ITEM]: entry(ModelEnum.AGENDA_ITEM, 'Darbotvarkės klausimas', 'Darbotvarkės klausimai', FileText, 1),
  [ModelEnum.BANNER]: entry(ModelEnum.BANNER, 'Skydelis', 'Skydeliai', Image, 5),
  [ModelEnum.CALENDAR]: entry(ModelEnum.CALENDAR, 'Renginys', 'Renginiai', Calendar, 6),
  [ModelEnum.COMMENT]: entry(ModelEnum.COMMENT, 'Komentaras', 'Komentarai', MessageSquare, 3),
  [ModelEnum.DOCUMENT]: entry(ModelEnum.DOCUMENT, 'Dokumentas', 'Dokumentai', Files, 7),
  [ModelEnum.DUTIABLE]: entry(ModelEnum.DUTIABLE, 'Pareigybės laikotarpis', 'Pareigybių laikotarpiai', UserRoundCheck, 4),
  [ModelEnum.DUTY]: entry(ModelEnum.DUTY, 'Pareigybė', 'Pareigybės', Puzzle, 4),
  [ModelEnum.EVENT_TYPE]: entry(ModelEnum.EVENT_TYPE, 'Renginio tipas', 'Renginių tipai', List, 6),
  [ModelEnum.FILE]: entry(ModelEnum.FILE, 'Failas', 'Failai', Files, 7),
  [ModelEnum.FORM]: entry(ModelEnum.FORM, 'Forma', 'Formos', ClipboardList, 6),
  [ModelEnum.GOAL]: entry(ModelEnum.GOAL, 'Tikslas', 'Tikslai', Target, 6),
  [ModelEnum.INSTITUTION]: entry(ModelEnum.INSTITUTION, 'Institucija', 'Institucijos', UsersRound, 8),
  [ModelEnum.MAILING_LIST]: entry(ModelEnum.MAILING_LIST, 'Konferencija', 'Konferencijos', Mails, 3),
  [ModelEnum.MEETING]: entry(ModelEnum.MEETING, 'Posėdis', 'Posėdžiai', Presentation, 1),
  [ModelEnum.NAVIGATION]: entry(ModelEnum.NAVIGATION, 'Navigacijos nuoroda', 'Navigacijos nuorodos', Menu, 5),
  [ModelEnum.NEWS]: entry(ModelEnum.NEWS, 'Naujiena', 'Naujienos', Newspaper, 5),
  [ModelEnum.QUICK_LINK]: entry(ModelEnum.QUICK_LINK, 'Greitoji nuoroda', 'Greitosios nuorodos', LayoutGrid, 5),
  [ModelEnum.PAGE]: entry(ModelEnum.PAGE, 'Puslapis', 'Puslapiai', Files, 5),
  [ModelEnum.PERMISSION]: entry(ModelEnum.PERMISSION, 'Leidimas', 'Leidimai', ShieldCheck, 4),
  [ModelEnum.PROBLEM]: entry(ModelEnum.PROBLEM, 'Problema', 'Problemos', Bell, 6),
  [ModelEnum.RELATIONSHIP]: entry(ModelEnum.RELATIONSHIP, 'Ryšys', 'Ryšiai', Workflow, 4),
  [ModelEnum.RESERVATION]: entry(ModelEnum.RESERVATION, 'Rezervacija', 'Rezervacijos', Bookmark, 2),
  [ModelEnum.RESERVATION_RESOURCE]: entry(ModelEnum.RESERVATION_RESOURCE, 'Rezervuojamas išteklius', 'Rezervuojami ištekliai', Box, 2),
  [ModelEnum.RESOURCE]: entry(ModelEnum.RESOURCE, 'Išteklius', 'Ištekliai', Box, 2),
  [ModelEnum.ROLE]: entry(ModelEnum.ROLE, 'responsibilities.entity.singular', 'responsibilities.entity.plural', SquareUser, 4),
  [ModelEnum.STUDY_PROGRAM]: entry(ModelEnum.STUDY_PROGRAM, 'Studijų programa', 'Studijų programos', GraduationCap, 8),
  [ModelEnum.STUDY_SET]: entry(ModelEnum.STUDY_SET, 'Studijų komplektas', 'Studijų komplektai', NotebookPen, 8),
  [ModelEnum.TAG]: entry(ModelEnum.TAG, 'Žyma', 'Žymos', Tag, 5),
  [ModelEnum.TASK]: entry(ModelEnum.TASK, 'Užduotis', 'Užduotys', ListChecks, 6),
  [ModelEnum.TENANT]: entry(ModelEnum.TENANT, 'Padalinys', 'Padaliniai', UserSearch, 8),
  [ModelEnum.INSTITUTION_TYPE]: entry(ModelEnum.INSTITUTION_TYPE, 'types.institutionType.singular', 'types.institutionType.title', Building2, 8),
  [ModelEnum.DUTY_TYPE]: entry(ModelEnum.DUTY_TYPE, 'types.dutyType.singular', 'types.dutyType.title', Tags, 4),
  [ModelEnum.USER]: entry(ModelEnum.USER, 'Narys', 'Nariai', User, 3),
};

function entry(
  type: ModelEnum,
  label: string,
  pluralLabel: string,
  icon: Component,
  category: EntityCategory,
): EntityTypeDefinition {
  return { type, label, pluralLabel, icon, category };
}

export function getEntityTypeDefinition(type: ModelEnum | keyof typeof ModelEnum | string): EntityTypeDefinition | null {
  if (type in ModelEnum) {
    return entityTypeRegistry[ModelEnum[type as keyof typeof ModelEnum]];
  }

  // Pages also name types by their translation key (`eventType`, `studyProgram`).
  const snake = type.replace(/([a-z])([A-Z])/g, '$1_$2').toLowerCase();

  return entityTypeRegistry[type as ModelEnum] ?? entityTypeRegistry[snake as ModelEnum] ?? null;
}
