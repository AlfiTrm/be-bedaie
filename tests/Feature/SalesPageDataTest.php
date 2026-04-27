<?php

namespace Tests\Feature;

use App\Models\SalesPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SalesPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_pages_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('sales_pages'));

        $this->assertTrue(Schema::hasColumns('sales_pages', [
            'id',
            'user_id',
            'product_name',
            'raw_input',
            'ai_output',
            'theme',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_user_can_own_many_sales_pages(): void
    {
        $user = User::factory()->create();

        SalesPage::query()->create([
            'user_id' => $user->id,
            'product_name' => 'Madu Hutan Liar',
            'raw_input' => [
                'description' => 'Madu murni dari hutan kalimantan.',
                'key_features' => ['Organik', 'Tanpa Gula Tambahan'],
                'target_audience' => 'Orang dewasa',
                'price' => 'Rp 150.000',
                'usp' => 'Garansi uang kembali',
            ],
            'ai_output' => [
                'hero' => [
                    'headline' => 'Madu Murni untuk Imunitas Kuat',
                    'subheadline' => 'Rasa alami, kualitas hutan liar.',
                ],
                'benefits' => [],
                'features' => ['Organik'],
                'social_proof' => [],
                'pricing' => [
                    'price_text' => 'Rp 150.000',
                    'call_to_action_text' => 'Beli Sekarang',
                    'guarantee' => '30 hari garansi',
                ],
            ],
            'theme' => 'dark-luxury',
        ]);

        $this->assertCount(1, $user->salesPages);
        $this->assertInstanceOf(SalesPage::class, $user->salesPages->first());
    }

    public function test_sales_page_casts_json_attributes_and_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $salesPage = SalesPage::query()->create([
            'user_id' => $user->id,
            'product_name' => 'Kopi Arabika Premium',
            'raw_input' => [
                'description' => 'Kopi arabika dengan aroma floral.',
            ],
            'ai_output' => [
                'hero' => [
                    'headline' => 'Seduh Kopi yang Bikin Nagih',
                    'subheadline' => 'Aroma floral dan body yang seimbang.',
                ],
                'benefits' => [],
                'features' => ['Roasted fresh'],
                'social_proof' => [],
                'pricing' => [
                    'price_text' => 'Rp 95.000',
                    'call_to_action_text' => 'Pesan Sekarang',
                    'guarantee' => 'Kopi diganti jika tidak fresh',
                ],
            ],
            'theme' => null,
        ]);

        $this->assertIsArray($salesPage->raw_input);
        $this->assertIsArray($salesPage->ai_output);
        $this->assertSame('Kopi Arabika Premium', $salesPage->product_name);
        $this->assertTrue($salesPage->user->is($user));
    }
}
