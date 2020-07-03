<?php declare(strict_types=1);
/**
 * @copyright 2020 Tpay Krajowy Integrator Płatności S.A. <https://tpay.com/>
 *
 * @author    Jakub Medyński <jme@crehler.com>
 * @support   Tpay <pt@tpay.com>
 * @created   23 cze 2020
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tpay\ShopwarePayment\Resources\snippet\pl_PL;


use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

class SnippetFile_pl_PL implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'tpay.pl-PL';
    }

    public function getPath(): string
    {
        return __DIR__ . '/tpay.pl-PL.json';
    }

    public function getIso(): string
    {
        return 'pl-PL';
    }

    public function getAuthor(): string
    {
        return 'Tpay Krajowy Integrator Płatności S.A.';
    }

    public function isBase(): bool
    {
        return false;
    }
}
