import { expect, test } from '@playwright/test';

const skip_without_shop = !process.env.PHOENIX_HTTP_BASE_URL;

test.describe('navbar currency dropdown', () => {
  test.skip(skip_without_shop, 'Set PHOENIX_HTTP_BASE_URL and start scripts/http-server.sh.');

  test('switches selected currency via dropdown item', async ({ page }) => {
    await page.goto('/product_info.php?products_id=3');

    const currency_toggle = page.locator('#navDropdownCurrencies');
    await expect(currency_toggle).toBeVisible();
    await expect(currency_toggle).toContainText('USD');

    await currency_toggle.click();
    const eur_item = page.locator('.nb-currencies .dropdown-item', { hasText: 'Euro' }).first();
    await expect(eur_item).toBeVisible();
    await eur_item.click();

    await expect(page).toHaveURL(/currency=EUR/);
    await expect(currency_toggle).toContainText('EUR');
    await expect(page.locator('[data-product-price="4.25"]')).toBeVisible();
  });
});
