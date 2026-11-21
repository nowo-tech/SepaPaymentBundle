import { test, expect } from '@playwright/test';

/** REQ-DEMO-013: the validation form reports success and failure as shell flashes. */
test.describe('SEPA Payment demo', () => {
  test('the home page lists the endpoint groups', async ({ page }) => {
    const response = await page.goto('/');
    expect(response?.status()).toBe(200);
    await expect(page.getByTestId('demo-platform')).toBeVisible();
    for (const title of ['Validators', 'Converters', 'Generators', 'Parsers', 'Export / Import', 'Mandate Management']) {
      await expect(page.getByRole('heading', { name: title })).toBeVisible();
    }
  });

  test('valid data is accepted with a success flash and the submitted data', async ({ page }) => {
    await page.goto('/demo-validation-form');
    await page.getByLabel('IBAN').fill('ES9121000418450200051332');
    await page.getByLabel('BIC').fill('ESPBESMM');
    await page.getByLabel('SEPA Country Code').fill('ES');
    await page.getByRole('button', { name: 'Validate' }).click();
    await expect(page.getByTestId('demo-platform-flashes')).toContainText('Validation successful');
    await expect(page.getByRole('heading', { name: 'Submitted data' })).toBeVisible();
  });

  test('an invalid IBAN is rejected with an error flash', async ({ page }) => {
    await page.goto('/demo-validation-form');
    await page.getByLabel('IBAN').fill('ES0000000000000000000000');
    await page.getByLabel('BIC').fill('ESPBESMM');
    await page.getByLabel('SEPA Country Code').fill('ES');
    await page.getByRole('button', { name: 'Validate' }).click();
    await expect(page.getByRole('alert').filter({ hasText: 'Validation failed' })).toBeVisible();
    await expect(page.getByText('This value is not a valid IBAN')).toBeVisible();
  });
});
