function readCookie(name: string): string | undefined {
  if (import.meta.server) return undefined;
  const match = document.cookie.match(
    new RegExp("(^|;\\s*)" + name + "=([^;]*)"),
  );
  return match?.[2] ? decodeURIComponent(match[2]) : undefined;
}

export const useApi = () => {
  const config = useRuntimeConfig();
  const forwardedHeaders = import.meta.server
    ? useRequestHeaders(["cookie", "x-xsrf-token"])
    : undefined;

  const api = $fetch.create({
    baseURL: `${config.public.apiBase}/api/v1`,
    credentials: "include",
    headers: {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
      ...forwardedHeaders,
    },
    onRequest({ options }) {
      const token = readCookie("XSRF-TOKEN");
      if (token) {
        const headers = new Headers(options.headers);
        headers.set("X-XSRF-TOKEN", token);
        options.headers = headers;
      }
    },
  });

  return { api };
};
