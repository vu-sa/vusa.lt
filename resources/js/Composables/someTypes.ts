export const modelNameDefaultOptions = {
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

export const modelTypeDefaultOptions = {
  sharepointFile: [
    "Metodinė medžiaga",
    "Protokolai",
    "Pristatymai",
    "Šablonai",
    "Veiklą reglamentuojantys dokumentai",
  ],
};

export const modelStatusDefaultOptions = {
  doing: ["Sukurtas", "Pabaigtas"],
};

export const contentTypeOptions = [
  {
    label: "Metodinė medžiaga",
    value: "Metodinė medžiaga",
  },
  {
    label: "Protokolai",
    value: "Protokolai",
  },
  {
    label: "Pristatymai",
    value: "Pristatymai",
  },
  {
    label: "Šablonai",
    value: "Šablonai",
  },
  {
    label: "Veiklą reglamentuojantys dokumentai",
    value: "Veiklą reglamentuojantys dokumentai",
  },
];

export const doingStatusOptions = [
  {
    label: "Sukurtas",
    value: "Sukurtas",
  },
  {
    label: "Pabaigtas",
    value: "Pabaigtas",
  },
];

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
