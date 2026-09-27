import { expect, test } from '@playwright/test';

const skip_without_shop = !process.env.PHOENIX_HTTP_BASE_URL;

test.describe('homepage carousel', () => {
  test.skip(skip_without_shop, 'Set PHOENIX_HTTP_BASE_URL and start scripts/http-server.sh.');

  test('advances visible slide when next control is clicked', async ({ page }) => {
    await page.goto('/');

    const carousel = page.locator('#cm-i-slider.carousel, .carousel').first();
    await expect(carousel).toBeVisible();

    const active_slide = carousel.locator('.carousel-item.active');
    await expect(active_slide).toBeVisible();

    const first_text = (await active_slide.innerText()).trim();
    expect(first_text.length).toBeGreaterThan(0);

    const next_control = carousel.locator('.carousel-control-next').first();
    await expect(next_control).toBeVisible();
    await next_control.click();

    await expect
      .poll(async () => (await carousel.locator('.carousel-item.active').innerText()).trim())
      .not.toBe(first_text);
  });
});
