<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Services;

use Adminos\Modules\Feedmanager\Models\Product;
use Adminos\Modules\Feedmanager\Services\B2bInclusion\B2bInclusionResolver;
use Adminos\Modules\FeedmanagerPro\Models\Partner;
use Generator;
use InvalidArgumentException;
use XMLWriter;

/**
 * Renders the B2B partner feed as XML, with stock-visibility thresholds
 * applied per partner / per product.
 *
 * Threshold rules:
 *
 *  - Partner has `default_low_stock_threshold` (e.g. 5 for standard, 2 for VIP),
 *    `default_low_stock_availability` (label like "Na dotaz") and
 *    `default_out_of_stock_availability` (e.g. "Vyprodáno").
 *  - Product may carry `b2b_low_stock_threshold` (floor) and an optional
 *    `b2b_low_stock_availability` override.
 *  - Effective threshold for a (partner, product) pair = MAX of partner default
 *    and product floor. Per-product is "be at least this careful, no matter
 *    which partner is asking".
 *
 * Behaviour at emit time:
 *
 *  - stock = 0:        availability = partner.default_out_of_stock; emit 0.
 *  - stock <= effective threshold: availability = product.b2b_low_stock_avail
 *    OR partner.default_low_stock_avail; stock element = 0 so partner can't
 *    see exact remaining count and snipe it before others.
 *  - stock > effective threshold: real availability + real stock.
 *
 * @api
 */
final class B2bFeedExporter
{
    public function __construct(
        private readonly B2bInclusionResolver $inclusionResolver,
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
            $this->writeProduct($writer, $product, $partner, $feedType);
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
     * @return array{availability: ?string, stock_to_emit: int}
     */
    public function resolveStockVisibility(Product $product, Partner $partner): array
    {
        $stock = (int) $product->stock_quantity;

        if ($stock <= 0) {
            return [
                'availability' => $partner->default_out_of_stock_availability,
                'stock_to_emit' => 0,
            ];
        }

        $effectiveThreshold = max(
            (int) ($partner->default_low_stock_threshold ?? 0),
            (int) ($product->b2b_low_stock_threshold ?? 0),
        );

        if ($effectiveThreshold > 0 && $stock <= $effectiveThreshold) {
            return [
                'availability' => $product->b2b_low_stock_availability
                    ?: $partner->default_low_stock_availability,
                'stock_to_emit' => 0,
            ];
        }

        return [
            'availability' => $product->availability,
            'stock_to_emit' => $stock,
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
        // Single source of truth — všechna pravidla (global exclude,
        // master flag, paused flag, force-allow/exclude override,
        // category exclusion tree) řeší {@see B2bInclusionResolver}.
        // Žádný hardcoded chain `where()->where()->where()` v exporteru.
        //
        // PIPELINE PRAVIDLO: B2B feed čerpá VÝHRADNĚ z `supplier.is_own=true`
        // produktů (= vlastní eshop). Produkty od externích dodavatelů (ACI,
        // velkoobchod) se do B2B feedu nedostanou — i kdyby měly
        // `b2b_inclusion_override = force_allowed`. Pokud chce admin prodat
        // dodavatelský produkt B2B, musí ho nejdřív propustit přes vlastní
        // eshop (Shoptet), který ho re-exportuje zpět do ADMINOSu pod
        // `is_own=true` supplier. Detail: `docs/poznamky/29-b2b-pipeline.md`.
        return $this->inclusionResolver->constrainToIncluded(Product::query())
            ->whereHas('supplier', fn ($q) => $q->where('is_own', true));
    }

    private function writeProduct(XMLWriter $writer, Product $product, Partner $partner, string $feedType): void
    {
        $visibility = $this->resolveStockVisibility($product, $partner);

        $writer->startElement('product');

        $writer->writeElement('code', $product->code);
        $writer->writeElement('price_vat', (string) $product->effectivePriceVat());
        $writer->writeElement('stock_quantity', (string) $visibility['stock_to_emit']);

        if ($visibility['availability'] !== null && $visibility['availability'] !== '') {
            $writer->writeElement('availability', $visibility['availability']);
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
