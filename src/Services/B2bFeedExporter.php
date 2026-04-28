<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Services;

use Adminos\Modules\FeedmanagerPro\Models\Partner;
use Adminos\Modules\Feedmanager\Models\Product;
use Generator;
use InvalidArgumentException;
use XMLWriter;

/**
 * Renders the B2B partner feed as XML.
 *
 * Output schema (full):
 *
 *   <products generated="2026-04-27T19:00:00+00:00" type="full" count="42">
 *     <product>
 *       <code>SKU-1</code>
 *       <name>...</name>
 *       <description>...</description>
 *       <price_vat>1234.56</price_vat>
 *       <price>1019.47</price>
 *       <old_price_vat>1499.99</old_price_vat>
 *       <currency>CZK</currency>
 *       <ean>...</ean>
 *       <product_number>...</product_number>
 *       <stock_quantity>5</stock_quantity>
 *       <availability>skladem</availability>
 *       <image_url>https://...</image_url>
 *       <category>...</category>
 *       <category_path>...</category_path>
 *     </product>
 *   </products>
 *
 * Stock variant only emits code, price_vat, stock_quantity, availability —
 * suitable for partners polling for inventory updates without re-fetching
 * full descriptions.
 *
 * @api
 */
final class B2bFeedExporter
{
    public function __construct(
        private readonly int $chunkSize = 500,
    ) {
    }

    /**
     * @return array{xml: string, count: int}
     */
    public function export(Partner $partner, string $feedType): array
    {
        if (! in_array($feedType, Partner::FEED_TYPES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown feed type "%s". Must be one of: %s.',
                $feedType,
                implode(', ', Partner::FEED_TYPES),
            ));
        }

        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString('  ');
        $writer->startDocument('1.0', 'UTF-8');

        $count = $this->exportableCount();
        $emitted = 0;

        $writer->startElement('products');
        $writer->writeAttribute('generated', now()->toIso8601String());
        $writer->writeAttribute('type', $feedType);
        $writer->writeAttribute('count', (string) $count);

        foreach ($this->productStream() as $product) {
            $this->writeProduct($writer, $product, $feedType);
            ++$emitted;
        }

        $writer->endElement();
        $writer->endDocument();

        return [
            'xml' => $writer->outputMemory(true),
            'count' => $emitted,
        ];
    }

    /**
     * @return Generator<int, Product>
     */
    private function productStream(): Generator
    {
        yield from $this->exportableQuery()
            ->orderBy('id')
            ->lazy($this->chunkSize);
    }

    private function exportableCount(): int
    {
        return $this->exportableQuery()->count();
    }

    private function exportableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Product::query()
            ->where('is_b2b_allowed', true)
            ->where('is_excluded', false);
    }

    private function writeProduct(XMLWriter $writer, Product $product, string $feedType): void
    {
        $writer->startElement('product');

        $writer->writeElement('code', $product->code);
        $writer->writeElement('price_vat', (string) $product->effectivePriceVat());
        $writer->writeElement('stock_quantity', (string) $product->stock_quantity);

        if ($product->availability !== null && $product->availability !== '') {
            $writer->writeElement('availability', $product->availability);
        }

        if ($feedType === Partner::FEED_FULL) {
            $writer->writeElement('name', $product->effectiveName());

            $description = $product->effectiveDescription();
            if ($description !== null && $description !== '') {
                $writer->startElement('description');
                $writer->writeCData($description);
                $writer->endElement();
            }

            $writer->writeElement('price', (string) $product->price);

            if ($product->old_price_vat !== null) {
                $writer->writeElement('old_price_vat', (string) $product->old_price_vat);
            }

            $writer->writeElement('currency', $product->currency);

            if ($product->ean !== null && $product->ean !== '') {
                $writer->writeElement('ean', $product->ean);
            }

            if ($product->product_number !== null && $product->product_number !== '') {
                $writer->writeElement('product_number', $product->product_number);
            }

            if ($product->manufacturer !== null && $product->manufacturer !== '') {
                $writer->writeElement('manufacturer', $product->manufacturer);
            }

            if ($product->image_url !== null && $product->image_url !== '') {
                $writer->writeElement('image_url', $product->image_url);
            }

            if ($product->category_text !== null && $product->category_text !== '') {
                $writer->writeElement('category', $product->category_text);
            }

            if ($product->complete_path !== null && $product->complete_path !== '') {
                $writer->writeElement('category_path', $product->complete_path);
            }
        }

        $writer->endElement();
    }
}
