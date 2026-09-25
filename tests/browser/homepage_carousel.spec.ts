import { expect, test } from '@playwright/test';

const skipWithoutShop = !process.env.PHOENIX_HTTP_BASE_URL;

test.describe('homepage carousel', () => {
  test.skip(skipWithoutShop, 'Set PHOENIX_HTTP_BASE_URL and start scripts/http-server.sh.');

  test('advances visible slide when next control is clicked', async ({ page }) => {
    await page.goto('/');

    const carousel = page.locator('#cm-i-slider.carousel, .carousel').first();
    await expect(carousel).toBeVisible();

    const activeSlide = carousel.locator('.carousel-item.active');
    await expect(activeSlide).toBeVisible();

    const firstText = (await activeSlide.innerText()).trim();
    expect(firstText.length).toBeGreaterThan(0);

    const nextControl = carousel.locator('.carousel-control-next').first();
    await expect(nextControl).toBeVisible();
    await nextControl.click();

    await expect
      .poll(async () => (await carousel.locator('.carousel-item.active').innerText()).trim())
      .not.toBe(firstText);

    const secondText = (await carousel.locator('.carousel-item.active').innerText()).trim();
    expect(secondText).toMatch(/Our Farm|Strawberries Coming Soon/);
    expect(secondText).not.toBe(firstText);
  });
});
