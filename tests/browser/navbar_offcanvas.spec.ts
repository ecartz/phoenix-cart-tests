import { expect, test } from '@playwright/test';

const skip_without_shop = !process.env.PHOENIX_HTTP_BASE_URL;

test.describe('navbar offcanvas', () => {
  test.skip(skip_without_shop, 'Set PHOENIX_HTTP_BASE_URL and start scripts/http-server.sh.');

  test.use({ viewport: { width: 375, height: 667 } });

  test('opens main site menu when hamburger control is clicked', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('networkidle');

    const toggler = page.locator('.nb-hamburger-button').first();
    await expect(toggler).toBeVisible();
    await toggler.scrollIntoViewIfNeeded();

    const offcanvas = page.locator('#collapseCoreNav');
    await expect(offcanvas).not.toHaveClass(/show/);

    await toggler.click();

    await expect(offcanvas).toHaveClass(/show/, { timeout: 10_000 });
    await expect(offcanvas.locator('input[name="keywords"]')).toBeVisible();
  });
});
