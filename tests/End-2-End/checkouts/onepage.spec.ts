import {expect, test} from '@playwright/test';
import LoadCheckout from '../actions/LoadCheckout';

async function waitForMagewire(page) {
    try {
        await page.locator('.magewire-loader-overlay').waitFor({state: 'visible', timeout: 5000});
    } catch {
        // Overlay may have already appeared and hidden before we got here
    }
    await page.locator('.magewire-loader-overlay').waitFor({state: 'hidden'});
}

test('Can place a successful order in the onepage checkout', async ({page}) => {
    await (new LoadCheckout('hyva_onepage')).execute(page);

    await waitForMagewire(page);

    await page.locator('#shipping-method-list label').first().check();

    await waitForMagewire(page);

    await page.locator('#payment-method-list > div').filter({hasText: 'Briqpay'}).click();

    await waitForMagewire(page);

    await expect(page.locator('#briqpay iframe')).toBeVisible({timeout: 30000});

    await page.waitForTimeout(5000);

    await expect(
        page.locator('#briqpay iframe')
            .contentFrame()
            .getByTestId('main-button')
    ).toBeEnabled();

    await page.waitForTimeout(5000);

    await page.locator('#briqpay iframe')
        .contentFrame()
        .getByTestId('main-button')
        .click();

    await page.waitForTimeout(5000);

    await expect(page).toHaveURL(/mollie\.com/);

    await page.locator('.payment-method-list button').first().click();

    await page.click(`input[value="paid"]`);
    await page.click('.button');

    await expect(page).toHaveURL(/checkout\/onepage\/success/);
})

test('Briqpay iframe price updates when shipping method changes', async ({page}) => {
    await (new LoadCheckout('hyva_onepage')).execute(page);

    await waitForMagewire(page);

    // Select first shipping method (Free, €0,00)
    await page.locator('#shipping-method-list label').first().check();

    await waitForMagewire(page);

    // Select Briqpay payment
    await page.locator('#payment-method-list > div').filter({hasText: 'Briqpay'}).click();

    await waitForMagewire(page);

    // Wait for iframe to appear and the Briqpay session to load
    await expect(page.locator('#briqpay iframe')).toBeVisible({timeout: 30000});
    await page.waitForTimeout(5000);

    // Read the initial total from the iframe (Free shipping, product only)
    const initialPrice = await page.locator('#briqpay iframe')
        .contentFrame()
        .locator('[data-testid="module-order_info"] span.text-xl')
        .innerText();

    // Select the second shipping method (Fixed, €5,00)
    await page.locator('#shipping-method-list label').nth(1).check();

    await waitForMagewire(page);

    // Wait for the iframe to reappear with the updated Briqpay session
    await expect(page.locator('#briqpay iframe')).toBeVisible({timeout: 30000});
    await page.waitForTimeout(5000);

    // Read the updated total from the iframe
    const updatedPrice = await page.locator('#briqpay iframe')
        .contentFrame()
        .locator('[data-testid="module-order_info"] span.text-xl')
        .innerText();

    // Price should have increased by the shipping cost (€5,00)
    expect(updatedPrice).not.toEqual(initialPrice);
})
