<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Admin\CatalogManager;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_catalogue_route_enforces_the_admin_boundary(): void
    {
        $this->get('/admin/catalogo')->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->get('/admin/catalogo')
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/catalogo')
            ->assertOk()
            ->assertSeeText('Gerenciar catálogo')
            ->assertSeeLivewire(CatalogManager::class);
    }

    public function test_non_admin_cannot_invoke_catalogue_component_actions(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CatalogManager::class)
            ->assertForbidden();
    }

    public function test_admin_can_create_and_update_a_category_with_server_generated_slug(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CatalogManager::class)
            ->set('categoryName', 'Eletrônicos de Teste')
            ->set('categoryDescription', 'Categoria exclusivamente fictícia.')
            ->set('categoryStatus', 'active')
            ->call('saveCategory')
            ->assertHasNoErrors();

        $category = Category::firstOrFail();
        $this->assertSame('eletronicos-de-teste', $category->slug);

        Livewire::actingAs($admin)
            ->test(CatalogManager::class)
            ->call('editCategory', $category->id)
            ->set('categoryName', 'Eletrônicos Inativos')
            ->set('categoryStatus', 'inactive')
            ->call('saveCategory')
            ->assertHasNoErrors();

        $this->assertSame('eletronicos-inativos', $category->fresh()->slug);
        $this->assertSame('inactive', $category->fresh()->status->value);
    }

    public function test_category_slug_collision_is_reported_as_validation_error(): void
    {
        Category::factory()->create(['name' => 'Casa', 'slug' => 'casa']);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(CatalogManager::class)
            ->set('categoryName', 'Cása')
            ->set('categoryStatus', 'active')
            ->call('saveCategory')
            ->assertHasErrors(['categoryName']);

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_admin_can_soft_delete_and_restore_a_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        Livewire::actingAs($admin)
            ->test(CatalogManager::class)
            ->call('deleteCategory', $category->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted($category);

        Livewire::actingAs($admin)
            ->test(CatalogManager::class)
            ->call('restoreCategory', $category->id)
            ->assertHasNoErrors();

        $this->assertNotSoftDeleted($category->fresh());
    }

    public function test_persisted_category_content_is_escaped(): void
    {
        $admin = User::factory()->admin()->create();
        $unsafe = '<script>alert("catalog")</script>';

        Livewire::actingAs($admin)
            ->test(CatalogManager::class)
            ->set('categoryName', $unsafe)
            ->set('categoryDescription', $unsafe)
            ->set('categoryStatus', 'active')
            ->call('saveCategory')
            ->assertHasNoErrors()
            ->assertSee($unsafe)
            ->assertDontSee($unsafe, false);
    }
}
