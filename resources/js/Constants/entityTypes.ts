import type { Component } from 'vue';

import {
  AgendaItemIcon,
  AgendaItemIconFilled,
  BannerIcon,
  BannerIconFilled,
  CalendarIcon,
  CalendarIconFilled,
  CommentIcon,
  CommentIconFilled,
  DocumentIcon,
  DocumentIconFilled,
  DutiableIcon,
  DutiableIconFilled,
  DutyIcon,
  DutyIconFilled,
  EventTypeIcon,
  EventTypeIconFilled,
  FileIcon,
  FileIconFilled,
  FormIcon,
  FormIconFilled,
  InstitutionIcon,
  InstitutionIconFilled,
  MeetingIcon,
  MeetingIconFilled,
  NavigationIcon,
  NavigationIconFilled,
  NewsIcon,
  NewsIconFilled,
  PageIcon,
  PageIconFilled,
  PermissionIcon,
  PermissionIconFilled,
  ProblemIcon,
  ProblemIconFilled,
  QuickLinkIcon,
  QuickLinkIconFilled,
  RelationshipableIcon,
  RelationshipableIconFilled,
  RelationshipIcon,
  RelationshipIconFilled,
  ReservationIcon,
  ReservationIconFilled,
  ReservationResourceIcon,
  ReservationResourceIconFilled,
  ResourceIcon,
  ResourceIconFilled,
  RoleIcon,
  RoleIconFilled,
  SharepointFileableIcon,
  SharepointFileableIconFilled,
  SharepointFileIcon,
  SharepointFileIconFilled,
  StudyProgramIcon,
  StudyProgramIconFilled,
  StudySetIcon,
  StudySetIconFilled,
  TagIcon,
  TagIconFilled,
  TaskIcon,
  TaskIconFilled,
  TenantIcon,
  TenantIconFilled,
  TypeIcon,
  TypeIconFilled,
  UserIcon,
  UserIconFilled,
} from '@/Components/icons/model-icons';
import { ModelEnum } from '@/Types/enums';

export type EntityCategory = 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8;

export interface EntityTypeDefinition {
  type: ModelEnum;
  label: string;
  pluralLabel: string;
  icon: Component;
  iconFilled: Component;
  category: EntityCategory;
}

