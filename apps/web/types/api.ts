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

export type { AuthUser } from "./auth";

export type LoginResponse = {
  message: string;
  user: import("./auth").AuthUser;
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

export type StaffUser = import("./auth").AuthUser & {
  created_at?: string | null;
  updated_at?: string | null;
  branch?: Branch | null;
  tenant?: Tenant | null;
};

export type Member = {
  id: number;
  tenant_id: number;
  branch_id: number;
  created_at?: string | null;
  updated_at?: string | null;
  member_code: string;
  first_name: string;
  last_name: string;
  email?: string | null;
  phone?: string | null;
  birthdate?: string | null;
  sex?: string | null;
  address?: string | null;
  emergency_contact_name?: string | null;
  emergency_contact_phone?: string | null;
  qr_code_value?: string | null;
  status: "active" | "inactive" | "blocked";
  joined_at?: string | null;
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

export type Payment = {
  id: number;
  tenant_id: number;
  branch_id: number;
  member_id: number;
  subscription_id?: number | null;
  gateway?: string | null;
  currency: string;
  gateway_checkout_session_id?: string | null;
  gateway_payment_id?: string | null;
  gateway_reference?: string | null;
  checkout_url?: string | null;
  gateway_metadata?: Record<string, string | null> | null;
  raw_response?: Record<string, unknown> | null;
  payment_date: string;
  paid_at?: string | null;
  amount: string | number;
  payment_method: "cash" | "gcash" | "bank_transfer" | "card" | "online";
  reference_no?: string | null;
  notes?: string | null;
  status: "pending" | "paid" | "failed" | "refunded";
  verification_status: "not_required" | "pending" | "verified" | "rejected";
  reviewed_at?: string | null;
  reviewed_by?: number | null;
  review_notes?: string | null;
  recorded_by?: number | null;
  created_at?: string | null;
  updated_at?: string | null;
  member?: Member;
  subscription?: Subscription;
  reviewer?: {
    id: number;
    name: string;
  } | null;
  proofs?: PaymentProof[];
};

export type PaymentProof = {
  id: number;
  payment_id: number;
  disk: string;
  path: string;
  original_name: string;
  mime_type?: string | null;
  file_size?: number | null;
  uploaded_by?: number | null;
  created_at?: string | null;
  updated_at?: string | null;
  url?: string | null;
  uploader?: {
    id: number;
    name: string;
  } | null;
};

export type DashboardStats = {
  active_members: number;
  expired_members: number;
  expired_subscriptions: number;
  new_members_this_month: number;
  today_checkins: number;
  payments_today: number;
  payments_this_month: number;
  income_today: number;
  monthly_revenue: number;
  upcoming_renewals: number;
};

export type Checkin = {
  id: number;
  tenant_id: number;
  branch_id: number;
  member_id: number;
  subscription_id: number;
  checkin_time: string;
  checkout_time?: string | null;
  source: "manual" | "qr" | "kiosk";
  status: string;
  verified_by?: number | null;
  created_at?: string | null;
  updated_at?: string | null;
  member?: Member;
  subscription?: Subscription;
  verifier?: {
    id: number;
    name: string;
  } | null;
};

export type DashboardResponse = {
  stats: DashboardStats;
};

export type DashboardPreviewState = {
  isPreview: boolean;
};
