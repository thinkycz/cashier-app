<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_index_is_displayed_with_expected_payload(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct([
            'name' => 'Mineral Water',
            'ean' => '8591234567890',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('products.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Index')
                ->has('products.data', 1)
                ->has('products.links')
                ->where('products.data.0.id', $product->id)
                ->where('products.data.0.name', $product->name)
                ->has('filters')
            );
    }

    public function test_products_index_persists_search_filter_in_props(): void
    {
        $user = User::factory()->create();
        $this->createProduct([
            'name' => 'Orange Juice',
            'ean' => '8591234567891',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('products.index', ['search' => 'Orange']));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Index')
                ->where('filters.search', 'Orange')
            );
    }

    public function test_products_create_page_uses_shared_form_component_in_create_mode(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('products.create'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Form')
                ->where('mode', 'create')
                ->where('product', null)
            );
    }

    public function test_products_edit_page_uses_shared_form_component_in_edit_mode(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct([
            'name' => 'Espresso Beans',
            'ean' => '8591234567892',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('products.edit', $product));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Form')
                ->where('mode', 'edit')
                ->where('product.id', $product->id)
                ->where('product.name', $product->name)
            );
    }

    public function test_products_show_page_renders_with_expected_product_payload(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct([
            'name' => 'Dark Chocolate',
            'ean' => '8591234567893',
            'vat_rate' => 15.00,
            'price' => 49.90,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('products.show', $product));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Show')
                ->where('product.id', $product->id)
                ->where('product.name', 'Dark Chocolate')
                ->where('product.ean', '8591234567893')
            );
    }

    public function test_short_name_is_saved_when_creating_product(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('products.store'), [
                'name' => 'Vanilla Yogurt',
                'short_name' => 'Yogurt',
                'ean' => '8591234567894',
                'vat_rate' => 21,
                'price' => 24.90,
                'is_active' => true,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Vanilla Yogurt',
            'short_name' => 'Yogurt',
        ]);
    }

    public function test_short_name_is_updated_when_editing_product(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct([
            'name' => 'Apple Juice',
            'short_name' => 'Apple',
            'ean' => '8591234567895',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('products.update', $product), [
                'name' => 'Apple Juice Premium',
                'short_name' => 'AJ Premium',
                'ean' => '8591234567895',
                'vat_rate' => 21,
                'price' => 39.90,
                'is_active' => true,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Apple Juice Premium',
            'short_name' => 'AJ Premium',
        ]);
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Sample Product',
            'short_name' => null,
            'ean' => null,
            'vat_rate' => 21.00,
            'price' => 99.90,
            'is_active' => true,
        ], $overrides));
    }
}
