export default class LoadCheckout {
    checkout: string;

    constructor(checkout: string) {
        this.checkout = checkout;
    }

    async execute(page) {
        await page.goto(`/${this.checkout}/joust-duffle-bag.html`);

        const productTitle = await page.locator('h1[data-ui-id="page-title-wrapper"]').innerText();

        await page.click('text=Add to Cart');

        await page.getByText(`You added ${productTitle} to your shopping cart.`).waitFor({state: 'visible'});
        await page.locator('#menu-cart-icon span').waitFor({state: 'visible'});

        await page.goto(`/${this.checkout}/checkout`);

        await page.getByRole('textbox', {name: 'Email address', exact: true}).fill('johndoe@example.com');
        await page.getByRole('textbox', {name: 'First Name', exact: true}).fill('John');
        await page.getByRole('textbox', {name: 'Last Name', exact: true}).fill('Doe');
        await page.getByRole('textbox', {name: 'Street Address', exact: true}).fill('Example street 123');
        await page.getByRole('textbox', {name: 'Zip/Postal Code', exact: true}).fill('1234AB');
        await page.getByRole('textbox', {name: 'City', exact: true}).fill('Example');
        await page.getByRole('textbox', {name: 'Phone Number', exact: true}).fill('01234567890');
    }
}
