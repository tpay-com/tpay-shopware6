<?php declare(strict_types=1);
/**
 * @copyright 2020 Tpay Krajowy Integrator Płatności S.A. <https://tpay.com/>
 *
 * @author    Jakub Medyński <jme@crehler.com>
 * @support   Tpay <pt@tpay.com>
 * @created   30 cze 2020
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tpay\ShopwarePayment\Payment;


use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use tpayLibs\src\Dictionaries\ErrorCodes\TransactionApiErrors;

trait TpayResponseHandlerTrait
{
    private function tpayResponseError(array $tpayResponse, SyncPaymentTransactionStruct $transaction): SyncPaymentProcessException
    {
        $this->logger->error(TransactionApiErrors::ERROR_CODES[$tpayResponse['err']], $transaction->jsonSerialize());
        throw new SyncPaymentProcessException
        ($transaction->getOrderTransaction()->getId(),
            'Tpay transaction error'
        );
    }

    private function handleTpayResponse(array $tpayResponse, SyncPaymentTransactionStruct $transaction): RedirectResponse
    {
        if (isset($tpayResponse['result']) && (int) $tpayResponse['result'] === 1) {
            return RedirectResponse::create($tpayResponse['url']);
        }

        throw $this->tpayResponseError($tpayResponse, $transaction);
    }
}
