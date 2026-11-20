import {Field, PaymentMethod, PlaceOrderButton} from '@loki-checkout/checkout-objects';
import {setupCheckout} from '@loki/setup-checkout';
import {test, expect} from '@loki/test';
import payNlConfig from './config/config';

const prefix = 'loki-checkout-payment-methods-paynl-payment-billink-form';

const billinkConfig = (showDob: string, showKvk: string) => ({
    ...payNlConfig,
    config: {
        ...payNlConfig.config,
        'payment/paynl_payment_billink/active': '1',
        'payment/paynl_payment_billink/showdob': showDob,
        'payment/paynl_payment_billink/showkvk': showKvk,
        'payment/paynl_payment_billink/showvat': '0',
        'payment/paynl_payment_billink/showforcompany': '0',
    }
});

test.describe('PAY. customer fields test', () => {
    test('should show the required Billink fields', async ({page, context}) => {
        await setupCheckout(page, context, billinkConfig('2', '2'));

        await expect(page.locator('#' + prefix)).toHaveCount(0);
        await new PaymentMethod(page, 'paynl_payment_billink').select();

        const dobField = new Field(page, prefix + '-dob-field');
        const cocField = new Field(page, prefix + '-cocnumber-field');
        const agreeField = new Field(page, prefix + '-billink-agree-field');

        await expect(dobField.getLocator()).toBeVisible();
        await expect(dobField.getLocator()).toHaveAttribute('type', 'date');
        await expect(dobField.getLocator()).toHaveAttribute('name', 'dob');
        await expect(dobField.getLocator()).toHaveAttribute('required', '');
        await expect(dobField.getLabelLocator()).toContainText('Date of birth');

        await expect(cocField.getLocator()).toBeVisible();
        await expect(cocField.getLocator()).toHaveAttribute('name', 'cocnumber');
        await expect(cocField.getLocator()).toHaveAttribute('required', '');
        await expect(cocField.getLabelLocator()).toContainText('CoC number');

        await expect(agreeField.getLocator()).toBeVisible();
        await expect(agreeField.getLocator()).toHaveAttribute('type', 'checkbox');
        await expect(agreeField.getLocator()).not.toBeChecked();
        await expect(page.locator('#' + prefix + '-billink-agree')).toContainText('I agree with the payment terms of Billink');

        await expect(page.locator('#' + prefix + '-vatnumber-field')).toHaveCount(0);
    });

    test('should reject a short CoC number', async ({page, context}) => {
        await setupCheckout(page, context, billinkConfig('2', '2'));
        await new PaymentMethod(page, 'paynl_payment_billink').select();

        const cocField = new Field(page, prefix + '-cocnumber-field');
        await cocField.fill('1234567');

        const cocMessages = page.locator('#' + prefix + '-cocnumber-messages');
        await expect(cocMessages).toContainText('Please enter a valid CoC number');
        await expect(cocField.getLocator()).toHaveAttribute('aria-invalid', 'true');

        await cocField.fill('12345678');
        await cocField.expectValue('12345678');
        await expect(cocMessages).toHaveCount(0);
        await expect(cocField.getLocator()).not.toHaveAttribute('aria-invalid', 'true');
    });

    test('should require the Billink terms', async ({page, context}) => {
        await setupCheckout(page, context, billinkConfig('2', '2'));
        await new PaymentMethod(page, 'paynl_payment_billink').select();

        const dobField = new Field(page, prefix + '-dob-field');
        await dobField.fill('1980-05-17');
        await dobField.expectValue('1980-05-17');

        const cocField = new Field(page, prefix + '-cocnumber-field');
        await cocField.fill('12345678');
        await cocField.expectValue('12345678');

        const placeOrderButton = new PlaceOrderButton(page);
        await placeOrderButton.clickAndFail();

        const agreeMessages = page.locator('#' + prefix + '-billink-agree-messages');
        await expect(agreeMessages).toContainText('Please agree to these terms');

        const agreeField = new Field(page, prefix + '-billink-agree-field');
        await agreeField.fill(true);
        await expect(agreeField.getLocator()).toBeChecked();
        await expect(agreeMessages).toHaveCount(0);
    });

    test('should hide the Billink fields when they are disabled', async ({page, context}) => {
        await setupCheckout(page, context, billinkConfig('0', '0'));
        await new PaymentMethod(page, 'paynl_payment_billink').select();

        await expect(page.locator('#' + prefix + '-dob-field')).toHaveCount(0);
        await expect(page.locator('#' + prefix + '-cocnumber-field')).toHaveCount(0);
        await expect(page.locator('#' + prefix + '-vatnumber-field')).toHaveCount(0);
        await expect(page.locator('#' + prefix + '-billink-agree-field')).toHaveCount(0);
    });
});
