import { describe, it, expect, beforeEach } from "vitest";
import { resetAllMocks } from "../setup";
import type { AuthUser } from "../../types/auth";
import {
  useAuthorization,
  resolveDefaultPermissions,
  formatRoleLabel,
} from "../../composables/useAuthorization";

beforeEach(() => {
  resetAllMocks();
});

function setUser(user: AuthUser | null) {
  const state = (globalThis as any).useState("auth.user", () => null);
  state.value = user;
}

function makeUser(overrides: Partial<AuthUser> = {}): AuthUser {
  return {
    id: 1,
    tenant_id: 1,
    branch_id: 1,
    name: "Test User",
    email: "test@example.com",
    role: "staff",
    staff_role: null,
    status: "active",
    permissions: [],
    ...overrides,
  };
}

describe("useAuthorization", () => {
  describe("hasPermission()", () => {
    it("returns true when no permission is required (null/undefined)", () => {
      setUser(makeUser({ permissions: [] }));
      const { hasPermission } = useAuthorization();

      expect(hasPermission(null)).toBe(true);
      expect(hasPermission(undefined)).toBe(true);
      expect(hasPermission("")).toBe(true);
    });

    it("returns true when user has the specific permission", () => {
      setUser(makeUser({ permissions: ["dashboard.view", "members.view"] }));
      const { hasPermission } = useAuthorization();

      expect(hasPermission("dashboard.view")).toBe(true);
      expect(hasPermission("members.view")).toBe(true);
    });

    it("returns false when user lacks the permission", () => {
      setUser(makeUser({ permissions: ["dashboard.view"] }));
      const { hasPermission } = useAuthorization();

      expect(hasPermission("members.manage")).toBe(false);
      expect(hasPermission("staff.manage")).toBe(false);
    });

    it("super_admin has all permissions (role-based wildcard)", () => {
      setUser(makeUser({ role: "super_admin", permissions: [] }));
      const { hasPermission } = useAuthorization();

      expect(hasPermission("dashboard.view")).toBe(true);
      expect(hasPermission("staff.manage")).toBe(true);
      expect(hasPermission("tenants.manage")).toBe(true);
      expect(hasPermission("anything.at.all")).toBe(true);
    });

    it("user with wildcard '*' permission has all permissions", () => {
      setUser(makeUser({ role: "staff", permissions: ["*"] }));
      const { hasPermission } = useAuthorization();

      expect(hasPermission("dashboard.view")).toBe(true);
      expect(hasPermission("tenants.manage")).toBe(true);
    });

    it("returns false when user is null", () => {
      setUser(null);
      const { hasPermission } = useAuthorization();

      expect(hasPermission("dashboard.view")).toBe(false);
    });
  });

  describe("hasAnyPermission()", () => {
    it("returns true if user has at least one of the listed permissions", () => {
      setUser(makeUser({ permissions: ["members.view"] }));
      const { hasAnyPermission } = useAuthorization();

      expect(hasAnyPermission(["members.view", "staff.manage"])).toBe(true);
    });

    it("returns false if user has none of the listed permissions", () => {
      setUser(makeUser({ permissions: ["dashboard.view"] }));
      const { hasAnyPermission } = useAuthorization();

      expect(hasAnyPermission(["members.manage", "staff.manage"])).toBe(false);
    });
  });
});

describe("resolveDefaultPermissions()", () => {
  it("super_admin gets wildcard ['*']", () => {
    expect(resolveDefaultPermissions("super_admin")).toEqual(["*"]);
  });

  it("gym_admin gets owner-level defaults", () => {
    const perms = resolveDefaultPermissions("gym_admin");
    expect(perms).toContain("dashboard.view");
    expect(perms).toContain("staff.manage");
    expect(perms).toContain("branches.manage");
    expect(perms).toContain("audit_logs.view");
    // Should NOT have tenant-level perms
    expect(perms).not.toContain("tenants.view");
    expect(perms).not.toContain("tenants.manage");
  });

  it("staff role 'manager' gets manager defaults", () => {
    const perms = resolveDefaultPermissions("staff", "manager");
    expect(perms).toContain("dashboard.view");
    expect(perms).toContain("members.manage");
    expect(perms).toContain("staff.view");
    // Manager should NOT have staff.manage
    expect(perms).not.toContain("staff.manage");
  });

  it("staff role 'front_desk' gets front_desk defaults", () => {
    const perms = resolveDefaultPermissions("staff", "front_desk");
    expect(perms).toContain("attendance.manage");
    expect(perms).toContain("payments.manage");
    expect(perms).not.toContain("staff.view");
  });

  it("staff role 'trainer' gets limited defaults", () => {
    const perms = resolveDefaultPermissions("staff", "trainer");
    expect(perms).toEqual(["members.view", "subscriptions.view", "attendance.view"]);
  });

  it("unknown staff role falls back to 'staff' defaults", () => {
    const perms = resolveDefaultPermissions("staff", null);
    expect(perms).toContain("dashboard.view");
    expect(perms).toContain("members.view");
    expect(perms).not.toContain("members.manage");
  });

  it("custom permissions on user override defaults via normalizePermissions", () => {
    setUser(
      makeUser({
        role: "staff",
        staff_role: "front_desk",
        permissions: ["custom.permission"],
      }),
    );
    const { hasPermission } = useAuthorization();

    // The user's actual permissions array is what matters for hasPermission
    expect(hasPermission("custom.permission")).toBe(true);
    // Default front_desk permissions are NOT automatically merged
    expect(hasPermission("attendance.manage")).toBe(false);
  });
});

describe("formatRoleLabel()", () => {
  it("returns 'Super admin' for super_admin role", () => {
    expect(formatRoleLabel("super_admin")).toBe("Super admin");
  });

  it("returns 'Gym owner' for gym_admin role", () => {
    expect(formatRoleLabel("gym_admin")).toBe("Gym owner");
  });

  it("returns staff role label when staff role is known", () => {
    expect(formatRoleLabel("staff", "manager")).toBe("Manager");
    expect(formatRoleLabel("staff", "front_desk")).toBe("Front desk");
    expect(formatRoleLabel("staff", "trainer")).toBe("Trainer (legacy)");
  });

  it("falls back to 'Staff' for unknown staff role", () => {
    expect(formatRoleLabel("staff", null)).toBe("Staff");
    expect(formatRoleLabel("staff", "unknown_role")).toBe("Staff");
  });
});
