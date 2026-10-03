import { expect, test } from '@playwright/test';

const skip_without_shop = !process.env.PHOENIX_HTTP_BASE_URL;

test.describe('navbar search form', () => {
  test.skip(skip_without_shop, 'Set PHOENIX_HTTP_BASE_URL and start scripts/http-server.sh.');

  test('submits quick find keywords to advanced search results', async ({ page }) => {
    await page.goto('/');

    const search_input = page.locator('form[name="quick_find"] input[name="keywords"]');
    await expect(search_input).toBeVisible();
    await search_input.fill('Oranges');
    await page.locator('form[name="quick_find"] button.btn-search').click();

    await expect(page).toHaveURL(/advanced_search_result\.php/);
    await expect(page.getByText('Oranges').first()).toBeVisible();
  });
});
