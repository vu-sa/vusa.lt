import { ModelEnum } from "./Types/enums";
import { pluralizeModels } from "@/Utils/String";
import Icons from "@/Types/Icons/regular";

export default [
  {
    title: "Puslapiai",
    icon: Icons.PAGE,
    // get label of enum
    key: pluralizeModels(ModelEnum.PAGE),
  },
  {
    title: "Naujienos",
    icon: Icons.NEWS,
    key: pluralizeModels(ModelEnum.NEWS),
  },
  {
    title: "Failai VU SA puslapyje",
    icon: Icons.FILE,
    key: pluralizeModels(ModelEnum.FILE),
  },
  {
    title: "Baneriai",
    icon: Icons.BANNER,
    key: pluralizeModels(ModelEnum.BANNER),
  },
  {
    title: "Kalendorius",
    icon: Icons.CALENDAR,
    key: pluralizeModels(ModelEnum.CALENDAR),
  },
  {
    title: "Greitieji mygtukai",
    icon: Icons.MAIN_PAGE,
    key: pluralizeModels(ModelEnum.MAIN_PAGE),
  },
  {
    title: "Navigacija",
    icon: Icons.NAVIGATION,
    key: pluralizeModels(ModelEnum.NAVIGATION),
  },
  {
    title: "Naudotojai",
    icon: Icons.USER,
    key: pluralizeModels(ModelEnum.USER),
  },
  {
    title: "Institucijos",
    icon: Icons.INSTITUTION,
    key: pluralizeModels(ModelEnum.INSTITUTION),
  },
  {
    title: "Svarstomi klausimai (temos)",
    icon: Icons.MATTER,
    key: pluralizeModels(ModelEnum.MATTER),
  },
  {
    title: "Susitikimai",
    icon: Icons.MEETING,
    key: pluralizeModels(ModelEnum.MEETING),
  },
  {
    title: "Susitikimo klausimai",
    icon: Icons.AGENDA_ITEM,
    key: pluralizeModels(ModelEnum.AGENDA_ITEM),
  },
  {
    title: "Veiklos (veiksmai)",
    icon: Icons.DOING,
    key: pluralizeModels(ModelEnum.DOING),
  },
  {
    title: "Pareigybės",
    icon: Icons.DUTY,
    key: pluralizeModels(ModelEnum.DUTY),
  },
  {
    title: "Tikslai",
    icon: Icons.GOAL,
    key: pluralizeModels(ModelEnum.GOAL),
  },
  {
    title: "Kontaktai",
    icon: Icons.CONTACT,
    key: pluralizeModels(ModelEnum.CONTACT),
  },
  {
    title: "Rezervacijos",
    icon: Icons.RESERVATION,
    key: pluralizeModels(ModelEnum.RESERVATION),
  },
  {
    title: "Ištekliai",
    icon: Icons.RESOURCE,
    key: pluralizeModels(ModelEnum.RESOURCE),
  },
  {
    title: "Komentarai",
    icon: Icons.COMMENT,
    key: "comments",
  },
  {
    title: "Dokumentai",
    icon: Icons.SHAREPOINT_FILE,
    key: pluralizeModels(ModelEnum.SHAREPOINT_FILE),
  },
  {
    title: "Užduotys",
    icon: Icons.TASK,
    key: pluralizeModels(ModelEnum.TASK),
  },
  {
    title: "Ryšiai",
    icon: Icons.RELATIONSHIP,
    key: pluralizeModels(ModelEnum.RELATIONSHIP),
  },
  {
    title: "Rolės",
    icon: Icons.ROLE,
    key: pluralizeModels(ModelEnum.ROLE),
  },
  {
    title: "Leidimai",
    icon: Icons.PERMISSION,
    key: pluralizeModels(ModelEnum.PERMISSION),
  },
];
