<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Services;

use Adminos\Modules\Feedmanager\Models\Product;
use Adminos\Modules\Feedmanager\Models\Supplier;
use Adminos\Modules\FeedmanagerPro\Models\ShoptetExportConfig;
use Generator;
use InvalidArgumentException;
use XMLWriter;

/**
 * Generates Shoptet auto-import XML feeds.
 *
 * Output is structured to validate against `products-supplier-v10.rng`. Two
 * feed types are supported:
 *
 *  - {@see ShoptetExportConfig::FEED_FULL}: full SHOPITEM with name, description,
 *    images, categories, manufacturer, supplier, prices, stock, availability,
 *    EAN, product number, etc. Shoptet imports this 1× per day.
 *  - {@see ShoptetExportConfig::FEED_STOCK}: minimal SHOPITEM with just identifier
 *    + price + stock + availability + visibility. For Shoptet's update import
 *    that runs 1–16× per day depending on tariff.
 *
 * Each emitted product carries a `<SUPPLIER>` element with the source supplier
 * name so the client can filter products in Shoptet admin by source.
 *
 * Eligibility filter:
 *
 *  - product.is_excluded = false
 *  - product.status = approved
 *  - supplier.is_active = true
 *  - supplier.is_own = false (own eshop produces the catalogue, doesn't consume it)
 *  - supplier.publish_to_shoptet = true
 *
 * @api
 */
final class ShoptetFeedExporter
{
    public function __construct(
        private readonly int $chunkSize = 500,
    ) {
    }

    /**
     * @return array{xml: string, count: int}
     */
    public function export(ShoptetExportConfig $config): array
    {
        if (! in_array($config->feed_type, ShoptetExportConfig::FEED_TYPES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown Shoptet feed type "%s". Must be one of: %s.',
                $config->feed_type,
                implode(', ', ShoptetExportConfig::FEED_TYPES),
            ));
        }

        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString('  ');
        $writer->startDocument('1.0', 'UTF-8');

        $writer->startElement('SHOP');

        $emitted = 0;
        foreach ($this->productStream() as $product) {
            $this->writeProduct($writer, $product, $config->feed_type);
            ++$emitted;
        }

        // RNG requires at least one SHOPITEM (oneOrMore). Emit a placeholder
        // when the catalogue is empty so the document still validates rather
        // than failing the auto-import with a cryptic error.
        if ($emitted === 0) {
            $this->writePlaceholder($writer);
        }

        $writer->endElement();
        $writer->endDocument();

