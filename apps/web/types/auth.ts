export type AuthUser = {
  id: number;
  tenant_id?: number | null;
  branch_id?: number | null;
  name: string;
  email: string;
  role: "super_admin" | "gym_admin" | "staff" | "member";
  status: "active" | "inactive";
};

export type MeResponse = {
  user: AuthUser | null;
};

export type LoginPayload = {
  email: string;
  password: string;
  remember?: boolean;
};
