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

namespace Tpay\ShopwarePayment\Payment;


use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Tpay\ShopwarePayment\Payment\Builder\BlikPaymentBuilderInterface;
use Tpay\ShopwarePayment\Payment\Exception\InvalidBlikCodeException;

class BlikPaymentHandler implements SynchronousPaymentHandlerInterface
{
    use TpayResponseHandlerTrait;

    /** @var OrderTransactionStateHandler */
    private $orderTransactionStateHandler;

    /** @var BlikPaymentBuilderInterface */
    private $blikPaymentBuilder;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(OrderTransactionStateHandler $orderTransactionStateHandler, BlikPaymentBuilderInterface $blikPaymentBuilder, LoggerInterface $logger)
    {
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->blikPaymentBuilder = $blikPaymentBuilder;
        $this->logger = $logger;
    }

	/**
	 * @inheritDoc
	 */
	public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
	{
        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            throw new SyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                (new CustomerNotLoggedInException())->getMessage()
            );
        }

        $responseBlik = $this->blikPaymentBuilder->createBlikTransaction($transaction, $salesChannelContext, $customer, $dataBag->getDigits('blikCode'));

        if (isset($responseBlik['result']) && (int) $responseBlik['result'] !== 1 ) {
            if ($responseBlik['err'] === 'ERR63') {
                throw new InvalidBlikCodeException();
            }

            $this->tpayResponseError($responseBlik, $transaction);
        }
    }

	/**
	 * @inheritDoc
	 */
	public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
	{
        /**
         * @See Tpay\ShopwarePayment\Payment\FinalizePaymentController
         * Nothing to do here.
         */
	}
}