        return [
            'xml' => $writer->outputMemory(true),
            'count' => $emitted,
        ];
    }

    /**
     * Sanitize a product code for the Shoptet `codeDatatype` (max 64 chars).
     *
     * Shoptet auto-import only accepts A–Z, 0–9, _, /, -, and space in the
     * <CODE> element. Diacritics and lowercase get translated/uppercased;
     * other characters are stripped.
     */
    public function sanitizeCode(string $raw): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $raw);
        if ($ascii === false) {
            $ascii = $raw;
        }

        $upper = strtoupper($ascii);
        $clean = preg_replace('/[^A-Z0-9_\/\- ]+/', '', $upper);

        return substr($clean ?? '', 0, 64);
    }

    /**
     * @return Generator<int, Product>
     */
    private function productStream(): Generator
    {
        yield from $this->exportableQuery()
            ->with(['supplier', 'feedConfig'])
            ->orderBy('id')
            ->lazy($this->chunkSize);
    }

    private function exportableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Product::query()
            ->where('is_excluded', false)
            ->where('status', Product::STATUS_APPROVED)
            ->whereHas('supplier', function ($q): void {
                $q->where('is_active', true)
                    ->where('is_own', false)
                    ->where('publish_to_shoptet', true);
            });
    }

    private function writeProduct(XMLWriter $writer, Product $product, string $feedType): void
    {
        $writer->startElement('SHOPITEM');

        $code = $this->sanitizeCode($product->code);
        // Skip products whose codes can't be safely represented — shouldn't
        // happen in practice unless the source feed had a totally malformed
        // identifier. Better silent skip than RNG-invalid output.
        if ($code === '') {
            $writer->endElement();
            return;
        }

        if ($feedType === ShoptetExportConfig::FEED_FULL) {
            $this->writeFullSection($writer, $product);
        }

        // Detail group (always required) — code/EAN, optional price, stock,
        // availability. RNG requires at least CODE or EAN here.
        $writer->writeElement('CODE', $code);

        if ($product->ean !== null && $product->ean !== '') {
            $writer->writeElement('EAN', $product->ean);
        }

        $priceVat = $product->effectivePriceVat();
        if ($priceVat !== null && $priceVat !== '') {
            $writer->writeElement('PRICE', $this->formatPrice((string) $priceVat));
        }

        if ($feedType === ShoptetExportConfig::FEED_FULL) {
            if ($product->old_price_vat !== null) {
                $writer->writeElement('STANDARD_PRICE', $this->formatPrice((string) $product->old_price_vat));
            }
        }

        if (in_array($product->currency, $this->allowedCurrencies(), true)) {
            $writer->writeElement('CURRENCY', $product->currency);
        }

        $stock = max(0, (int) $product->stock_quantity);
        $writer->startElement('STOCK');
        $writer->writeElement('AMOUNT', (string) $stock);
        $writer->endElement();

        $availability = $product->availability;
        if ($availability !== null && $availability !== '') {
            $availability = mb_substr($availability, 0, 100);
            // Shoptet wants symmetric in-stock and out-of-stock labels. Use
            // the same label for both when only one is known — Shoptet picks
            // based on real stock at import time.
            $writer->writeElement('AVAILABILITY_IN_STOCK', $availability);
            $writer->writeElement('AVAILABILITY_OUT_OF_STOCK', $availability);
        }

        $writer->writeElement('VISIBLE', '1');

        $writer->endElement();
    }

    private function writeFullSection(XMLWriter $writer, Product $product): void
    {
        $name = mb_substr($product->effectiveName(), 0, 250);
        $writer->writeElement('NAME', $name);

        if ($product->short_description !== null && $product->short_description !== '') {
            $writer->startElement('SHORT_DESCRIPTION');
            $writer->writeCData($product->short_description);
            $writer->endElement();
        }

        $description = $product->effectiveDescription();
        if ($description !== null && $description !== '') {
            $writer->startElement('DESCRIPTION');
            $writer->writeCData($description);
            $writer->endElement();
        }

        if ($product->manufacturer !== null && $product->manufacturer !== '') {
            $writer->writeElement('MANUFACTURER', mb_substr($product->manufacturer, 0, 200));
        }

        $supplier = $product->supplier;
        if ($supplier instanceof Supplier && $supplier->name !== '') {
            $writer->writeElement('SUPPLIER', mb_substr($supplier->name, 0, 255));
        }

        $writer->writeElement('ITEM_TYPE', 'product');

        $categories = $this->resolveCategories($product);
        if ($categories !== []) {
            $writer->startElement('CATEGORIES');
            foreach ($categories as $category) {
                $writer->writeElement('CATEGORY', $category);
            }
            $writer->endElement();
        }

        $images = $this->resolveImages($product);
        if ($images !== []) {
            $writer->startElement('IMAGES');
            foreach ($images as $url) {
                $writer->startElement('IMAGE');
                $writer->text($url);
                $writer->endElement();
            }
            $writer->endElement();
        }

        if ($product->product_number !== null && $product->product_number !== '') {
            // PRODUCT_NUMBER lives in the detail group per the RNG, not here.
        }

        // Origin tag for sorting the merged feed in Shoptet admin.
        $feedConfig = $product->feedConfig;
        if ($feedConfig !== null && $feedConfig->name !== '') {
            $writer->writeElement('XML_FEED_NAME', mb_substr($feedConfig->name, 0, 100));
        }
    }

    private function writePlaceholder(XMLWriter $writer): void
    {
        $writer->startElement('SHOPITEM');
        $writer->writeElement('CODE', 'ADMINOS-PLACEHOLDER');
        $writer->writeElement('VISIBLE', '0');
        $writer->endElement();
    }

    /**
     * @return array<int, string>
     */
    private function resolveCategories(Product $product): array
    {
        $path = $product->complete_path ?? $product->category_text;
        if ($path === null || $path === '') {
            return [];
        }

        // RNG accepts free-form CATEGORY text; emit the full path under one
        // CATEGORY element. Shoptet splits it on `>` server-side.
        return [trim((string) $path)];
    }

    /**
     * @return array<int, string>
     */
    private function resolveImages(Product $product): array
    {
        $urls = [];

        if ($product->relationLoaded('images') && $product->images->isNotEmpty()) {
            foreach ($product->images as $image) {
                if ($image->url !== null && $image->url !== '') {
                    $urls[] = $image->url;
                }
            }
        }

        if ($urls === [] && $product->image_url !== null && $product->image_url !== '') {
            $urls[] = $product->image_url;
        }

        return $urls;
    }

    private function formatPrice(string $raw): string
    {
        $value = (float) str_replace(',', '.', $raw);

        return number_format($value, 2, '.', '');
    }

    /**
     * @return array<int, string>
     */
    private function allowedCurrencies(): array
    {
        return [
            'CZK', 'EUR', 'AUD', 'BGN', 'BRL', 'CAD', 'CNY', 'DKK', 'GBP',
            'HKD', 'HRK', 'HUF', 'CHF', 'IDR', 'ILS', 'INR', 'ISK', 'JPY',
            'KRW', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RON', 'RUB',
            'SEK', 'SGD', 'THB', 'TRY', 'USD', 'VND', 'XDR', 'ZAR',
        ];
    }
}
