import { test, expect } from "@playwright/test";

test.describe("Role-based permissions", () => {
  test("gym_admin can access members and dashboard", async ({ page }) => {
    // Login as gym admin (default demo credentials)
    await page.goto("/login");

    const emailInput = page.getByLabel("Email");
    const passwordInput = page.getByLabel("Password");

    await emailInput.clear();
    await emailInput.fill("admin@demofitness.local");
    await passwordInput.clear();
    await passwordInput.fill("password");

    await page.getByRole("button", { name: "Sign in" }).click();
    await page.waitForURL("**/dashboard");

    // Verify dashboard is accessible
    await expect(page).toHaveURL(/\/dashboard/);
    await expect(page.getByText("Welcome back")).toBeVisible();

    // Navigate to members page
    await page.goto("/members");
    await page.waitForURL("**/members");

    // Verify members page is accessible
    await expect(page.getByText("All members")).toBeVisible();
  });

  test("super_admin can access tenant management", async ({ page }) => {
    // Login as super admin
    await page.goto("/login");

    const emailInput = page.getByLabel("Email");
    const passwordInput = page.getByLabel("Password");

    await emailInput.clear();
    await emailInput.fill("superadmin@gymsaas.local");
    await passwordInput.clear();
    await passwordInput.fill("password");

    await page.getByRole("button", { name: "Sign in" }).click();
    await page.waitForURL("**/dashboard");

    // Verify dashboard access
    await expect(page).toHaveURL(/\/dashboard/);

    // Super admin should have access to tenant management
    // Navigate to tenants page (tenant management route)
    await page.goto("/tenants");

    // Verify the page loads without being redirected away
    // The super_admin role should have permission to view tenants
    await expect(page).toHaveURL(/\/tenants/);
  });
});
