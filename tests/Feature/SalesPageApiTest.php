<?php

namespace Tests\Feature;

use App\Models\SalesPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesPageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_sales_pages_endpoints(): void
    {
        $this->getJson('/api/sales-pages')->assertUnauthorized();
        $this->postJson('/api/sales-pages', [])->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_only_their_sales_pages(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownedPage = SalesPage::query()->create([
            'user_id' => $user->id,
            'product_name' => 'Madu Hutan Liar',
            'raw_input' => ['description' => 'Madu murni'],
            'ai_output' => $this->validAiOutput('Beli Madu Hutan Asli'),
            'theme' => 'dark-luxury',
        ]);

        SalesPage::query()->create([
            'user_id' => $otherUser->id,
            'product_name' => 'Kopi Arabika',
            'raw_input' => ['description' => 'Kopi premium'],
            'ai_output' => $this->validAiOutput('Seduh Kopi Premium'),
            'theme' => 'minimalist',
        ]);

        $response = $this->withToken($this->tokenFor($user))
            ->getJson('/api/sales-pages');

        $response
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $ownedPage->id)
            ->assertJsonPath('0.product_name', 'Madu Hutan Liar')
            ->assertJsonPath('0.created_at', $ownedPage->created_at?->toJSON())
            ->assertJsonMissingPath('0.raw_input')
            ->assertJsonMissingPath('0.ai_output')
            ->assertJsonMissingPath('0.theme');
    }

    public function test_authenticated_user_can_view_their_own_sales_page_detail(): void
    {
        $user = User::factory()->create();
        $salesPage = SalesPage::query()->create([
            'user_id' => $user->id,
            'product_name' => 'Madu Hutan Liar',
            'raw_input' => ['description' => 'Madu murni'],
            'ai_output' => $this->validAiOutput('Beli Madu Hutan Asli'),
            'theme' => 'dark-luxury',
        ]);

        $response = $this->withToken($this->tokenFor($user))
            ->getJson("/api/sales-pages/{$salesPage->id}");

        $response
            ->assertOk()
            ->assertJsonPath('id', $salesPage->id)
            ->assertJsonPath('product_name', 'Madu Hutan Liar')
            ->assertJsonPath('theme', 'dark-luxury')
            ->assertJsonPath('raw_input.description', 'Madu murni')
            ->assertJsonPath('ai_output.hero.headline', 'Beli Madu Hutan Asli');
    }

    public function test_user_cannot_view_another_users_sales_page_detail(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $salesPage = SalesPage::query()->create([
            'user_id' => $otherUser->id,
            'product_name' => 'Kopi Arabika',
            'raw_input' => ['description' => 'Kopi premium'],
            'ai_output' => $this->validAiOutput('Seduh Kopi Premium'),
            'theme' => null,
        ]);

        $this->withToken($this->tokenFor($user))
            ->getJson("/api/sales-pages/{$salesPage->id}")
            ->assertNotFound();
    }

    public function test_authenticated_user_can_store_a_sales_page(): void
    {
        $user = User::factory()->create();

        $payload = [
            'product_name' => 'Madu Hutan Liar',
            'raw_input' => [
                'description' => 'Madu murni dari hutan kalimantan.',
                'key_features' => ['Organik', 'Tanpa Gula Tambahan'],
                'target_audience' => 'Orang dewasa',
                'price' => 'Rp 150.000',
                'usp' => 'Garansi uang kembali',
            ],
            'ai_output' => $this->validAiOutput('Beli Madu Hutan Asli'),
            'theme' => 'dark-luxury',
        ];

        $response = $this->withToken($this->tokenFor($user))
            ->postJson('/api/sales-pages', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('product_name', 'Madu Hutan Liar')
            ->assertJsonPath('theme', 'dark-luxury')
            ->assertJsonPath('raw_input.description', 'Madu murni dari hutan kalimantan.')
            ->assertJsonPath('ai_output.hero.headline', 'Beli Madu Hutan Asli');

        $this->assertDatabaseHas('sales_pages', [
            'user_id' => $user->id,
            'product_name' => 'Madu Hutan Liar',
            'theme' => 'dark-luxury',
        ]);
    }

    public function test_authenticated_user_can_store_preview_html_inside_ai_output(): void
    {
        $user = User::factory()->create();
        $payload = $this->validSalesPagePayload();
        $payload['ai_output']['preview_html'] = '<!DOCTYPE html><html><body><section>Preview HTML</section></body></html>';

        $response = $this->withToken($this->tokenFor($user))
            ->postJson('/api/sales-pages', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('ai_output.preview_html', '<!DOCTYPE html><html><body><section>Preview HTML</section></body></html>');

        $this->assertDatabaseHas('sales_pages', [
            'user_id' => $user->id,
            'product_name' => 'Madu Hutan Liar',
        ]);

        $this->assertSame(
            '<!DOCTYPE html><html><body><section>Preview HTML</section></body></html>',
            SalesPage::query()->firstOrFail()->ai_output['preview_html'] ?? null,
        );
    }

    public function test_store_sales_page_requires_product_name(): void
    {
        $user = User::factory()->create();
        $payload = $this->validSalesPagePayload();
        $payload['product_name'] = '';

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/sales-pages', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_name']);
    }

    public function test_store_sales_page_requires_key_features_to_be_an_array(): void
    {
        $user = User::factory()->create();
        $payload = $this->validSalesPagePayload();
        $payload['raw_input']['key_features'] = 'Organik';

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/sales-pages', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['raw_input.key_features']);
    }

    public function test_store_sales_page_requires_ai_output_hero_headline(): void
    {
        $user = User::factory()->create();
        $payload = $this->validSalesPagePayload();
        unset($payload['ai_output']['hero']['headline']);

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/sales-pages', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ai_output.hero.headline']);
    }

    public function test_authenticated_user_can_delete_their_own_sales_page(): void
    {
        $user = User::factory()->create();
        $salesPage = SalesPage::query()->create([
            'user_id' => $user->id,
            'product_name' => 'Madu Hutan Liar',
            'raw_input' => ['description' => 'Madu murni'],
            'ai_output' => $this->validAiOutput('Beli Madu Hutan Asli'),
            'theme' => 'dark-luxury',
        ]);

        $this->withToken($this->tokenFor($user))
            ->deleteJson("/api/sales-pages/{$salesPage->id}")
            ->assertOk()
            ->assertJson([
                'message' => 'Sales page deleted successfully.',
            ]);

        $this->assertDatabaseMissing('sales_pages', [
            'id' => $salesPage->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_sales_page(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $salesPage = SalesPage::query()->create([
            'user_id' => $otherUser->id,
            'product_name' => 'Kopi Arabika',
            'raw_input' => ['description' => 'Kopi premium'],
            'ai_output' => $this->validAiOutput('Seduh Kopi Premium'),
            'theme' => null,
        ]);

        $this->withToken($this->tokenFor($user))
            ->deleteJson("/api/sales-pages/{$salesPage->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('sales_pages', [
            'id' => $salesPage->id,
        ]);
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    /**
     * @return array<string, mixed>
     */
    private function validSalesPagePayload(): array
    {
        return [
            'product_name' => 'Madu Hutan Liar',
            'raw_input' => [
                'description' => 'Madu murni dari hutan kalimantan.',
                'key_features' => ['Organik', 'Tanpa Gula Tambahan'],
                'target_audience' => 'Orang dewasa',
                'price' => 'Rp 150.000',
                'usp' => 'Garansi uang kembali',
            ],
            'ai_output' => $this->validAiOutput('Beli Madu Hutan Asli'),
            'theme' => 'dark-luxury',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validAiOutput(string $headline): array
    {
        return [
            'hero' => [
                'headline' => $headline,
                'subheadline' => 'Copy yang persuasif untuk preview.',
            ],
            'benefits' => [
                ['title' => 'Benefit 1', 'description' => 'Deskripsi benefit 1'],
            ],
            'features' => ['Feature 1', 'Feature 2'],
            'social_proof' => [
                ['name' => 'Dummy Name 1', 'review' => 'Review positif'],
            ],
            'pricing' => [
                'price_text' => 'Rp 150.000',
                'call_to_action_text' => 'Beli Sekarang',
                'guarantee' => '30 hari garansi',
            ],
        ];
    }
}
