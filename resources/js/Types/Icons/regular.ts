/* eslint-disable no-secrets/no-secrets */
import {
  Alert24Regular,
  BookContacts28Regular,
  BookQuestionMark24Regular,
  CalendarLtr24Regular,
  Comment24Regular,
  DeviceMeetingRoomRemote24Regular,
  DocumentBulletList24Regular,
  DocumentMultiple24Regular,
  DocumentSave24Regular,
  DocumentSettings20Regular,
  Flow20Regular,
  FlowchartCircle24Regular,
  Grid24Regular,
  Home24Regular,
  ImageArrowBack24Regular,
  Important24Regular,
  Navigation24Regular,
  News24Regular,
  PeopleSearch24Regular,
  PeopleTeam24Regular,
  Person24Regular,
  PersonBoard24Regular,
  PersonClock24Regular,
  PuzzlePiece24Regular,
  ShieldKeyhole24Regular,
  Sparkle24Regular,
  StarLineHorizontal324Regular,
  Tag24Regular,
  TaskListSquareLtr24Regular,
  TextBulletListSquare24Regular,
  TextCaseTitle24Regular,
  TextField24Regular,
} from "@vicons/fluent";
import type { Component } from "vue";
import type { FormEnum, OtherIconEnum } from "../otherEnums";
import type { ModelEnum } from "../enums";

const modelIcons: Record<keyof typeof ModelEnum, Component> = {
  AGENDA_ITEM: DocumentBulletList24Regular,
  BANNER: ImageArrowBack24Regular,
  CALENDAR: CalendarLtr24Regular,
  CATEGORY: TextBulletListSquare24Regular,
  COMMENT: Comment24Regular,
  CONTACT: BookContacts28Regular,
  DOING: Important24Regular,
  DUTIABLE: PersonClock24Regular,
  DUTY: PuzzlePiece24Regular,
  GOAL: StarLineHorizontal324Regular,
  GOAL_GROUP: Sparkle24Regular,
  INSTITUTION: PeopleTeam24Regular,
  MAIN_PAGE: Grid24Regular,
  MATTER: BookQuestionMark24Regular,
  MEETING: DeviceMeetingRoomRemote24Regular,
  NAVIGATION: Navigation24Regular,
  NEWS: News24Regular,
  PAGE: DocumentMultiple24Regular,
  PERMISSION: ShieldKeyhole24Regular,
  RELATIONSHIP: Flow20Regular,
  RELATIONSHIPABLE: FlowchartCircle24Regular,
  ROLE: PersonBoard24Regular,
  SAZININGAI_EXAM: PeopleSearch24Regular,
  SAZININGAI_EXAM_FLOW: PeopleSearch24Regular,
  SAZININGAI_EXAM_OBSERVER: PeopleSearch24Regular,
  SHAREPOINT_FILE: DocumentMultiple24Regular,
  TAG: Tag24Regular,
  TASK: TaskListSquareLtr24Regular,
  TYPE: DocumentSettings20Regular,
  USER: Person24Regular,
};

const formIcons: Record<keyof typeof FormEnum, Component> = {
  DATE: CalendarLtr24Regular,
  SAVE: DocumentSave24Regular,
  TITLE: TextField24Regular,
};

const otherIcons: Record<keyof typeof OtherIconEnum, Component> = {
  HOME: Home24Regular,
  NOTIFICATION: Alert24Regular,
};

export default { ...modelIcons, ...formIcons, ...otherIcons };
