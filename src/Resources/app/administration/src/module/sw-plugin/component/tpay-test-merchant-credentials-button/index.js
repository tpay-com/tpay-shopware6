/**
 * @copyright 2020 Tpay Krajowy Integrator Płatności S.A. <https://tpay.com/>
 *
 * @author    Jakub Medyński <jme@crehler.com>
 * @support   Tpay <pt@tpay.com>
 * @created   29 kwi 2020
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import template from './tpay-test-merchant-credentials-button.html.twig';

const {Component, Mixin} = Shopware;

const TPAY_CONFIG_NAMESPACE = 'TpayShopwarePayment.config.';


Component.register('tpay-test-merchant-credentials-button', {
    template: template,

    inject: ['TpayMerchantCredentialsService'],

    mixins: [
        Mixin.getByName('notification')
    ],


    data() {
        return {
            isLoading: false,
            isSuccess: false
        }
    },

    methods: {
        tpayTestMerchantCredentials() {
            this.isLoading = true;
            const fields = this.$parent.$parent.$children;

            const merchantId = this.getValue(fields, 'merchantId');
            const merchantSecret = this.getValue(fields, 'merchantSecret')
            const merchantTrApiKey = this.getValue(fields, 'merchantTrApiKey')
            const merchantTrApiPass = this.getValue(fields, 'merchantTrApiPass')

            this.TpayMerchantCredentialsService.validateMerchantCredentials(merchantId, merchantSecret, merchantTrApiKey, merchantTrApiPass)
                .then((response) => {
                    this.isLoading = false;
                    if (!response.credentialsValid) {
                        this.onError();
                        return;
                    }
                    this.onSuccess();
                }).catch(() => {
                this.isLoading = false;
                this.onError();
            })

        },

        onSuccess() {
            const that = this;

            this.isSuccess = true;
            this.createNotificationSuccess({
                title: this.$tc('tpay-shopware-payment.config.successTestMerchantCredentialsNotificationTitle'),
                message: this.$tc('tpay-shopware-payment.config.successTestMerchantCredentialsNotificationMessage'),
                autoClose: true
            });
            setTimeout(() => that.isSuccess = false, 2000);
        },

        onError() {
            this.createNotificationError({
                title: this.$tc('tpay-shopware-payment.config.errorTestMerchantCredentialsNotificationTitle'),
                message: this.$tc('tpay-shopware-payment.config.errorTestMerchantCredentialsNotificationMessage'),
                autoClose: true
            });
        },

        getFieldByName(field, name) {
            return field.$attrs.name === TPAY_CONFIG_NAMESPACE + name;
        },

        getValue(fields, name) {
            const field = fields.find((field) => {
                return this.getFieldByName(field, name)
            })

            if (typeof field.currentValue === 'undefined' || "" === field.currentValue) {
                return field.$attrs.placeholder;
            }

            return field.currentValue;
        }
    }


});
