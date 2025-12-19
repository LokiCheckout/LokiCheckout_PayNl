import {PaymentMethod, PlaceOrderButton} from '@loki/checkout-objects';
import {setupCheckout} from '@loki/setup-checkout';
import {test, expect} from '@loki/test';

import {PayNlPortal} from './helpers/paynl-objects';
import payNlConfig from './config/config';

test.describe('ideal payment test', () => {
    test('should allow me to go to the checkout', async ({page, context}) => {
        await setupCheckout(page, context, {
            ...payNlConfig,
            config: {
                ...payNlConfig.config,
                'payment/paynl_payment_ideal/active': 1,
            }
        });

        const paymentMethod = new PaymentMethod(page, 'paynl_payment_ideal');
        await paymentMethod.select();

        const placeOrderButton = new PlaceOrderButton(page);
        await placeOrderButton.click();

        const payNlPortal = new PayNlPortal(page);
        await payNlPortal.expectIssuerPage();
    });
});
