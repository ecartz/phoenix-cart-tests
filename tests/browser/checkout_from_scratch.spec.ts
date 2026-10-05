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
    const password_confirmation = page.locator('input[name="password_confirmation"]');
    if ((await password_confirmation.count()) > 0) {
      await password_confirmation.fill(password, { force: true });
    }
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

    const telephone = page.locator('input[name="telephone"]');
    if ((await telephone.count()) > 0) {
      await telephone.fill('555-0100', { force: true });
    }
    await page.locator('input[name="matc"]').check();

    await page.locator('form[name="create_account"] button[type="submit"], form[name="create_account"] input[type="submit"]').first().click();

    await page.waitForURL(/create_account_success\.php|checkout_shipping\.php/);
    if (!page.url().includes('checkout_shipping.php')) {
      await page.goto('/checkout_shipping.php');
    }

    await expect(page).toHaveURL(/checkout_shipping\.php/);
    await expect(page.locator('form[name="checkout_shipping"]')).toBeVisible();
    await expect(page.locator('body')).toContainText('Flat Rate');

    const shipping_form = page.locator('form[name="checkout_shipping"]');
    const flat_shipping = shipping_form.locator('input[name="shipping"][value="flat_flat"]');
    if ((await flat_shipping.count()) > 0) {
      await flat_shipping.check({ force: true });
    } else {
      await shipping_form.locator('input[name="shipping"]').first().check({ force: true });
    }
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
