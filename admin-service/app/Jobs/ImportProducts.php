<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class ImportProducts implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public const COLUMNS = ['sku', 'name', 'category_slug', 'price', 'quantity', 'low_stock_threshold', 'is_active', 'description'];

    public const REQUIRED_COLUMNS = ['sku', 'name', 'category_slug', 'price'];

    public function __construct(
        public readonly string $path,
        public readonly int $userId,
    ) {}

    public static function summaryKey(int $userId): string
    {
        return "product-import:{$userId}";
    }

    public function handle(): void
    {
        $summary = ['created' => 0, 'updated' => 0, 'failed' => 0, 'errors' => [], 'finished_at' => now()->toDateTimeString()];

        try {
            $this->import($summary);
        } catch (RuntimeException $e) {
            $summary['errors'][] = $e->getMessage();
        } finally {
            Storage::disk('local')->delete($this->path);
            $summary['finished_at'] = now()->toDateTimeString();
            Cache::put(self::summaryKey($this->userId), $summary, now()->addDay());
        }
    }

    private function import(array &$summary): void
    {
        $handle = fopen(Storage::disk('local')->path($this->path), 'r');
        if ($handle === false) {
            throw new RuntimeException('Uploaded file could not be read.');
        }

        $header = array_map(fn ($h) => Str::of((string) $h)->trim()->lower()->replace("\u{FEFF}", '')->toString(), fgetcsv($handle) ?: []);
        $missing = array_diff(self::REQUIRED_COLUMNS, $header);
        if ($missing !== []) {
            fclose($handle);
            throw new RuntimeException('Missing required columns: '.implode(', ', $missing));
        }

        $categories = Category::pluck('id', 'slug');
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if ($row === [null]) {
                continue;
            }
            if (count($row) !== count($header)) {
                $this->fail($summary, $line, 'column count does not match the header');

                continue;
            }

            $data = array_combine($header, array_map('trim', $row));
            $validator = Validator::make($data, [
                'sku' => ['required', 'string', 'max:100'],
                'name' => ['required', 'string', 'max:255'],
                'category_slug' => ['required', 'string'],
                'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
                'quantity' => ['nullable', 'integer', 'min:0'],
                'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
                'is_active' => ['nullable', 'in:0,1,true,false,yes,no'],
                'description' => ['nullable', 'string', 'max:10000'],
            ]);
            if ($validator->fails()) {
                $this->fail($summary, $line, implode(' ', $validator->errors()->all()));

                continue;
            }

            $categoryId = $categories->get($data['category_slug']);
            if (! $categoryId) {
                $this->fail($summary, $line, "unknown category \"{$data['category_slug']}\"");

                continue;
            }

            DB::transaction(function () use ($data, $categoryId, &$summary) {
                $product = Product::withTrashed()->firstOrNew(['sku' => $data['sku']]);
                $isNew = ! $product->exists;

                $product->fill([
                    'category_id' => $categoryId,
                    'name' => $data['name'],
                    'price' => $data['price'],
                    'description' => ($data['description'] ?? '') ?: $product->description,
                    'is_active' => in_array(strtolower($data['is_active'] ?? '1'), ['1', 'true', 'yes'], true),
                ]);
                if ($isNew) {
                    $product->slug = $this->uniqueSlug($data['name'], $data['sku']);
                }
                $product->save();
                if ($product->trashed()) {
                    $product->restore();
                }

                Inventory::updateOrCreate(['product_id' => $product->id], [
                    'quantity' => (int) ($data['quantity'] ?? 0),
                    'low_stock_threshold' => (int) (($data['low_stock_threshold'] ?? '') !== '' ? $data['low_stock_threshold'] : 10),
                ]);

                $summary[$isNew ? 'created' : 'updated']++;
            });
        }

        fclose($handle);
    }

    private function fail(array &$summary, int $line, string $message): void
    {
        $summary['failed']++;
        if (count($summary['errors']) < 50) {
            $summary['errors'][] = "Line {$line}: {$message}";
        }
    }

    private function uniqueSlug(string $name, string $sku): string
    {
        $slug = Str::slug($name);

        return Product::withTrashed()->where('slug', $slug)->exists() ? $slug.'-'.Str::slug($sku) : $slug;
    }
}
