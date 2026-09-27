import { expect, test } from '@playwright/test';

const skip_without_shop = !process.env.PHOENIX_HTTP_BASE_URL;

test.describe('checkout from scratch', () => {
  test.skip(skip_without_shop, 'Set PHOENIX_HTTP_BASE_URL and start scripts/http-server.sh.');

  test('cart checkout opens create account and accepts first customer field input', async ({ page }) => {
    await page.goto('/');
    await page.goto('/index.php?action=buy_now&products_id=3');

    await expect(page).toHaveURL(/shopping_cart\.php/);
    await expect(page.locator('body')).toContainText('Pears');

    const checkout_link = page.locator('.cm-sc-checkout a.btn-success').first();
    await expect(checkout_link).toBeVisible();
    await checkout_link.click();

    await expect(page).toHaveURL(/create_account\.php/);

    const first_name = page.locator('input[name="firstname"]');
    await expect(first_name).toBeVisible();
    await first_name.fill('Playwright');
    await expect(first_name).toHaveValue('Playwright');
  });
});
