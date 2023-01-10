/* eslint-disable no-secrets/no-secrets */
import {
  BookContacts28Regular,
  BookQuestionMark24Regular,
  CalendarLtr24Regular,
  Comment24Regular,
  DeviceMeetingRoomRemote24Regular,
  DocumentBulletList24Regular,
  DocumentMultiple24Regular,
  DocumentSettings20Regular,
  Flow20Regular,
  Grid24Regular,
  ImageArrowBack24Regular,
  Important24Regular,
  Navigation24Regular,
  News24Regular,
  PeopleSearch24Regular,
  PeopleTeam24Regular,
  Person24Regular,
  PersonBoard24Regular,
  PuzzlePiece24Regular,
  ShieldKeyhole24Regular,
  Sparkle24Regular,
  StarLineHorizontal324Regular,
  TaskListSquareLtr24Regular,
} from "@vicons/fluent";
import type { Component } from "vue";

import type { Models } from "../enums";

const icons: Record<keyof typeof Models, Component> = {
  BANNER: ImageArrowBack24Regular,
  CALENDAR: CalendarLtr24Regular,
  CONTACT: BookContacts28Regular,
  COMMENT: Comment24Regular,
  DOING: Important24Regular,
  DUTY: PuzzlePiece24Regular,
  GOAL: StarLineHorizontal324Regular,
  GOAL_GROUP: Sparkle24Regular,
  INSTITUTION: PeopleTeam24Regular,
  MAINPAGE: Grid24Regular,
  MATTER: BookQuestionMark24Regular,
  MEETING: DeviceMeetingRoomRemote24Regular,
  NAVIGATION: Navigation24Regular,
  NEWS: News24Regular,
  PAGE: DocumentMultiple24Regular,
  PERMISSION: ShieldKeyhole24Regular,
  ROLE: PersonBoard24Regular,
  RELATIONSHIP: Flow20Regular,
  SAZININGAIEXAM: PeopleSearch24Regular,
  SHAREPOINTDOCUMENT: DocumentBulletList24Regular,
  TASK: TaskListSquareLtr24Regular,
  TYPE: DocumentSettings20Regular,
  USER: Person24Regular,
};

export default icons;
