<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Demo data for both services. Skips itself when users already exist so `docker compose up` can run it on every start.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private const CATALOGUE = [
        'Electronics' => ['Phones', 'Laptops', 'Audio'],
        'Home & Kitchen' => ['Cookware', 'Furniture'],
        'Sports' => ['Fitness', 'Outdoor'],
        'Books' => ['Fiction', 'Technology'],
    ];

    public function run(): void
    {
        if (User::withTrashed()->exists()) {
            $this->command?->info('Database already seeded, skipping.');

            return;
        }

        fake()->seed(2026);

        User::factory()->admin()->create(['name' => 'Admin', 'email' => 'admin@example.com']);
        User::factory()->create(['name' => 'Demo Customer', 'email' => 'customer@example.com']);
        $customers = User::factory(10)->create()->push(User::where('email', 'customer@example.com')->first());

        $products = $this->seedCatalogue();
        $this->seedOrders($customers, $products);
    }

    /**
     * @return Collection<int, Product>
     */
    private function seedCatalogue(): Collection
    {
        $products = collect();

        foreach (self::CATALOGUE as $rootName => $children) {
            $root = Category::create(['name' => $rootName, 'slug' => Str::slug($rootName)]);

            foreach ($children as $childName) {
                $child = Category::create(['name' => $childName, 'slug' => Str::slug($childName), 'parent_id' => $root->id]);

                foreach (range(1, 3) as $i) {
                    $name = Str::title(fake()->unique()->words(2, true)).' '.Str::singular($childName);
                    $product = Product::create([
                        'category_id' => $child->id,
                        'sku' => strtoupper(Str::substr($child->slug, 0, 3)).'-'.str_pad((string) ($products->count() + 1), 4, '0', STR_PAD_LEFT),
                        'name' => $name,
                        'slug' => Str::slug($name),
                        'description' => fake()->paragraph(),
                        'price' => fake()->randomFloat(2, 5, 1500),
                        'is_active' => fake()->boolean(90),
                    ]);
                    Inventory::create([
                        'product_id' => $product->id,
                        'quantity' => fake()->randomElement([0, 3, 8, ...range(15, 120, 15)]),
                        'low_stock_threshold' => 10,
                    ]);
                    $products->push($product);
                }
            }
        }

        return $products;
    }

    /**
     * @param  Collection<int, User>  $customers
     * @param  Collection<int, Product>  $products
     */
    private function seedOrders(Collection $customers, Collection $products): void
    {
        $statuses = ['delivered' => 40, 'shipped' => 15, 'processing' => 10, 'paid' => 15, 'pending' => 10, 'cancelled' => 10];

        foreach (range(1, 60) as $n) {
            $placedAt = now()->subDays(fake()->numberBetween(0, 60))->setTime(fake()->numberBetween(8, 22), fake()->numberBetween(0, 59));
            $status = $this->weighted($statuses);
            $lines = $products->random(fake()->numberBetween(1, 4));

            $order = Order::create([
                'order_number' => 'ORD-'.$placedAt->format('Ymd').'-'.strtoupper(Str::random(8)),
                'user_id' => $customers->random()->id,
                'status' => $status,
                'payment_status' => match ($status) {
                    'pending' => 'pending',
                    'cancelled' => fake()->boolean() ? 'refunded' : 'failed',
                    default => 'paid',
                },
                'payment_reference' => in_array($status, ['pending'], true) ? null : 'pay_'.Str::uuid(),
                'subtotal' => 0,
                'total' => 0,
            ]);

            $subtotal = 0;
            foreach ($lines as $product) {
                $quantity = fake()->numberBetween(1, 3);
                $lineTotal = round((float) $product->price * $quantity, 2);
                $subtotal += $lineTotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ]);
            }

            $order->forceFill([
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'created_at' => $placedAt,
                'updated_at' => $placedAt,
            ])->saveQuietly();
        }
    }

    /**
     * @param  array<string, int>  $weights
     */
    private function weighted(array $weights): string
    {
        $roll = fake()->numberBetween(1, array_sum($weights));
        foreach ($weights as $value => $weight) {
            if (($roll -= $weight) <= 0) {
                return $value;
            }
        }

        return array_key_first($weights);
    }
}
