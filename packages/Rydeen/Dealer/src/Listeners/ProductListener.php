<?php

namespace Rydeen\Dealer\Listeners;

use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;

class ProductListener
{
    /**
     * Handle product create/update events.
     * Generates a url_key from SKU + name when the product has no url_key set.
     */
    public function afterSave(Product $product): void
    {
        $urlKeyAttribute = DB::table('attributes')->where('code', 'url_key')->first();

        if (! $urlKeyAttribute) {
            return;
        }

        $existingUrlKey = DB::table('product_attribute_values')
            ->where('product_id', $product->id)
            ->where('attribute_id', $urlKeyAttribute->id)
            ->value('text_value');

        if (! empty($existingUrlKey)) {
            return;
        }

        $slug = $this->generateSlug($product);
        $uniqueSlug = $this->ensureUnique($slug, $product->id, $urlKeyAttribute->id);

        $this->persistUrlKey($product, $urlKeyAttribute, $uniqueSlug);
    }

    /**
     * Build slug from "{sku}-{slugified-name}" format.
     * Falls back to SKU only if name is empty.
     */
    protected function generateSlug(Product $product): string
    {
        $nameAttribute = DB::table('attributes')->where('code', 'name')->first();

        $name = $nameAttribute
            ? DB::table('product_attribute_values')
                ->where('product_id', $product->id)
                ->where('attribute_id', $nameAttribute->id)
                ->value('text_value')
            : null;

        $base = $product->sku;

        if (! empty($name)) {
            $base .= ' ' . $name;
        }

        return $this->slugify($base);
    }

    /**
     * Slugify a string using Bagisto's algorithm:
     * lowercase, NFKD normalize, strip diacriticals, keep letters/numbers/spaces/hyphens,
     * spaces to hyphens, collapse duplicates, trim edges.
     */
    protected function slugify(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');

        if (class_exists('Normalizer')) {
            $value = \Normalizer::normalize($value, \Normalizer::FORM_KD);
        }

        // Strip diacritical marks (combining characters)
        $value = preg_replace('/[\x{0300}-\x{036f}]/u', '', $value);

        // Keep only letters, numbers, spaces, and hyphens
        $value = preg_replace('/[^\p{L}\p{N}\s-]+/u', '', $value);

        // Spaces to hyphens
        $value = preg_replace('/\s+/', '-', $value);

        // Collapse duplicate hyphens
        $value = preg_replace('/-+/', '-', $value);

        // Trim leading/trailing hyphens
        return trim($value, '-');
    }

    /**
     * Append -1, -2, etc. if the slug already exists for a different product.
     */
    protected function ensureUnique(string $slug, int $productId, int $urlKeyAttributeId): string
    {
        $candidate = $slug;
        $suffix = 0;
        $maxAttempts = 100;

        while ($this->slugExists($candidate, $productId, $urlKeyAttributeId)) {
            $suffix++;

            if ($suffix > $maxAttempts) {
                $candidate = $slug . '-' . uniqid();
                break;
            }

            $candidate = $slug . '-' . $suffix;
        }

        return $candidate;
    }

    /**
     * Check if a url_key slug exists for any product other than the given one.
     */
    protected function slugExists(string $slug, int $productId, int $urlKeyAttributeId): bool
    {
        return DB::table('product_attribute_values')
            ->where('attribute_id', $urlKeyAttributeId)
            ->where('text_value', $slug)
            ->where('product_id', '!=', $productId)
            ->exists();
    }

    /**
     * Save the url_key attribute value and refresh the product flat index.
     */
    protected function persistUrlKey(Product $product, object $urlKeyAttribute, string $slug): void
    {
        $channels = $product->channels->pluck('code')->toArray();

        if (empty($channels)) {
            $channels = [DB::table('channels')->value('code') ?? 'default'];
        }

        $locales = DB::table('channel_locales')
            ->join('channels', 'channels.id', '=', 'channel_locales.channel_id')
            ->whereIn('channels.code', $channels)
            ->join('locales', 'locales.id', '=', 'channel_locales.locale_id')
            ->pluck('locales.code')
            ->unique()
            ->toArray();

        if (empty($locales)) {
            $locales = [DB::table('locales')->value('code') ?? 'en'];
        }

        foreach ($channels as $channelCode) {
            foreach ($locales as $localeCode) {
                $uniqueId = implode('|', [$channelCode, $localeCode, $product->id, $urlKeyAttribute->id]);

                DB::table('product_attribute_values')->updateOrInsert(
                    ['unique_id' => $uniqueId],
                    [
                        'product_id'   => $product->id,
                        'attribute_id' => $urlKeyAttribute->id,
                        'text_value'   => $slug,
                        'channel'      => $channelCode,
                        'locale'       => $localeCode,
                    ]
                );
            }
        }

        // Refresh product flat index so url_key is available for queries
        try {
            $indexer = app(\Webkul\Product\Helpers\Indexers\Flat::class);
            $indexer->refresh($product);
        } catch (\Exception $e) {
            report($e);
        }
    }
}
