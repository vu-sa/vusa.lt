import * as Descriptions from "@/Types/EntityDescriptions/DescriptionComponents";
import { ModelEnum } from "../enums";
import { pluralizeModels } from "@/Utils/String";
import Icons from "@/Types/Icons/regular";

export default [
  {
    title: "Puslapiai",
    icon: Icons.PAGE,
    // get label of enum
    key: pluralizeModels(ModelEnum.PAGE),
    description: Descriptions.PageDescription,
  },
  {
    title: "Naujienos",
    icon: Icons.NEWS,
    key: pluralizeModels(ModelEnum.NEWS),
    description: Descriptions.NewsDescription,
  },
  {
    title: "Baneriai",
    icon: Icons.BANNER,
    key: pluralizeModels(ModelEnum.BANNER),
    description: Descriptions.BannerDescription,
  },
  {
    title: "Kalendorius",
    icon: Icons.CALENDAR,
    key: pluralizeModels(ModelEnum.CALENDAR),
    description: Descriptions.CalendarDescription,
  },
  {
    title: "Greitieji mygtukai",
    icon: Icons.MAIN_PAGE,
    key: pluralizeModels(ModelEnum.MAIN_PAGE),
    description: Descriptions.MainPageDescription,
  },
  {
    title: "Navigacija",
    icon: Icons.NAVIGATION,
    key: pluralizeModels(ModelEnum.NAVIGATION),
    description: Descriptions.NavigationDescription,
  },
  {
    title: "Sąžiningai egzaminai",
    icon: Icons.SAZININGAI_EXAM,
    key: pluralizeModels(ModelEnum.SAZININGAI_EXAM),
    description: Descriptions.SaziningaiExamDescription,
  },
  {
    title: "Naudotojai",
    icon: Icons.USER,
    key: pluralizeModels(ModelEnum.USER),
    description: Descriptions.UserDescription,
  },
  {
    title: "Institucijos",
    icon: Icons.INSTITUTION,
    key: pluralizeModels(ModelEnum.INSTITUTION),
    description: Descriptions.InstitutionDescription,
  },
  {
    title: "Svarstomi klausimai (temos)",
    icon: Icons.MATTER,
    key: pluralizeModels(ModelEnum.MATTER),
    description: Descriptions.MatterDescription,
  },
  {
    title: "Susitikimai",
    icon: Icons.MEETING,
    key: pluralizeModels(ModelEnum.MEETING),
    description: Descriptions.MeetingDescription,
  },
  {
    title: "Susitikimo klausimai",
    icon: Icons.AGENDA_ITEM,
    key: pluralizeModels(ModelEnum.AGENDA_ITEM),
    description: Descriptions.AgendaItemDescription,
  },
  {
    title: "Veiklos (veiksmai)",
    icon: Icons.DOING,
    key: pluralizeModels(ModelEnum.DOING),
    description: Descriptions.DoingDescription,
  },
  {
    title: "Pareigybės",
    icon: Icons.DUTY,
    key: pluralizeModels(ModelEnum.DUTY),
    description: Descriptions.DutyDescription,
  },
  {
    title: "Tikslai",
    icon: Icons.GOAL,
    key: pluralizeModels(ModelEnum.GOAL),
    description: Descriptions.GoalDescription,
  },
  {
    title: "Kontaktai",
    icon: Icons.CONTACT,
    key: pluralizeModels(ModelEnum.CONTACT),
    description: Descriptions.ContactDescription,
  },
  {
    title: "Rezervacijos",
    icon: Icons.RESERVATION,
    key: pluralizeModels(ModelEnum.RESERVATION),
    description: Descriptions.ReservationDescription,
  },
  {
    title: "Ištekliai",
    icon: Icons.RESOURCE,
    key: pluralizeModels(ModelEnum.RESOURCE),
    description: Descriptions.ResourceDescription,
  },
  {
    title: "Komentarai",
    icon: Icons.COMMENT,
    key: "comments",
    description: Descriptions.CommentDescription,
  },
  {
    title: "Dokumentai",
    icon: Icons.SHAREPOINT_FILE,
    key: pluralizeModels(ModelEnum.SHAREPOINT_FILE),
    description: Descriptions.SharepointFileDescription,
  },
  {
    title: "Užduotys",
    icon: Icons.TASK,
    key: pluralizeModels(ModelEnum.TASK),
    description: Descriptions.TaskDescription,
  },
  {
    title: "Ryšiai",
    icon: Icons.RELATIONSHIP,
    key: pluralizeModels(ModelEnum.RELATIONSHIP),
    description: Descriptions.RelationshipDescription,
  },
  {
    title: "Rolės",
    icon: Icons.ROLE,
    key: pluralizeModels(ModelEnum.ROLE),
    description: Descriptions.RoleDescription,
  },
  {
    title: "Leidimai",
    icon: Icons.PERMISSION,
    key: pluralizeModels(ModelEnum.PERMISSION),
    description: Descriptions.PermissionDescription,
  },
];
