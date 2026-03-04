import {expect, test} from '@playwright/test';
import LoadCheckout from '../actions/LoadCheckout';

test('Can place a successful order in the default checkout', async ({page}) => {
    await (new LoadCheckout('hyva_default')).execute(page);

    await page.locator('#shipping-method-list label').first().check();
    await page.locator('[data-route="payment"]').click();

    await page.locator('#payment-method-list > div').filter({hasText: 'Briqpay'}).click();

    await expect(page.locator('#briqpay iframe')).toBeVisible();

    await page.locator('#briqpay iframe')
        .contentFrame()
        .locator('[data-testid="payment-method-MollieIdeal"]')
        .click();

    await expect(
        page.locator('#briqpay iframe')
            .contentFrame()
            .locator('[data-testid="main-button"]')
    ).toBeEnabled();

    await page.locator('#briqpay iframe')
        .contentFrame()
        .locator('[data-testid="main-button"]')
        .click();

    await expect(page).toHaveURL(/mollie\.com/);

    await page.locator('.payment-method-list button').first().click();

    await page.click(`input[value="paid"]`);
    await page.click('.button');

    await expect(page).toHaveURL(/checkout\/onepage\/success/);
})
