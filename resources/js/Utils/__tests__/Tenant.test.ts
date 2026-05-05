import { describe, expect, it } from "vitest";
import { formatVuFacultyShortname } from "../Tenant";

describe("formatVuFacultyShortname", () => {
  it("formats tenant labels with the VU faculty shortname", () => {
    expect(formatVuFacultyShortname({
      shortname: "VU SA MIF",
      shortname_vu: "MIF",
    })).toBe("VU MIF");
  });

  it("does not duplicate the VU prefix when the VU shortname already includes it", () => {
    expect(formatVuFacultyShortname({
      shortname: "VU SA CHGF",
      shortname_vu: "VU CHGF",
    })).toBe("VU CHGF");
  });

  it("falls back by removing SA from the full tenant shortname", () => {
    expect(formatVuFacultyShortname({
      shortname: "VU SA IF",
      shortname_vu: null,
    })).toBe("VU IF");
  });
});
