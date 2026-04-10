export type PaginatedResponse<T> = {
  current_page: number;
  data: T[];
  first_page_url: string;
  from: number | null;
  last_page: number;
  last_page_url: string;
  links: Array<{ url: string | null; label: string; active: boolean }>;
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number | null;
  total: number;
};

export type AuthUser = {
  id: number;
  tenant_id?: number | null;
  branch_id?: number | null;
  name: string;
  email: string;
  role: "super_admin" | "gym_admin" | "staff";
  status: "active" | "inactive";
};

export type LoginResponse = {
  message: string;
  user: AuthUser;
};

export type Tenant = {
  id: number;
  name: string;
  slug: string;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  status: "active" | "inactive";
};

export type Branch = {
  id: number;
  tenant_id: number;
  name: string;
  code?: string | null;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  status: "active" | "inactive";
};

export type Member = {
  id: number;
  tenant_id: number;
  branch_id: number;
  member_code: string;
  first_name: string;
  last_name: string;
  email?: string | null;
  phone?: string | null;
  status: "active" | "inactive" | "blocked";
};

export type MembershipPlan = {
  id: number;
  tenant_id: number;
  branch_id?: number | null;
  name: string;
  description?: string | null;
  duration_type: "day" | "week" | "month" | "year" | "session";
  duration_value: number;
  price: string | number;
  session_limit?: number | null;
  freeze_limit_days?: number | null;
  status: "active" | "inactive";
};

export type Subscription = {
  id: number;
  tenant_id: number;
  branch_id: number;
  member_id: number;
  membership_plan_id: number;
  start_date: string;
  end_date: string;
  amount: string | number;
  sessions_remaining?: number | null;
  payment_status: "unpaid" | "partial" | "paid";
  status: "pending" | "active" | "expired" | "frozen" | "cancelled";
  member?: Member;
  membership_plan?: MembershipPlan;
};

export type DashboardStats = {
  active_members: number;
  expired_subscriptions: number;
  today_checkins: number;
  monthly_revenue: number;
};

export type DashboardResponse = {
  stats: DashboardStats;
};

export type DashboardPreviewState = {
  isPreview: boolean;
};
