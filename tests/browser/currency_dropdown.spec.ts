import { expect, test } from '@playwright/test';

const skip_without_shop = !process.env.PHOENIX_HTTP_BASE_URL;

test.describe('currency dropdown', () => {
  test.skip(skip_without_shop, 'Set PHOENIX_HTTP_BASE_URL and start scripts/http-server.sh.');

  test.use({ viewport: { width: 1280, height: 720 } });

  test('switches session currency from navbar control', async ({ page }) => {
    await page.goto('/product_info.php?products_id=3');
    await page.waitForLoadState('domcontentloaded');

    const currency_toggle = page.locator('.nb-currencies .dropdown-toggle').first();
    await expect(currency_toggle).toBeVisible();

    await currency_toggle.click();
    const eur_link = page.locator('.nb-currencies .dropdown-menu a').filter({ hasText: 'Euro' }).first();
    await expect(eur_link).toBeVisible();
    await Promise.all([
      page.waitForURL(/currency=EUR/),
      eur_link.click(),
    ]);

    await expect(page.locator('.nb-currencies .dropdown-toggle')).toContainText('EUR');
    await expect(page.locator('[data-product-price="4.25"]')).toBeVisible();
  });
});
