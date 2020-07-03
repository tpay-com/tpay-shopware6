<?php declare(strict_types=1);
/**
 * @copyright 2020 Tpay Krajowy Integrator Płatności S.A. <https://tpay.com/>
 *
 * @author    Jakub Medyński <jme@crehler.com>
 * @support   Tpay <pt@tpay.com>
 * @created   23 kwi 2020
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tpay\ShopwarePayment\Config\Service;


use Tpay\ShopwarePayment\Component\TpayPayment\TpayBasicApi;
use Tpay\ShopwarePayment\Config\Exception\TpayInvalidMerchantCredentialsException;
use Tpay\ShopwarePayment\Config\Util\TestCredentialsTransaction;
use tpayLibs\src\_class_tpay\Utilities\TException;

class MerchantCredentialsService implements MerchantCredentialsServiceInterface
{

    public function testMerchantCredentials(int $merchantId, string $merchantSecret, string $merchantTrApiKey, string $merchantTrApiPass): bool
    {
        $testTransactionConfig = TestCredentialsTransaction::getTestTransactionData();

        $basicApi = new TpayBasicApi(
            $merchantId,
            $merchantSecret,
            $merchantTrApiKey,
            $merchantTrApiPass
        );

        try {
            $basicApi->create($testTransactionConfig->getTransactionConfig());
        } catch (TException $e) {
            throw new TpayInvalidMerchantCredentialsException();
        }

        return true;
    }


}

