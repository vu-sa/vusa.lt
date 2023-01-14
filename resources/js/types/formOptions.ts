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
  doing: [
    "Susitikimas su studentais",
    "Planuotas posėdis",
    "Susitikimas su koordinatoriumi",
  ],
  doingStatus: ["Sukurtas", "Pabaigtas"],
};

export const modelTypes = {
  sharepointFile: [
    "Metodinė medžiaga",
    "Protokolai",
    "Pristatymai",
    "Šablonai",
    "Veiklą reglamentuojantys dokumentai",
  ],
  type: [
    uppercase(ModelEnum.INSTITUTION),
    uppercase(ModelEnum.DUTY),
    uppercase(ModelEnum.DOING),
  ],
};

export const modelStatus = {
  doing: ["Sukurtas", "Pabaigtas"],
  matter: ["Sukurtas", "Pabaigtas"],
};

export const modelStatusDefaults = {
  doing: ["Sukurtas", "Pabaigtas"],
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
