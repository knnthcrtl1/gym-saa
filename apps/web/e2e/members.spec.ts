import { test, expect } from "@playwright/test";

/**
 * Helper: log in with default credentials and wait for dashboard.
 */
async function loginAsAdmin(page: import("@playwright/test").Page) {
  await page.goto("/login");
  await page.getByRole("button", { name: "Sign in" }).click();
  await page.waitForURL("**/dashboard");
}

test.describe("Members page", () => {
  test("members page loads after login", async ({ page }) => {
    await loginAsAdmin(page);

    await page.goto("/members");
    await page.waitForURL("**/members");

    // Page header should display the title
    await expect(page.getByText("All members")).toBeVisible();

    // The members table should be present
    await expect(page.locator(".v-table")).toBeVisible();
  });

  test("add member via form dialog and verify member appears in list", async ({
    page,
  }) => {
    await loginAsAdmin(page);
    await page.goto("/members");

    // Click the "Add member" button to open the create dialog
    await page.getByRole("button", { name: "Add member" }).click();

    // Wait for the modal dialog to appear
    const dialog = page.locator(".v-overlay--active");
    await expect(dialog).toBeVisible();

    // Fill in required fields
    const memberCode = `E2E-${Date.now()}`;
    await dialog.getByLabel("Member Code").fill(memberCode);
    await dialog.getByLabel("First Name").fill("Test");
    await dialog.getByLabel("Last Name").fill("Member");

    // Click "Create member" to save
    await dialog.getByRole("button", { name: "Create member" }).click();

    // Wait for the dialog to close
    await expect(dialog).not.toBeVisible({ timeout: 10_000 });

    // Verify the new member appears in the table
    await expect(page.getByText("Test")).toBeVisible();
    await expect(page.getByText("Member")).toBeVisible();
  });

  test("add member with missing fields shows validation errors", async ({
    page,
  }) => {
    await loginAsAdmin(page);
    await page.goto("/members");

    // Click the "Add member" button
    await page.getByRole("button", { name: "Add member" }).click();

    const dialog = page.locator(".v-overlay--active");
    await expect(dialog).toBeVisible();

    // Clear any default values and leave required fields empty
    await dialog.getByLabel("Member Code").clear();
    await dialog.getByLabel("First Name").clear();
    await dialog.getByLabel("Last Name").clear();

    // Submit the empty form
    await dialog.getByRole("button", { name: "Create member" }).click();

    // The backend should return validation errors displayed as error messages
    // Vuetify v-text-field renders error messages in .v-messages elements
    const errorMessages = dialog.locator(
      ".v-messages__message, .v-alert--type-error",
    );
    await expect(errorMessages.first()).toBeVisible({ timeout: 10_000 });
  });
});
