import { ModelEnum } from "@/Types/enums";

const uppercase = (string: string) => {
  return string[0].toUpperCase() + string.substring(1);
};

export const modelDefaults = {
};

export const modelTypes = {
  relationshipable: [
    uppercase(ModelEnum.INSTITUTION),
    uppercase(ModelEnum.TYPE),
  ],
  sharepointFile: [
    "Ataskaitos",
    "Metodinė medžiaga",
    "Protokolai",
    "Pristatymai",
    "Šablonai",
    "Veiklą reglamentuojantys dokumentai",
  ],
  type: [
    uppercase(ModelEnum.DUTY),
    uppercase(ModelEnum.INSTITUTION),
    uppercase(ModelEnum.MEETING),
  ],
};

export const modelStatus = {
};

export const documentTemplate = {
  name: "",
  file: {
    mimeType: "",
  },
  createdDateTime: {
    date: "",
  },
  size: 0,
  type: "",
  description: "",
};
