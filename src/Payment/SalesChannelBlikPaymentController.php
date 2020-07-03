<?php declare(strict_types=1);
/**
 * @copyright 2020 Tpay Krajowy Integrator Płatności S.A. <https://tpay.com/>
 *
 * @author    Jakub Medyński <jme@crehler.com>
 * @support   Tpay <pt@tpay.com>
 * @created   07 maj 2020
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tpay\ShopwarePayment\Payment;


use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Checkout\Payment\Exception\PaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\UnknownPaymentMethodException;
use Shopware\Core\Checkout\Payment\PaymentService;
use Shopware\Core\Checkout\Payment\SalesChannel\AbstractHandlePaymentMethodRoute;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Framework\AffiliateTracking\AffiliateTrackingListener;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Tpay\ShopwarePayment\Payment\Builder\BlikPaymentBuilder;
use Tpay\ShopwarePayment\Payment\Exception\InvalidBlikCodeException;

/**
 * @RouteScope(scopes={"sales-channel-api"})
 */
class SalesChannelBlikPaymentController extends AbstractController
{
    /** @var OrderService */
    private $orderService;

    /** @var PaymentService */
    private $paymentService;

    /** @var EntityRepositoryInterface */
    private $orderTransactionRepository;

    /** @var AbstractHandlePaymentMethodRoute */
    private $handlePaymentMethodRoute;

    /** @var SessionInterface */
    private $session;

    public function __construct(
        OrderService $orderService,
        PaymentService $paymentService,
        EntityRepositoryInterface $orderTransactionRepository,
        AbstractHandlePaymentMethodRoute $handlePaymentMethodRoute,
        SessionInterface $session
    ) {
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->handlePaymentMethodRoute = $handlePaymentMethodRoute;
        $this->session = $session;
    }

    /**
     * @Route("/sales-channel-api/v{version}/tpay/blik-payment/register-transaction", name="sales-channel-api.tpay.blik-paymen.register-transaction", methods={"POST"})
     */
    public function registerTransaction(int $version, RequestDataBag $dataBag, Request $request, SalesChannelContext $context): JsonResponse
    {
        if (!$context->getCustomer()) {
            throw new CustomerNotLoggedInException();
        }
        try {
            $this->addAffiliateTracking($dataBag, $request->getSession());
            $orderId = $this->orderService->createOrder($dataBag, $context);
            $finishUrl = $this->generateUrl('frontend.checkout.finish.page', ['orderId' => $orderId]);

            $this->paymentService->handlePaymentByOrder($orderId, $dataBag, $context);
            $this->session->remove(BlikPaymentBuilder::BLIK_TRANSACTION_SESSION_KEY);

            return $this->json(['success' => true, 'orderId' => $orderId, 'finishUrl' => $finishUrl]);
        } catch (InvalidBlikCodeException $exception) {
            return $this->json(['success'=> false, 'orderId' => $orderId, 'blikCodeValid' => false ,'message' => $exception->getMessage()]);
        } catch (ConstraintViolationException $formViolations) {
        } catch (Error $blockedError) {
        } catch (AsyncPaymentProcessException | InvalidOrderException | SyncPaymentProcessException | UnknownPaymentMethodException $e) {
            $this->session->remove(BlikPaymentBuilder::BLIK_TRANSACTION_SESSION_KEY);
            throw $e;
        }
        $this->session->remove(BlikPaymentBuilder::BLIK_TRANSACTION_SESSION_KEY);
        return  $this->json(['success' => false, 'orderId' => $orderId, 'finishUrl' => null]);
    }

    /**
     * @Route("/sales-channel-api/v{version}/tpay/blik-payment/register-transaction-again", name="sales-channel-api.tpay.blik-paymen.register-transaction-again", methods={"POST"})
     */
    public function registerTransactionAgain(int $version, RequestDataBag $dataBag, Request $request, SalesChannelContext $context): JsonResponse
    {
        $orderId = $dataBag->getAlnum('orderId');

        $finishUrl = $this->generateUrl('frontend.checkout.finish.page', [
            'orderId' => $orderId,
        ]);

        $errorUrl = $this->generateUrl('frontend.checkout.finish.page', [
            'orderId' => $orderId,
            'paymentFailed' => true,
        ]);

        $handlePaymentRequest = new Request();
        $handlePaymentRequest->request->set('orderId', $request->get('orderId'));
        $handlePaymentRequest->request->set('finishUrl', $finishUrl);
        $handlePaymentRequest->request->set('errorUrl', $errorUrl);
        $handlePaymentRequest->request->set('blikCode', $request->get('blikCode'));

        try {
            $routeResponse = $this->handlePaymentMethodRoute->load($handlePaymentRequest, $context);
            $response = $routeResponse->getRedirectResponse();
            $this->session->remove(BlikPaymentBuilder::BLIK_TRANSACTION_SESSION_KEY);
        } catch (InvalidBlikCodeException $exception) {
                return $this->json(['success'=> false, 'orderId' => $orderId, 'blikCodeValid' => false ,'message' => $exception->getMessage()]);
        } catch (PaymentProcessException $paymentProcessException) {
            $this->session->remove(BlikPaymentBuilder::BLIK_TRANSACTION_SESSION_KEY);
            return $this->json(['success' => false, 'orderId' => $orderId, 'finishUrl' => null]);
        }
        if ($response === null) {
            $this->session->remove(BlikPaymentBuilder::BLIK_TRANSACTION_SESSION_KEY);
            return $this->json(['success' => true, 'orderId' => $orderId, 'finishUrl' => $finishUrl]);
        }
        $this->session->remove(BlikPaymentBuilder::BLIK_TRANSACTION_SESSION_KEY);
        return  $this->json(['success' => false, 'orderId' => $orderId, 'finishUrl' => null]);
    }

    /**
     * @Route("/sales-channel-api/v{version}/tpay/blik-payment/check-payment-state", name="sales-channel-api.tpay.blik-paymen.check-payment-state", methods={"POST"})
     */
    public function checkPaymentState(int $version, Request $request, SalesChannelContext $context ): JsonResponse
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $request->get('orderId')));

        /** @var OrderTransactionEntity $transaction */
        $transaction = $this->orderTransactionRepository->search($criteria, $context->getContext())->first();

        $stateName = $transaction->getStateMachineState()->getTechnicalName();

        if ($stateName === OrderTransactionStates::STATE_PAID) {
            $responseData = [
                'waiting' => false,
                'success' => true,
            ];
        } else if ($stateName === OrderTransactionStates::STATE_OPEN) {
            $responseData = [
                'waiting' => true
            ];
        } else {
            $responseData = [
                'waiting' => false,
                'success' => false,
            ];
        }
        $this->session->remove(BlikPaymentBuilder::BLIK_TRANSACTION_SESSION_KEY);
        return $this->json($responseData);
    }

    private function addAffiliateTracking(RequestDataBag $dataBag, SessionInterface $session): void
    {
        $affiliateCode = $session->get(AffiliateTrackingListener::AFFILIATE_CODE_KEY);
        $campaignCode = $session->get(AffiliateTrackingListener::CAMPAIGN_CODE_KEY);
        if ($affiliateCode !== null && $campaignCode !== null) {
            $dataBag->set(AffiliateTrackingListener::AFFILIATE_CODE_KEY, $affiliateCode);
            $dataBag->set(AffiliateTrackingListener::CAMPAIGN_CODE_KEY, $campaignCode);
        }
    }

}
