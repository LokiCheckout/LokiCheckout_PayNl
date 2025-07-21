import {Field} from '@loki/checkout-objects';
import {setupCheckout} from '@loki/setup-checkout';
import {test, expect} from '@loki/test';
import payNlConfig from './config/config';

test.describe('LokiCheckout_PayNl test', () => {
    test('should allow me to go to the checkout', async ({page, context}) => {
        await setupCheckout(page, context, {
            ...payNlConfig,
            config: {
                ...payNlConfig.config,
                'payment/paynl_payment_ideal/active': 1,
            }
        });

        const fields = {
            'shipping.country_id': 'NL',
        };

        for (const [fieldName, fieldValue] of Object.entries(fields)) {
            const field = new Field(page, fieldName);
            await field.fill(fieldValue);
            await field.expectValue(fieldValue);
        }

        await page.locator('//input[@value="paynl_payment_ideal"]').check();
    });
});
