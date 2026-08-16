<?php

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\Product;
use App\Services\CampaignCustomPageService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CampaignCustomPageServiceTest extends TestCase
{
    public function test_complete_html_upload_is_split_into_clean_editors(): void
    {
        $service = new CampaignCustomPageService();
        $source = <<<'HTML'
<!doctype html><html><head>
<style>.hero { color: red; }</style>
<script>window.previewReady = true;</script>
<script src="https://example.com/remote.js"></script>
</head><body><h1>{{ product_name }}</h1><?php echo 'unsafe'; ?></body></html>
HTML;

        $result = $service->splitHtmlUpload($source);

        $this->assertSame('<h1>{{ product_name }}</h1>', $result['html']);
        $this->assertStringContainsString('.hero { color: red; }', $result['css']);
        $this->assertStringContainsString('window.previewReady = true;', $result['js']);
        $this->assertStringNotContainsString('remote.js', $result['js']);
        $this->assertStringNotContainsString('<?php', $result['html']);
    }

    public function test_live_tokens_are_replaced_and_commerce_is_never_omitted(): void
    {
        $service = new CampaignCustomPageService();
        $campaign = new Campaign([
            'name' => 'Summer Campaign',
            'short_description' => '<b>Soft cotton</b>',
        ]);
        $product = new Product([
            'id' => 15,
            'name' => 'Premium T-Shirt',
            'new_price' => 990,
            'old_price' => 1490,
            'stock' => 8,
        ]);
        $product->setRelation('variantPrices', new Collection());

        $html = $service->render(
            '<h1>{{ product_name }}</h1><p>৳{{ product_price }}</p><div>{{ reviews }}</div>',
            $campaign,
            collect([$product])
        );

        $this->assertStringContainsString('Premium T-Shirt', $html);
        $this->assertStringContainsString('৳990', $html);
        $this->assertStringContainsString('data-cpb-dynamic="reviews"', $html);
        $this->assertStringContainsString('data-cpb-dynamic="products"', $html);
        $this->assertStringContainsString('data-cpb-dynamic="checkout"', $html);
    }

    public function test_unknown_blade_and_php_are_not_stored(): void
    {
        $service = new CampaignCustomPageService();
        $clean = $service->cleanHtml(
            '@include("secret") {{ config("app.key") }} {{ stock }} {!! $danger !!} <?php phpinfo(); ?>'
        );

        $this->assertSame('{{ stock }}', $clean);
    }
}
