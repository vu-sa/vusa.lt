declare namespace App.Entities {
  export type AgendaItem = App.Models.Pivots.AgendaItem;
  export type Banner = App.Models.Banner;
  export type Calendar = App.Models.Calendar;
  export type Category = App.Models.Category;
  export type Comment = App.Models.Comment;
  export type Contact = App.Models.Contact;
  export type Doable = App.Models.Pivots.Doable;
  export type Doing = App.Models.Doing;
  export type Dutiable = App.Models.Pivots.Dutiable;

  export interface Duty extends App.Models.Duty {
    roles?: Array<App.Models.Role> | null; // manually added
    roles_count?: number | null; // manually added
  }

  export type Goal = App.Models.Goal;
  export type GoalGroup = App.Models.GoalGroup;
  export type GoalMatter = App.Models.Pivots.GoalMatter;
  export type Institution = App.Models.Institution;
  export type MainPage = App.Models.MainPage;
  export type Matter = App.Models.Matter;

  export interface Meeting extends Omit<App.Models.Meeting, "start_time"> {
    start_time: number; // casted to number
  }

  export type Navigation = App.Models.Navigation;
  export type News = App.Models.News;
  export type Padalinys = App.Models.Padalinys;
  export type Page = App.Models.Page;
  export type Permission = App.Models.Permission;
  export type Registration = App.Models.Registration;
  export type RegistrationForm = App.Models.RegistrationForm;
  export type Relationship = App.Models.Relationship;
  export type Relationshipable = App.Models.Pivots.Relationshipable;
  export type Role = App.Models.Role;
  export type SaziningaiExam = App.Models.SaziningaiExam;
  export type SaziningaiExamFlow = App.Models.SaziningaiExamFlow;
  export type SaziningaiExamObserver = App.Models.SaziningaiExamObserver;
  export type SharepointDocument = App.Models.SharepointDocument;
  export type Tag = App.Models.Tag;
  export type Task = App.Models.Task;
  export type Type = App.Models.Type;

  export interface User extends Omit<App.Models.User, "padaliniai"> {
    padaliniai?: Array<App.Models.Padalinys> | null; // manually added
    roles?: Array<App.Models.Role> | null; // manually added
    roles_count?: number | null; // manually added
  }
}
