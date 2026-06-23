export type AuthUser = {
  id: number;
  tenant_id?: number | null;
  branch_id?: number | null;
  name: string;
  email: string;
  role: "super_admin" | "gym_admin" | "staff" | "member";
  staff_role?: "owner" | "manager" | "front_desk" | "trainer" | null;
  status: "active" | "inactive";
  permissions: string[];
};

export type MeResponse = {
  user: AuthUser | null;
};

export type LoginPayload = {
  email: string;
  password: string;
  remember?: boolean;
};
