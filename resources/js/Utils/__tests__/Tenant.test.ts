import { describe, expect, it } from "vitest";
import { formatVuFacultyShortname } from "../Tenant";

describe("formatVuFacultyShortname", () => {
  it("formats tenant labels with the VU faculty shortname", () => {
    expect(formatVuFacultyShortname({
      shortname: "VU SA MIF",
      shortname_vu: "MIF",
    })).toBe("VU MIF");
  });

  it("falls back by removing SA from the full tenant shortname", () => {
    expect(formatVuFacultyShortname({
      shortname: "VU SA IF",
      shortname_vu: null,
    })).toBe("VU IF");
  });
});
