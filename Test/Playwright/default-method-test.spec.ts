import {PaymentMethod} from '@loki-checkout/checkout-objects';
import {setupCheckout} from '@loki/setup-checkout';
import {test, expect} from '@loki/test';
import payNlConfig from './config/config';

const defaultMethodConfig = (defaultPaymentOption: string, selectDefaultPaymentMethod: string) => ({
    ...payNlConfig,
    config: {
        ...payNlConfig.config,
        'payment/paynl_payment_billink/active': '1',
        'payment/paynl/default_payment_option': defaultPaymentOption,
        'loki_checkout/billing/select_default_payment_method': selectDefaultPaymentMethod,
    }
});

test.describe('PAY. default payment method test', () => {
    test('should preselect the PAY. default method when Loki does not select a default', async ({page, context}) => {
        await setupCheckout(page, context, defaultMethodConfig('paynl_payment_billink', '0'));

        await expect(new PaymentMethod(page, 'paynl_payment_billink').getLocator()).toBeChecked();
        await expect(new PaymentMethod(page, 'checkmo').getLocator()).not.toBeChecked();
    });

    test('should prefer the PAY. default method over the first method', async ({page, context}) => {
        await setupCheckout(page, context, defaultMethodConfig('paynl_payment_ideal', '1'));

        await expect(new PaymentMethod(page, 'paynl_payment_ideal').getLocator()).toBeChecked();
        await expect(new PaymentMethod(page, 'checkmo').getLocator()).not.toBeChecked();
    });

    test('should not preselect a method without a PAY. default method', async ({page, context}) => {
        await setupCheckout(page, context, defaultMethodConfig('0', '0'));

        await expect(page.locator('input[name="payment_method"]:checked')).toHaveCount(0);
    });
});
