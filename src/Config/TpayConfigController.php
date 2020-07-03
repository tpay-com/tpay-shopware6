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


namespace Tpay\ShopwarePayment\Config;


use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Tpay\ShopwarePayment\Config\Service\MerchantCredentialsServiceInterface;

/**
 * @RouteScope(scopes={"api"})
 */
class TpayConfigController extends AbstractController
{

    /** @var MerchantCredentialsServiceInterface */
    private $merchantCredentialsService;

    public function __construct(MerchantCredentialsServiceInterface $merchantCredentialsService)
    {
        $this->merchantCredentialsService = $merchantCredentialsService;
    }

    /**
     * @Route("/api/v{version}/_action/tpay/validate-merchant-credentials", name="api.action.tpay.validate.merchant.credentials", methods={"POST"})
     */
    public function validateMerchantCredentials(Request $request): JsonResponse
    {
        $merchantId = $request->request->getInt('merchantId');
        $merchantSecret = $request->request->get('merchantSecret');
        $merchantTrApiKey = $request->request->get('merchantTrApiKey');
        $merchantTrApiPass = $request->request->get('merchantTrApiPass');

        $credentialsValid = $this->merchantCredentialsService->testMerchantCredentials($merchantId, $merchantSecret, $merchantTrApiKey, $merchantTrApiPass);

        return new JsonResponse(['credentialsValid' => $credentialsValid]);
    }


}
