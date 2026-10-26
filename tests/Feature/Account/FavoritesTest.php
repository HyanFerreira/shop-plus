<?php

namespace Tests\Feature\Account;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FavoritesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_read_or_mutate_favorites(): void
    {
        $product = Product::factory()->create();
        $this->get('/favoritos')->assertRedirect('/login');
        $this->postJson('/favoritos/'.$product->id)->assertUnauthorized();
        $this->deleteJson('/favoritos/'.$product->id)->assertUnauthorized();
        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_add_and_remove_are_idempotent_and_ignore_supplied_user_ids(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $product = Product::factory()->create();
        $b->favoriteProducts()->attach($product);
        $this->actingAs($a);
        foreach (range(1, 2) as $_) {
            $this->postJson('/favoritos/'.$product->id, ['user_id' => $b->id])->assertOk()->assertJson(['favorited' => true]);
        }
        $this->assertDatabaseCount('favorites', 2);
        foreach (range(1, 2) as $_) {
            $this->deleteJson('/favoritos/'.$product->id, ['user_id' => $b->id])->assertOk()->assertJson(['favorited' => false]);
        }
        $this->assertDatabaseMissing('favorites', ['user_id' => $a->id]);
        $this->assertDatabaseHas('favorites', ['user_id' => $b->id, 'product_id' => $product->id]);
    }

    public function test_database_itself_rejects_duplicates(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $user->favoriteProducts()->attach($product);
        $this->expectException(QueryException::class);
        $user->favoriteProducts()->attach($product);
    }

    public function test_invalid_ids_and_hidden_products_cannot_be_added(): void
    {
        $user = User::factory()->create();
        $inactive = Product::factory()->inactive()->create();
        $hiddenCategory = Product::factory()->create();
        $hiddenCategory->category->update(['status' => 'inactive']);
        $deleted = Product::factory()->create();
        $deleted->delete();
        $this->actingAs($user);
        foreach (['invalid', '-1', '0', '999999999', $inactive->id, $hiddenCategory->id, $deleted->id] as $id) {
            $this->postJson('/favoritos/'.$id)->assertNotFound();
        }
        foreach (['invalid', '-1', '0', '999999999'] as $id) {
            $this->deleteJson('/favoritos/'.$id)->assertNotFound();
        }
        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_list_is_private_paginated_and_uses_current_product_data(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $products = Product::factory()->count(13)->create();
        $a->favoriteProducts()->attach($products->modelKeys());
        $foreign = Product::factory()->create(['name' => 'Favorito privado B']);
        $b->favoriteProducts()->attach($foreign);
        $latest = $products->last();
        $latest->update(['price_cents' => 12345]);
        $this->actingAs($a)->get('/favoritos?user_id='.$b->id)->assertOk()
            ->assertSee($latest->name)->assertSeeText('R$ 123,45')->assertSeeText('Indisponível no momento')
            ->assertDontSeeText($foreign->name)->assertSee('gotoPage(2', false);
        $this->get('/favoritos?page=2')->assertOk()->assertSee($products->first()->name);
    }

    public function test_unpublished_deleted_products_are_hidden_and_can_be_removed(): void
    {
        $user = User::factory()->create();
        $products = Product::factory()->count(3)->create();
        $user->favoriteProducts()->attach($products->modelKeys());
        $products[0]->update(['status' => 'inactive']);
        $products[1]->category->update(['status' => 'inactive']);
        $products[2]->delete();
        $response = $this->actingAs($user)->get('/favoritos')->assertOk()
            ->assertSeeText('Você ainda não tem favoritos disponíveis')->assertSee(route('catalog.index'));
        foreach ($products as $product) {
            $response->assertDontSeeText($product->name);
            $this->deleteJson('/favoritos/'.$product->id)->assertOk();
        }
        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_csrf_is_required_for_favorites_and_logout(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create());
        // Enable the real CSRF check normally bypassed by Laravel's test runtime.
        $this->app->instance('env', 'production');
        $this->postJson('/favoritos/'.$product->id)->assertStatus(419);
        $this->deleteJson('/favoritos/'.$product->id)->assertStatus(419);
        $this->post('/logout')->assertStatus(419);
        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_disabled_users_cannot_access_favorites(): void
    {
        $product = Product::factory()->create();
        foreach (['GET', 'POST', 'DELETE'] as $method) {
            $this->actingAs(User::factory()->create(['status' => 'disabled']));
            $this->call($method, $method === 'GET' ? '/favoritos' : '/favoritos/'.$product->id)->assertRedirect('/login');
            $this->assertGuest('web');
        }
        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_current_users_favorite_state_is_rendered_without_nested_controls(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        User::factory()->create()->favoriteProducts()->attach($product);
        $this->actingAs($user)->get('/catalogo/'.$product->slug)->assertOk()
            ->assertSee('aria-pressed="false"', false);
        $user->favoriteProducts()->attach($product);
        foreach (['/', '/catalogo', '/catalogo/'.$product->slug, '/favoritos'] as $path) {
            $response = $this->get($path)->assertOk()->assertSee('aria-pressed="true"', false);
            $document = new \DOMDocument;
            @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
            $xpath = new \DOMXPath($document);
            $this->assertSame(0, $xpath->query('//a//button | //button//button | //a//form | //form//form')->length);
            $this->assertSame(0, $xpath->query('//main//aside[contains(@class,"customer-navigation")]')->length);
        }
    }

    public function test_csrf_token_allows_real_favorite_operations(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create())->withSession(['_token' => 'synthetic-csrf-test']);
        $this->app->instance('env', 'production');
        $this->postJson('/favoritos/'.$product->id, [], ['X-CSRF-TOKEN' => 'synthetic-csrf-test'])->assertOk();
        $this->deleteJson('/favoritos/'.$product->id, [], ['X-CSRF-TOKEN' => 'synthetic-csrf-test'])->assertOk();
        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_card_state_survives_fresh_page_requests_after_add_and_remove(): void
    {
        $product = Product::factory()->create();
        $related = Product::factory()->create(['category_id' => $product->category_id]);
        $this->actingAs(User::factory()->create());

        foreach ([true, false] as $favorited) {
            $this->json($favorited ? 'POST' : 'DELETE', '/favoritos/'.$product->id)
                ->assertOk()->assertJson(['favorited' => $favorited]);

            foreach (['/', '/catalogo', '/catalogo/'.$related->slug] as $path) {
                $response = $this->get($path)->assertOk();
                $document = new \DOMDocument;
                @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
                $xpath = new \DOMXPath($document);
                $button = $xpath->query('//div[contains(@class,"favorite-control--card")]//form[@action="'.route('favorites.store', $product->id).'"]//button')->item(0);
                $this->assertNotNull($button, $path);
                $this->assertSame($favorited ? 'true' : 'false', $button->getAttribute('aria-pressed'), $path);
            }
            $favorites = $this->get('/favoritos')->assertOk();
            if ($favorited) {
                $favorites->assertSee('aria-pressed="true"', false)->assertSeeText($product->name);
            } else {
                $favorites->assertSeeText('Você ainda não tem favoritos disponíveis');
            }
        }
    }

    public function test_product_and_user_hard_deletes_cascade(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $user->favoriteProducts()->attach($product);
        $product->forceDelete();
        $this->assertDatabaseCount('favorites', 0);
        $user->favoriteProducts()->attach(Product::factory()->create());
        DB::table('users')->where('id', $user->id)->delete();
        $this->assertDatabaseCount('favorites', 0);
    }
}
