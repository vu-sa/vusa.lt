import { ModelEnum } from "@/Types/enums";

const uppercase = (string: string) => {
  return string[0].toUpperCase() + string.substring(1);
};

export const modelDefaults = {
  matter: [
    "Studijų tinklelio peržiūra",
    "Studentų nuomonės išnagrinėjimas posėdyje",
    "Dėstytojo keitimas",
  ],
  doing: ["Susitikimas su studentais", "Susitikimas su koordinatoriumi"],
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
    uppercase(ModelEnum.DOING),
    uppercase(ModelEnum.INSTITUTION),
  ],
};

export const modelStatus = {
  doing: ["Sukurtas", "Pabaigtas"],
  matter: ["Sukurtas", "Pabaigtas"],
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
