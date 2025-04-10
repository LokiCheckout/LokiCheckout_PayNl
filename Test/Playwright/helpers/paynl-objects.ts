const {expect} = require(process.cwd() + '/node_modules/@playwright/test');

export class PayNlPortal {
    page;
    locator;

    constructor(page) {
        this.page = page;
    }

    async expectTestPaymentPage() {
        await expect(this.page).toHaveURL(/api.pay.nl\/controllers/, {timeout: 5000});

        const body = await this.page.locator('body');
        await expect(body).toHaveText(/Testbetaling/);    }

    async expectIssuerPage() {
        await expect(this.page).toHaveURL(/api.pay.nl\/controllers\/payments\/issuer/, {timeout: 5000});

        const body = await this.page.locator('body');
        await expect(body).toHaveText(/Testbetaling/);
    }
}
