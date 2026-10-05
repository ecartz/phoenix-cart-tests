import { expect, test } from '@playwright/test';

const skip_without_shop = !process.env.PHOENIX_HTTP_BASE_URL;

test.describe('product attribute select', () => {
  test.skip(skip_without_shop, 'Set PHOENIX_HTTP_BASE_URL and start scripts/http-server.sh.');

  test('selects the larger box size on the lemons product page', async ({ page }) => {
    await page.goto('/product_info.php?products_id=2');

    await expect(page.locator('body')).toContainText('Lemons');

    const attributes = page.locator('.pi-options-attributes');
    await expect(attributes).toBeVisible();
    await expect(attributes).toContainText('Available Options');
    await expect(attributes).toContainText('Box Size');

    const box_size = attributes.locator('select[name="id[1]"]');
    await expect(box_size).toBeVisible();
    await expect(box_size.locator('option').first()).toHaveText('--- Please Select ---');
    await expect(box_size.locator('option[value="1"]')).toHaveText('12');

    await box_size.selectOption('2');

    await expect(box_size).toHaveValue('2');
    const selected_text = (await box_size.locator('option:checked').innerText()).trim();
    expect(selected_text).toMatch(/^24\b/);

    const cart_form = page.locator('form[name="cart_quantity"]');
    await expect(cart_form).toBeVisible();
    await cart_form.locator('button[type="submit"], input[type="submit"]').first().click();

    await expect(page).toHaveURL(/shopping_cart\.php/);
    await expect(page.locator('body')).toContainText('Lemons');
    await expect(page.locator('body')).toContainText('24');
    await expect(page.locator('body')).toContainText('$12.49');
  });
});
