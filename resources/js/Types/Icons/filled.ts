/* eslint-disable no-secrets/no-secrets */
import {
  Alert24Filled,
  BookContacts28Filled,
  BookQuestionMark24Filled,
  CalendarLtr24Filled,
  Comment24Filled,
  DeviceMeetingRoomRemote24Filled,
  DocumentBulletList24Filled,
  DocumentMultiple24Filled,
  DocumentSave24Filled,
  DocumentSettings20Filled,
  Flow20Filled,
  FlowchartCircle24Filled,
  Grid24Filled,
  Home24Filled,
  ImageArrowBack24Filled,
  Important24Filled,
  Navigation24Filled,
  News24Filled,
  PeopleSearch24Filled,
  PeopleTeam24Filled,
  Person24Filled,
  PersonBoard24Filled,
  PersonClock24Filled,
  PuzzlePiece24Filled,
  ShieldKeyhole24Filled,
  Sparkle24Filled,
  StarLineHorizontal324Filled,
  Tag24Filled,
  TaskListSquareLtr24Filled,
  TextBulletListSquare24Filled,
  TextField24Filled,
} from "@vicons/fluent";
import type { Component } from "vue";
import type { FormEnum, OtherIconEnum } from "../otherEnums";
import type { ModelEnum } from "../enums";

const modelIcons: Record<keyof typeof ModelEnum, Component> = {
  AGENDA_ITEM: DocumentBulletList24Filled,
  BANNER: ImageArrowBack24Filled,
  CALENDAR: CalendarLtr24Filled,
  CATEGORY: TextBulletListSquare24Filled,
  CHANGELOG_ITEM: DocumentBulletList24Filled,
  COMMENT: Comment24Filled,
  CONTACT: BookContacts28Filled,
  DOING: Important24Filled,
  DUTIABLE: PersonClock24Filled,
  DUTY: PuzzlePiece24Filled,
  GOAL: StarLineHorizontal324Filled,
  GOAL_GROUP: Sparkle24Filled,
  INSTITUTION: PeopleTeam24Filled,
  MAIN_PAGE: Grid24Filled,
  MATTER: BookQuestionMark24Filled,
  MEETING: DeviceMeetingRoomRemote24Filled,
  NAVIGATION: Navigation24Filled,
  NEWS: News24Filled,
  PAGE: DocumentMultiple24Filled,
  PERMISSION: ShieldKeyhole24Filled,
  RELATIONSHIP: Flow20Filled,
  RELATIONSHIPABLE: FlowchartCircle24Filled,
  ROLE: PersonBoard24Filled,
  SAZININGAI_EXAM: PeopleSearch24Filled,
  SAZININGAI_EXAM_FLOW: PeopleSearch24Filled,
  SAZININGAI_EXAM_OBSERVER: PeopleSearch24Filled,
  SHAREPOINT_FILE: DocumentMultiple24Filled,
  TAG: Tag24Filled,
  TASK: TaskListSquareLtr24Filled,
  TYPE: DocumentSettings20Filled,
  USER: Person24Filled,
};

const formIcons: Record<keyof typeof FormEnum, Component> = {
  DATE: CalendarLtr24Filled,
  SAVE: DocumentSave24Filled,
  TITLE: TextField24Filled,
};

const otherIcons: Record<keyof typeof OtherIconEnum, Component> = {
  HOME: Home24Filled,
  NOTIFICATION: Alert24Filled,
};

export default { ...modelIcons, ...formIcons, ...otherIcons };
