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

namespace Tpay\ShopwarePayment\Util\Locale;


use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Language\LanguageCollection;
use WhiteCube\Lingua\Service;

class LocaleProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $languageRepository;

    public function __construct(EntityRepositoryInterface $languageRepository)
    {
        $this->languageRepository = $languageRepository;
    }

    public function getLocaleCodeFromContext(Context $context): string
    {
        $languageId = $context->getLanguageId();

        $criteria = new Criteria([$languageId]);
        $criteria->addAssociation('locale');

        /** @var LanguageCollection $languageCollection */
        $languageCollection = $this->languageRepository->search($criteria, $context)->getEntities();

        $language = $languageCollection->get($languageId);
        if ($language === null) {
            return 'en-GB';
        }

        $locale = $language->getLocale();
        if (!$locale) {
            return 'en-GB';
        }
        $tpayLocale = Service::create($locale->getCode());

        if (!$tpayLocale->toISO_639_1()) {
            return 'EN';
        }

        return strtoupper($tpayLocale->toISO_639_1());
    }
}
