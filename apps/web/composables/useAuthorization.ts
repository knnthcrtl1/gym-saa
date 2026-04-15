import type { AuthUser } from "../types/auth";

export type PermissionOption = {
  value: string;
  label: string;
  description: string;
};

export const permissionOptions: PermissionOption[] = [
  {
    value: "dashboard.view",
    label: "Dashboard view",
    description: "View summary metrics and quick actions.",
  },
  {
    value: "members.view",
    label: "Members view",
    description: "Search and view member records.",
  },
  {
    value: "members.manage",
    label: "Members manage",
    description: "Create, edit, and delete members.",
  },
  {
    value: "plans.view",
    label: "Plans view",
    description: "View membership plan offers.",
  },
  {
    value: "plans.manage",
    label: "Plans manage",
    description: "Create, edit, and delete membership plans.",
  },
  {
    value: "subscriptions.view",
    label: "Subscriptions view",
    description: "View subscription lifecycle and statuses.",
  },
  {
    value: "subscriptions.manage",
    label: "Subscriptions manage",
    description: "Create, renew, and edit subscriptions.",
  },
  {
    value: "payments.view",
    label: "Payments view",
    description: "View payment ledger and proof records.",
  },
  {
    value: "payments.manage",
    label: "Payments manage",
    description: "Record manual payments and checkout links.",
  },
  {
    value: "payments.review",
    label: "Payments review",
    description: "Verify or reject proof-based payments.",
  },
  {
    value: "attendance.view",
    label: "Attendance view",
    description: "View daily check-in logs.",
  },
  {
    value: "attendance.manage",
    label: "Attendance manage",
    description: "Record manual attendance check-ins.",
  },
  {
    value: "staff.view",
    label: "Staff view",
    description: "View staff directory and assignments.",
  },
  {
    value: "staff.manage",
    label: "Staff manage",
    description: "Create, edit, suspend, and delete staff accounts.",
  },
  {
    value: "branches.view",
    label: "Branches view",
    description: "View branch records and ownership.",
  },
  {
    value: "branches.manage",
    label: "Branches manage",
    description: "Create and edit branches.",
  },
  {
    value: "tenants.view",
    label: "Tenants view",
    description: "View platform-wide gym accounts.",
  },
  {
    value: "tenants.manage",
    label: "Tenants manage",
    description: "Create and manage tenant accounts.",
  },
];

const permissionDefaults: Record<string, string[]> = {
  owner: [
    "dashboard.view",
    "members.view",
    "members.manage",
    "plans.view",
    "plans.manage",
    "subscriptions.view",
    "subscriptions.manage",
    "payments.view",
    "payments.manage",
    "payments.review",
    "attendance.view",
    "attendance.manage",
    "staff.view",
    "staff.manage",
    "branches.view",
    "branches.manage",
  ],
  manager: [
    "dashboard.view",
    "members.view",
    "members.manage",
    "plans.view",
    "subscriptions.view",
    "subscriptions.manage",
    "payments.view",
    "payments.manage",
    "attendance.view",
    "attendance.manage",
    "staff.view",
  ],
  front_desk: [
    "dashboard.view",
    "members.view",
    "members.manage",
    "plans.view",
    "subscriptions.view",
    "subscriptions.manage",
    "payments.view",
    "payments.manage",
    "attendance.view",
    "attendance.manage",
  ],
  trainer: ["members.view", "subscriptions.view", "attendance.view"],
  staff: [
    "dashboard.view",
    "members.view",
    "subscriptions.view",
    "attendance.view",
  ],
};

export const rolePresetLabels: Record<string, string> = {
  super_admin: "Super admin",
  owner: "Gym owner",
  gym_admin: "Gym owner",
  manager: "Manager",
  front_desk: "Front desk",
  trainer: "Trainer (legacy)",
  staff: "Staff",
};

export const formatRoleLabel = (
  role?: string | null,
  staffRole?: string | null,
) => {
  if (role === "super_admin") {
    return rolePresetLabels.super_admin;
  }

  if (role === "gym_admin") {
    return rolePresetLabels.gym_admin;
  }

  if (staffRole && rolePresetLabels[staffRole]) {
    return rolePresetLabels[staffRole];
  }

  return rolePresetLabels.staff;
};

const normalizePermissions = (user: AuthUser | null) => user?.permissions ?? [];

export const resolveDefaultPermissions = (
  role?: string | null,
  staffRole?: string | null,
) => {
  if (role === "super_admin") {
    return ["*"];
  }

  if (role === "gym_admin") {
    return [...(permissionDefaults.owner ?? [])];
  }

  if (staffRole && permissionDefaults[staffRole]) {
    return [...permissionDefaults[staffRole]];
  }

  return [...(permissionDefaults.staff ?? [])];
};

export const useAuthorization = () => {
  const user = useState<AuthUser | null>("auth.user", () => null);

  const hasPermission = (permission?: string | null) => {
    if (!permission) {
      return true;
    }

    if (user.value?.role === "super_admin") {
      return true;
    }

    const permissions = normalizePermissions(user.value);

    return permissions.includes("*") || permissions.includes(permission);
  };

  const hasAnyPermission = (permissions: string[]) =>
    permissions.some((permission) => hasPermission(permission));

  return {
    user,
    hasPermission,
    hasAnyPermission,
    permissionOptions,
  };
};
