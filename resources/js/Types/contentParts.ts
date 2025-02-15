// Check enums.ts -> ContentParts for types that need to be declared

// Implemented

export type ImageGrid = {
  json_content: {
    colspan: "col-span-2" | "col-span-3" | "col-span-4" | "col-span-full";
    image: string
  }[];
  options: null
}

export type Tiptap = {
  json_content: {
    type: "doc";
    content: Record<string, any>[];
  };
  options: null
};

// eslint-disable-next-line @typescript-eslint/no-unused-vars
export type ShadcnAccordion = {
  json_content: {
    label: string;
    content: Tiptap["json_content"];
  }[];
  options: null
}

// eslint-disable-next-line @typescript-eslint/no-unused-vars
export type ShadcnCard = {
  json_content: Tiptap["json_content"];
  options: {
    color?: "zinc" | "red" | "yellow";
    variant?: "outline" | "soft";
    title?: string;
    isTitleColored?: boolean;
    showIcon?: boolean;
  };
}

// Not implemented

export type Calendar = {
  json_content: {}
  options: null
};

export type News = {
  json_content: {}
  options: null
}; 
