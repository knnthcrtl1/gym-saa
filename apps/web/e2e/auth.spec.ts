import { test, expect } from "@playwright/test";

test.describe("Authentication", () => {
  test("login with valid credentials redirects to dashboard", async ({
    page,
  }) => {
    await page.goto("/login");

    // The login form has default credentials pre-filled (admin@demofitness.local / password)
    // Clear and fill to be explicit
    const emailInput = page.getByLabel("Email");
    const passwordInput = page.getByLabel("Password");

    await emailInput.clear();
    await emailInput.fill("admin@demofitness.local");

    await passwordInput.clear();
    await passwordInput.fill("password");

    await page.getByRole("button", { name: "Sign in" }).click();

    await page.waitForURL("**/dashboard");
    await expect(page).toHaveURL(/\/dashboard/);
  });

  test("login with wrong password shows error message", async ({ page }) => {
    await page.goto("/login");

    const emailInput = page.getByLabel("Email");
    const passwordInput = page.getByLabel("Password");

    await emailInput.clear();
    await emailInput.fill("admin@demofitness.local");

    await passwordInput.clear();
    await passwordInput.fill("wrongpassword");

    await page.getByRole("button", { name: "Sign in" }).click();

    // The login page renders a v-alert with type="error" on failure
    const alert = page.locator(".v-alert");
    await expect(alert).toBeVisible();
    await expect(alert).toContainText(/unable to sign in|credentials/i);
  });

  test("logout redirects to login page", async ({ page }) => {
    // Login first
    await page.goto("/login");

    await page.getByRole("button", { name: "Sign in" }).click();
    await page.waitForURL("**/dashboard");

    // Open the user menu and click Sign out
    // The sidebar has a "Sign out" list item
    await page.getByText("Sign out").click();

    await page.waitForURL("**/login");
    await expect(page).toHaveURL(/\/login/);
  });

  test("accessing /dashboard without auth redirects to /login", async ({
    page,
  }) => {
    await page.goto("/dashboard");

    // The auth middleware redirects unauthenticated users to /login
    await page.waitForURL("**/login**");
    await expect(page).toHaveURL(/\/login/);
  });
});
