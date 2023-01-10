/* eslint-disable no-secrets/no-secrets */
import {
  BookContacts28Filled,
  BookQuestionMark24Filled,
  CalendarLtr24Filled,
  Comment24Filled,
  DeviceMeetingRoomRemote24Filled,
  DocumentMultiple24Filled,
  DocumentSettings20Filled,
  Flow20Filled,
  Grid24Filled,
  ImageArrowBack24Filled,
  Important24Filled,
  Navigation24Filled,
  News24Filled,
  PeopleSearch24Filled,
  PeopleTeam24Filled,
  Person24Filled,
  PersonBoard24Filled,
  PuzzlePiece24Filled,
  ShieldKeyhole24Filled,
  Sparkle24Filled,
  StarLineHorizontal324Filled,
  TaskListSquareLtr24Regular,
} from "@vicons/fluent";
import type { Component } from "vue";

import type { Models } from "../enums";

const icons: Record<keyof typeof Models, Component> = {
  BANNER: ImageArrowBack24Filled,
  CALENDAR: CalendarLtr24Filled,
  CONTACT: BookContacts28Filled,
  COMMENT: Comment24Filled,
  DOING: Important24Filled,
  DUTY: PuzzlePiece24Filled,
  GOAL: StarLineHorizontal324Filled,
  GOAL_GROUP: Sparkle24Filled,
  INSTITUTION: PeopleTeam24Filled,
  MAINPAGE: Grid24Filled,
  MATTER: BookQuestionMark24Filled,
  MEETING: DeviceMeetingRoomRemote24Filled,
  NAVIGATION: Navigation24Filled,
  NEWS: News24Filled,
  PAGE: DocumentMultiple24Filled,
  PERMISSION: ShieldKeyhole24Filled,
  ROLE: PersonBoard24Filled,
  RELATIONSHIP: Flow20Filled,
  SAZININGAIEXAM: PeopleSearch24Filled,
  SHAREPOINTDOCUMENT: DocumentMultiple24Filled,
  TASK: TaskListSquareLtr24Regular,
  TYPE: DocumentSettings20Filled,
  USER: Person24Filled,
};

export default icons;
