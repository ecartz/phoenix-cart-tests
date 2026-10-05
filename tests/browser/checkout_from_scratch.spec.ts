import { expect, test } from '@playwright/test';

const skip_without_shop = !process.env.PHOENIX_HTTP_BASE_URL;

test.describe('checkout from scratch', () => {
  test.skip(skip_without_shop, 'Set PHOENIX_HTTP_BASE_URL and start scripts/http-server.sh.');

  test('cart checkout registers account and completes cod', async ({ page }) => {
    const unique_suffix = `${Date.now()}-${Math.random().toString(16).slice(2, 8)}`;
    const email_address = `playwright-checkout-${unique_suffix}@example.com`;
    const password = 'phoenix-test';

    await page.goto('/');
    await page.goto('/index.php?action=buy_now&products_id=3');

    await expect(page).toHaveURL(/shopping_cart\.php/);
    await expect(page.locator('body')).toContainText('Pears');

    const checkout_link = page.locator('.cm-sc-checkout a.btn-success').first();
    await expect(checkout_link).toBeVisible();
    await checkout_link.click();

    await expect(page).toHaveURL(/create_account\.php/);

    await page.locator('input[name="firstname"]').fill('Playwright');
    await page.locator('input[name="lastname"]').fill('Checkout');
    await page.locator('input[name="email_address"]').fill(email_address);
    await page.locator('input[name="password"]').fill(password);
    await page.locator('input[name="password_confirmation"]').fill(password);
    await page.locator('input[name="street_address"]').fill('1 Test Street');
    await page.locator('input[name="city"]').fill('Testville');
    await page.locator('input[name="postcode"]').fill('90210');
    await page.locator('select[name="country_id"]').selectOption('223');

    const zone_select = page.locator('select[name="zone_id"]');
    if (await zone_select.isVisible()) {
      await zone_select.selectOption({ label: 'Florida' });
    } else {
      await page.locator('input[name="state"]').fill('Florida');
    }

    await page.locator('input[name="telephone"]').fill('555-0100');
    await page.locator('input[name="matc"]').check();

    await page.locator('form[name="create_account"] button[type="submit"], form[name="create_account"] input[type="submit"]').first().click();

    await expect(page).toHaveURL(/checkout_shipping\.php/);
    await expect(page.locator('body')).toContainText('Flat Rate');

    await page.locator('input[type="radio"][value="flat_flat"]').check();
    await page.locator('form[name="checkout_shipping"] button[type="submit"], form[name="checkout_shipping"] input[type="submit"]').first().click();

    await expect(page).toHaveURL(/checkout_payment\.php/);
    await page.locator('input[type="radio"][value="cod"]').check();
    await page.locator('form[name="checkout_payment"] button[type="submit"], form[name="checkout_payment"] input[type="submit"]').first().click();

    await expect(page).toHaveURL(/checkout_confirmation\.php/);
    await page.locator('form[name="checkout_confirmation"] button[type="submit"], form[name="checkout_confirmation"] input[type="submit"]').first().click();

    await expect(page).toHaveURL(/checkout_success\.php/);
    await expect(page.locator('body')).toContainText('cm-cs-thank-you');
  });
});
