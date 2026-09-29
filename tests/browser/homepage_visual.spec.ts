import { expect, test, type Locator, type Page } from '@playwright/test';

const skip_without_shop = !process.env.PHOENIX_HTTP_BASE_URL;

const carousel_baseline_width = 1116;
const carousel_baseline_height = 765;

async function expect_carousel_baseline(page: Page, carousel: Locator): Promise<void> {
  await carousel.evaluate(
    (element, height) => {
      element.style.setProperty('max-height', `${height}px`, 'important');
      element.style.setProperty('height', `${height}px`, 'important');
      element.style.setProperty('overflow', 'hidden', 'important');
      element.style.setProperty('box-sizing', 'border-box', 'important');
    },
    carousel_baseline_height,
  );

  const screenshot_options = {
    clip: {
      x: 0,
      y: 0,
      width: carousel_baseline_width,
      height: carousel_baseline_height,
    },
    maxDiffPixelRatio: 0.06,
  };

  if (process.env.GITHUB_ACTIONS === 'true') {
    await expect(carousel).toHaveScreenshot('homepage-carousel.png', screenshot_options);

    return;
  }

  let box = await carousel.boundingBox();
  if (box === null) {
    throw new Error('carousel has no layout box');
  }

  const clip_bottom = box.y + carousel_baseline_height;
  let viewport_height = (await page.viewportSize())?.height ?? 720;
  if (clip_bottom > viewport_height) {
    viewport_height = Math.ceil(clip_bottom + 16);
    await page.setViewportSize({ width: 1280, height: viewport_height });
    await expect(carousel).toBeVisible();
    box = await carousel.boundingBox();
    if (box === null) {
      throw new Error('carousel has no layout box after viewport resize');
    }
  }

  await expect(page).toHaveScreenshot('homepage-carousel.png', {
    clip: {
      x: box.x,
      y: box.y,
      width: Math.min(carousel_baseline_width, box.width),
      height: carousel_baseline_height,
    },
    maxDiffPixelRatio: 0.06,
  });
}

test.describe('homepage visual', () => {
  test.skip(skip_without_shop, 'Set PHOENIX_HTTP_BASE_URL and start scripts/http-server.sh.');

  test('carousel matches committed screenshot baseline', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 720 });
    await page.goto('/');
    await page.waitForLoadState('networkidle');

    const carousel = page.locator('#cm-i-slider.carousel, .carousel').first();
    await expect(carousel).toBeVisible();
    await carousel.locator('img').first().waitFor({ state: 'visible' });

    await expect_carousel_baseline(page, carousel);
  });
});
