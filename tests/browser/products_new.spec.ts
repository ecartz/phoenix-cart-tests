import { expect, test } from '@playwright/test';

const skip_without_shop = !process.env.PHOENIX_HTTP_BASE_URL;

test.describe('products_new page', () => {
  test.skip(skip_without_shop, 'Set PHOENIX_HTTP_BASE_URL and start scripts/http-server.sh.');

  test('lists sample catalog items from navbar new products link', async ({ page }) => {
    await page.goto('/products_new.php');

    await expect(page.locator('.is-product').first()).toBeVisible();
    await expect(page.getByText(/Oranges|Pears/).first()).toBeVisible();
  });
});
