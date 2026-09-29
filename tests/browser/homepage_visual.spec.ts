import { expect, test } from '@playwright/test';

const skip_without_shop = !process.env.PHOENIX_HTTP_BASE_URL;

test.describe('homepage visual', () => {
  test.skip(skip_without_shop, 'Set PHOENIX_HTTP_BASE_URL and start scripts/http-server.sh.');

  test('carousel matches committed screenshot baseline', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 720 });
    await page.goto('/');
    await page.waitForLoadState('networkidle');

    const carousel = page.locator('#cm-i-slider.carousel, .carousel').first();
    await expect(carousel).toBeVisible();
    await carousel.locator('img').first().waitFor({ state: 'visible' });

    await carousel.evaluate((element) => {
      element.style.maxHeight = '765px';
      element.style.overflow = 'hidden';
    });

    await expect(carousel).toHaveScreenshot('homepage-carousel.png', {
      maxDiffPixelRatio: 0.06,
    });
  });
});