export const entityTypeRegistry: Record<ModelEnum, EntityTypeDefinition> = {
  [ModelEnum.AGENDA_ITEM]: entry(ModelEnum.AGENDA_ITEM, 'Darbotvarkės klausimas', 'Darbotvarkės klausimai', AgendaItemIcon, AgendaItemIconFilled, 1),
  [ModelEnum.BANNER]: entry(ModelEnum.BANNER, 'Skydelis', 'Skydeliai', BannerIcon, BannerIconFilled, 5),
  [ModelEnum.CALENDAR]: entry(ModelEnum.CALENDAR, 'Renginys', 'Renginiai', CalendarIcon, CalendarIconFilled, 6),
  [ModelEnum.COMMENT]: entry(ModelEnum.COMMENT, 'Komentaras', 'Komentarai', CommentIcon, CommentIconFilled, 3),
  [ModelEnum.DOCUMENT]: entry(ModelEnum.DOCUMENT, 'Dokumentas', 'Dokumentai', DocumentIcon, DocumentIconFilled, 7),
  [ModelEnum.DUTIABLE]: entry(ModelEnum.DUTIABLE, 'Pareigybės laikotarpis', 'Pareigybių laikotarpiai', DutiableIcon, DutiableIconFilled, 4),
  [ModelEnum.DUTY]: entry(ModelEnum.DUTY, 'Pareigybė', 'Pareigybės', DutyIcon, DutyIconFilled, 4),
  [ModelEnum.EVENT_TYPE]: entry(ModelEnum.EVENT_TYPE, 'Renginio tipas', 'Renginių tipai', EventTypeIcon, EventTypeIconFilled, 6),
  [ModelEnum.FILE]: entry(ModelEnum.FILE, 'Failas', 'Failai', FileIcon, FileIconFilled, 7),
  [ModelEnum.FORM]: entry(ModelEnum.FORM, 'Forma', 'Formos', FormIcon, FormIconFilled, 6),
  [ModelEnum.INSTITUTION]: entry(ModelEnum.INSTITUTION, 'Institucija', 'Institucijos', InstitutionIcon, InstitutionIconFilled, 8),
  [ModelEnum.MEETING]: entry(ModelEnum.MEETING, 'Posėdis', 'Posėdžiai', MeetingIcon, MeetingIconFilled, 1),
  [ModelEnum.NAVIGATION]: entry(ModelEnum.NAVIGATION, 'Navigacijos nuoroda', 'Navigacijos nuorodos', NavigationIcon, NavigationIconFilled, 5),
  [ModelEnum.NEWS]: entry(ModelEnum.NEWS, 'Naujiena', 'Naujienos', NewsIcon, NewsIconFilled, 5),
  [ModelEnum.QUICK_LINK]: entry(ModelEnum.QUICK_LINK, 'Greitoji nuoroda', 'Greitosios nuorodos', QuickLinkIcon, QuickLinkIconFilled, 5),
  [ModelEnum.PAGE]: entry(ModelEnum.PAGE, 'Puslapis', 'Puslapiai', PageIcon, PageIconFilled, 5),
  [ModelEnum.PERMISSION]: entry(ModelEnum.PERMISSION, 'Leidimas', 'Leidimai', PermissionIcon, PermissionIconFilled, 4),
  [ModelEnum.PROBLEM]: entry(ModelEnum.PROBLEM, 'Problema', 'Problemos', ProblemIcon, ProblemIconFilled, 6),
  [ModelEnum.RELATIONSHIP]: entry(ModelEnum.RELATIONSHIP, 'Ryšys', 'Ryšiai', RelationshipIcon, RelationshipIconFilled, 4),
  [ModelEnum.RELATIONSHIPABLE]: entry(ModelEnum.RELATIONSHIPABLE, 'Ryšio objektas', 'Ryšių objektai', RelationshipableIcon, RelationshipableIconFilled, 4),
  [ModelEnum.RESERVATION]: entry(ModelEnum.RESERVATION, 'Rezervacija', 'Rezervacijos', ReservationIcon, ReservationIconFilled, 2),
  [ModelEnum.RESERVATION_RESOURCE]: entry(ModelEnum.RESERVATION_RESOURCE, 'Rezervuojamas išteklius', 'Rezervuojami ištekliai', ReservationResourceIcon, ReservationResourceIconFilled, 2),
  [ModelEnum.RESOURCE]: entry(ModelEnum.RESOURCE, 'Išteklius', 'Ištekliai', ResourceIcon, ResourceIconFilled, 2),
  [ModelEnum.ROLE]: entry(ModelEnum.ROLE, 'Rolė', 'Rolės', RoleIcon, RoleIconFilled, 4),
  [ModelEnum.SHAREPOINT_FILE]: entry(ModelEnum.SHAREPOINT_FILE, 'SharePoint failas', 'SharePoint failai', SharepointFileIcon, SharepointFileIconFilled, 7),
  [ModelEnum.SHAREPOINT_FILEABLE]: entry(ModelEnum.SHAREPOINT_FILEABLE, 'SharePoint failo objektas', 'SharePoint failų objektai', SharepointFileableIcon, SharepointFileableIconFilled, 7),
  [ModelEnum.STUDY_PROGRAM]: entry(ModelEnum.STUDY_PROGRAM, 'Studijų programa', 'Studijų programos', StudyProgramIcon, StudyProgramIconFilled, 8),
  [ModelEnum.STUDY_SET]: entry(ModelEnum.STUDY_SET, 'Studijų komplektas', 'Studijų komplektai', StudySetIcon, StudySetIconFilled, 8),
  [ModelEnum.TAG]: entry(ModelEnum.TAG, 'Žyma', 'Žymos', TagIcon, TagIconFilled, 5),
  [ModelEnum.TASK]: entry(ModelEnum.TASK, 'Užduotis', 'Užduotys', TaskIcon, TaskIconFilled, 6),
  [ModelEnum.TENANT]: entry(ModelEnum.TENANT, 'Padalinys', 'Padaliniai', TenantIcon, TenantIconFilled, 8),
  [ModelEnum.TYPE]: entry(ModelEnum.TYPE, 'Tipas', 'Tipai', TypeIcon, TypeIconFilled, 4),
  [ModelEnum.USER]: entry(ModelEnum.USER, 'Narys', 'Nariai', UserIcon, UserIconFilled, 3),
};

function entry(
  type: ModelEnum,
  label: string,
  pluralLabel: string,
  icon: Component,
  iconFilled: Component,
  category: EntityCategory,
): EntityTypeDefinition {
  return { type, label, pluralLabel, icon, iconFilled, category };
}

export function getEntityTypeDefinition(type: ModelEnum | keyof typeof ModelEnum | string): EntityTypeDefinition | null {
  if (type in ModelEnum) {
    return entityTypeRegistry[ModelEnum[type as keyof typeof ModelEnum]];
  }

  return entityTypeRegistry[type as ModelEnum] ?? null;
}

export function getModelIcon(
  modelKey: keyof typeof ModelEnum,
  variant: 'regular' | 'filled' = 'regular',
): Component {
  const definition = getEntityTypeDefinition(modelKey);

  if (!definition) {
    throw new Error(`Unknown entity type: ${modelKey}`);
  }

  return variant === 'filled' ? definition.iconFilled : definition.icon;
}

export const modelIconMappingRegular = Object.fromEntries(
  Object.keys(ModelEnum).map(key => [key, entityTypeRegistry[ModelEnum[key as keyof typeof ModelEnum]].icon]),
) as Record<keyof typeof ModelEnum, Component>;

export const modelIconMappingFilled = Object.fromEntries(
  Object.keys(ModelEnum).map(key => [key, entityTypeRegistry[ModelEnum[key as keyof typeof ModelEnum]].iconFilled]),
) as Record<keyof typeof ModelEnum, Component>;

export const modelIconMapping = modelIconMappingRegular;
