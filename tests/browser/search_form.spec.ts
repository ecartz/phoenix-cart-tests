import { expect, test } from '@playwright/test';

const skip_without_shop = !process.env.PHOENIX_HTTP_BASE_URL;

test.describe('search form', () => {
  test.skip(skip_without_shop, 'Set PHOENIX_HTTP_BASE_URL and start scripts/http-server.sh.');

  test.use({ viewport: { width: 1280, height: 720 } });

  test('navbar quick find returns matching products', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('networkidle');

    const keywords = page.locator('input[name="keywords"]').first();
    await expect(keywords).toBeVisible();
    await keywords.fill('Oranges');

    await page.locator('form[name="quick_find"] button[type="submit"]').first().click();
    await page.waitForLoadState('networkidle');

    await expect(page).toHaveURL(/advanced_search_result\.php/);
    await expect(page.locator('body')).toContainText('Oranges');
  });
});
