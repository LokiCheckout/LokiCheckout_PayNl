import {setupCheckout} from '@loki/setup-checkout';
import {test, expect} from '@loki/test';
import payNlConfig from './config/config';

const fastCheckoutConfig = (config: Record<string, string>) => ({
    ...payNlConfig,
    config: {
        ...payNlConfig.config,
        ...config,
    }
});

test.describe('iDEAL fast checkout test', () => {
    test('should post the simple fast checkout button to PAY.', async ({page, context}) => {
        await setupCheckout(page, context, fastCheckoutConfig({
            'loki_checkout/paynl/ideal_fast_checkout_enabled': '1',
            'payment/paynl_payment_ideal/fast_checkout_show_modal': '0',
        }));

        const form = page.locator('#loki-checkout-pay-nl-fast-checkout-ideal-simple');
        await expect(form).toBeVisible();
        await expect(page.locator('#loki-checkout-pay-nl-fast-checkout-ideal-modal')).toHaveCount(0);

        const origin = new URL(page.url()).origin;
        const action = await form.getAttribute('action');
        expect(action).toBe(origin + '/paynl/checkout/fastcheckoutstart/');
        await expect(form).toHaveAttribute('method', 'post');
        await expect(form.locator('input[name="selected_estimate_country"]')).toHaveValue('NL');
        await expect(form.locator('input[name="form_key"]')).not.toHaveValue('');

        let postData = '';
        await page.route('**/paynl/checkout/fastcheckoutstart/**', async route => {
            postData = route.request().postData() || '';
            await route.fulfill({status: 200, contentType: 'text/html', body: '<html><body>PAY. fast checkout stub</body></html>'});
        });

        const button = form.getByRole('button', {name: /Checkout with/});
        await expect(button).toBeVisible();
        await button.click();

        await expect(page.locator('body')).toHaveText('PAY. fast checkout stub');
        await expect(page).toHaveURL(/\/paynl\/checkout\/fastcheckoutstart\/$/);
        expect(postData).toContain('selected_estimate_country=NL');
        expect(postData).toMatch(/form_key=[A-Za-z0-9]+/);
    });

    test('should open and close the fast checkout modal', async ({page, context}) => {
        await setupCheckout(page, context, fastCheckoutConfig({
            'loki_checkout/paynl/ideal_fast_checkout_enabled': '1',
            'payment/paynl_payment_ideal/fast_checkout_show_modal': '1',
        }));

        const modal = page.locator('#loki-checkout-pay-nl-fast-checkout-ideal-modal');
        await expect(page.locator('#loki-checkout-pay-nl-fast-checkout-ideal-simple')).toHaveCount(0);

        const trigger = modal.getByRole('button', {name: /Checkout with/});
        const dialog = modal.locator('dialog');
        await expect(trigger).toBeVisible();
        await expect(dialog).toBeHidden();

        await trigger.click();
        await expect(dialog).toBeVisible();
        await expect(dialog).toContainText('Order faster');
        await expect(dialog).toContainText('with iDEAL fast checkout');
        await expect(dialog).toContainText('Create an iDEAL profile and save your address for your next order');
        await expect(dialog.getByRole('button', {name: 'Fast Checkout'})).toBeVisible();

        const form = dialog.locator('form');
        const origin = new URL(page.url()).origin;
        expect(await form.getAttribute('action')).toBe(origin + '/paynl/checkout/fastcheckoutstart/');

        await dialog.getByRole('button', {name: 'Enter my own address and pay'}).click();
        await expect(dialog).toBeHidden();

        await trigger.click();
        await expect(dialog).toBeVisible();

        await dialog.locator('.loki-checkout-pay-nl-fast-checkout-ideal-modal__dialog-close').click();
        await expect(dialog).toBeHidden();
    });

    test('should show the fast checkout to guests when it is guest only', async ({page, context}) => {
        await setupCheckout(page, context, fastCheckoutConfig({
            'loki_checkout/paynl/ideal_fast_checkout_enabled': '1',
            'payment/paynl_payment_ideal/fast_checkout_show_modal': '0',
            'payment/paynl_payment_ideal/fast_checkout_guest_only': '1',
        }));

        await expect(page.locator('#loki-checkout-pay-nl-fast-checkout-ideal-simple')).toBeVisible();
    });

    test('should hide the fast checkout when it is disabled', async ({page, context}) => {
        await setupCheckout(page, context, fastCheckoutConfig({
            'loki_checkout/paynl/ideal_fast_checkout_enabled': '0',
            'payment/paynl_payment_ideal/fast_checkout_show_modal': '1',
        }));

        await expect(page.locator('#loki-checkout-pay-nl-fast-checkout-ideal-modal')).toHaveCount(0);
        await expect(page.locator('#loki-checkout-pay-nl-fast-checkout-ideal-simple')).toHaveCount(0);
        await expect(page.locator('form[action*="paynl/checkout/fastcheckoutstart"]')).toHaveCount(0);
    });
});
