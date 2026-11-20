import {Field, PaymentMethod} from '@loki-checkout/checkout-objects';
import {setupCheckout} from '@loki/setup-checkout';
import {test, expect} from '@loki/test';
import payNlConfig from './config/config';

const filterConfig = (showForCompany: string) => ({
    ...payNlConfig,
    config: {
        ...payNlConfig.config,
        'payment/paynl_payment_billink/active': '1',
        'payment/paynl_payment_billink/showforcompany': showForCompany,
    }
});

test.describe('PAY. payment filter test', () => {
    test('should only show Billink with a billing company', async ({page, context}) => {
        await setupCheckout(page, context, filterConfig('2'));

        const billink = new PaymentMethod(page, 'paynl_payment_billink').getLocator();
        const ideal = new PaymentMethod(page, 'paynl_payment_ideal').getLocator();
        const shippingAsBilling = page.locator('#loki-checkout-billing-address-shipping-as-billing-field');
        const company = new Field(page, 'shipping.company');

        await expect(shippingAsBilling).toBeChecked();
        await expect(company.getLocator()).not.toHaveValue('');
        await expect(billink).toBeVisible();

        await company.fill('');
        await page.reload();
        await expect(company.getLocator()).toHaveValue('');
        await expect(billink).toHaveCount(0);
        await expect(ideal).toBeVisible();

        await company.fill('Yireo');
        await page.reload();
        await expect(company.getLocator()).toHaveValue('Yireo');
        await expect(billink).toBeVisible();
        await expect(ideal).toBeVisible();
    });

    test('should only show Billink without a billing company', async ({page, context}) => {
        await setupCheckout(page, context, filterConfig('1'));

        const billink = new PaymentMethod(page, 'paynl_payment_billink').getLocator();
        const company = new Field(page, 'shipping.company');

        await expect(company.getLocator()).not.toHaveValue('');
        await expect(billink).toHaveCount(0);

        await company.fill('');
        await page.reload();
        await expect(billink).toBeVisible();
    });

    test('should show Billink regardless of the company by default', async ({page, context}) => {
        await setupCheckout(page, context, filterConfig('0'));

        const billink = new PaymentMethod(page, 'paynl_payment_billink').getLocator();
        await expect(billink).toBeVisible();

        await new Field(page, 'shipping.company').fill('');
        await page.reload();
        await expect(billink).toBeVisible();
    });
});
