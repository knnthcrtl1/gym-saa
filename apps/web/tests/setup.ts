import { vi } from "vitest";
import { ref, reactive } from "vue";

// ---- Nuxt auto-import mocks ------------------------------------------------

// useState: returns a ref scoped by key (mimics Nuxt shared state)
const stateStore = new Map<string, ReturnType<typeof ref>>();

export function resetNuxtState() {
  stateStore.clear();
}

const useState = vi.fn(<T>(key: string, init?: () => T) => {
  if (!stateStore.has(key)) {
    stateStore.set(key, ref(init ? init() : undefined));
  }
  return stateStore.get(key)!;
});

// useCookie: returns a ref whose .value is the cookie value
const cookieStore = new Map<string, ReturnType<typeof ref>>();

const useCookie = vi.fn(<T = string | null>(name: string) => {
  if (!cookieStore.has(name)) {
    cookieStore.set(name, ref<T | null>(null));
  }
  return cookieStore.get(name)!;
});

export function resetCookieStore() {
  cookieStore.clear();
}

// useRuntimeConfig
const useRuntimeConfig = vi.fn(() => ({
  public: {
    apiBase: "http://localhost:8000",
    dashboardPreviewMode: true,
  },
}));

// navigateTo
const navigateTo = vi.fn(() => Promise.resolve());

// useRoute
const useRoute = vi.fn(() => ({
  query: {},
  params: {},
  path: "/",
  fullPath: "/",
  name: "",
  matched: [],
  meta: {},
  hash: "",
  redirectedFrom: undefined,
}));

// useRequestHeaders
const useRequestHeaders = vi.fn(() => ({}));

// definePageMeta (no-op at runtime)
const definePageMeta = vi.fn();

// $fetch mock
const $fetch = Object.assign(vi.fn(), {
  create: vi.fn(() => vi.fn()),
  raw: vi.fn(),
  native: vi.fn(),
});

// ---- Expose on globalThis so composables can use them without imports --------
Object.assign(globalThis, {
  useState,
  useCookie,
  useRuntimeConfig,
  navigateTo,
  useRoute,
  useRequestHeaders,
  definePageMeta,
  $fetch,
});

// Also expose for explicit imports from #imports
export {
  useState,
  useCookie,
  useRuntimeConfig,
  navigateTo,
  useRoute,
  useRequestHeaders,
  definePageMeta,
  $fetch,
};

// ---- Reset helpers ----------------------------------------------------------
// Call in beforeEach to get a clean slate between tests.
export function resetAllMocks() {
  resetNuxtState();
  resetCookieStore();
  vi.clearAllMocks();
}
