<?php

use App\Mail\BackInStock;
use App\Mail\NewContactMessage;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockAlert;
use App\Models\User;
use App\Support\Cart;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(fn () => Mail::fake());

describe('contact form', function () {
    it('saves the message and emails it to the shop', function () {
        Setting::put(['email' => 'owner@kimdern.test']);

        Livewire::test('pages::shop.contact')
            ->set('name', 'Ada Obi')
            ->set('email', 'ada@example.com')
            ->set('subject', 'order')
            ->set('orderNumber', 'kds-abc1234')
            ->set('message', 'Where is my order please? It has been three days.')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', true);

        $message = ContactMessage::query()->sole();
        expect($message->order_number)->toBe('KDS-ABC1234')->and($message->read_at)->toBeNull();
        Mail::assertSent(NewContactMessage::class, fn ($mail) => $mail->hasTo('owner@kimdern.test') && $mail->hasReplyTo('ada@example.com'));
    });

    it('validates the form', function () {
        Livewire::test('pages::shop.contact')
            ->set('email', 'nope')
            ->set('subject', 'hacking')
            ->set('message', 'short')
            ->call('send')
            ->assertHasErrors(['name', 'email', 'subject', 'message']);
    });

    it('quietly drops messages from bots that fill the hidden field', function () {
        Livewire::test('pages::shop.contact')
            ->set('name', 'Bot')->set('email', 'bot@example.com')->set('message', 'Buy cheap followers now!!!')
            ->set('website', 'http://spam.example')
            ->call('send')
            ->assertSet('sent', true);

        expect(ContactMessage::query()->count())->toBe(0);
        Mail::assertNothingSent();
    });

    it('lets the owner read messages in the admin', function () {
        $message = ContactMessage::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'subject' => 'product', 'message' => 'Is the serum good for oily skin?']);

        Livewire::actingAs(User::factory()->admin()->create())->test('pages::admin.messages')
            ->assertSee('Ada')
            ->call('open', $message->id)
            ->assertSee('Is the serum good for oily skin?');

        expect($message->fresh()->read_at)->not->toBeNull();
    });
});

describe('back in stock alerts', function () {
    it('lets a shopper ask to be told when a sold-out size returns', function () {
        $product = sizedProduct(['M' => 0, 'L' => 3]);

        Livewire::test('pages::shop.product', ['product' => $product])
            ->set('size', 'M')
            ->assertSee('Notify me')
            ->set('alertEmail', 'ada@example.com')
            ->call('requestStockAlert')
            ->assertSet('alertSaved', true);

        $alert = StockAlert::query()->sole();
        expect($alert->product_variant_id)->toBe($product->variants->firstWhere('size', 'M')->id);
    });

    it('emails waiting shoppers when the owner adds stock, only once', function () {
        $product = sizedProduct(['M' => 0, 'L' => 0]);
        $medium = $product->variants->firstWhere('size', 'M');
        StockAlert::query()->create(['product_id' => $product->id, 'product_variant_id' => $medium->id, 'email' => 'ada@example.com']);
        StockAlert::query()->create(['product_id' => $product->id, 'product_variant_id' => $product->variants->firstWhere('size', 'L')->id, 'email' => 'bola@example.com']);

        $form = Livewire::actingAs(User::factory()->admin()->create())->test('pages::admin.product-form', ['product' => $product]);
        $index = collect($form->get('variants'))->search(fn ($row) => $row['id'] === $medium->id);
        $form->set("variants.{$index}.stock", 5)->call('save')->assertHasNoErrors();

        Mail::assertSent(BackInStock::class, 1);
        Mail::assertSent(BackInStock::class, fn ($mail) => $mail->hasTo('ada@example.com'));

        Livewire::actingAs(User::factory()->admin()->create())->test('pages::admin.product-form', ['product' => $product->fresh()])->call('save');
        Mail::assertSent(BackInStock::class, 1);
    });
});

it('puts a past order back in the cart with Buy again', function () {
    $user = User::factory()->create();
    $product = simpleProduct(['stock' => 5]);
    $gone = simpleProduct(['is_active' => false]);
    $order = Order::factory()->for($user)->create();
    $order->items()->create(['product_id' => $product->id, 'name' => $product->name, 'price' => 100, 'quantity' => 2, 'line_total' => 200]);
    $order->items()->create(['product_id' => $gone->id, 'name' => 'Old Thing', 'price' => 100, 'quantity' => 1, 'line_total' => 100]);

    Livewire::actingAs($user)->test('pages::account.order', ['order' => $order])
        ->call('buyAgain')
        ->assertRedirect(route('cart'));

    expect(app(Cart::class)->count())->toBe(2)
        ->and(session('buy_again_missed'))->toBe(['Old Thing']);
});

describe('flash sale', function () {
    it('shows sale items with a countdown while the sale is running', function () {
        simpleProduct(['name' => 'Discounted Serum', 'price' => 800000, 'compare_at_price' => 1000000]);
        Setting::put(['flash_sale_title' => 'Black Friday', 'flash_sale_ends_at' => now()->addDay()->format('Y-m-d\TH:i')]);

        $this->get(route('home'))->assertSee('Black Friday')->assertSee('Discounted Serum');
    });

    it('hides itself once the end time has passed', function () {
        simpleProduct(['price' => 800000, 'compare_at_price' => 1000000]);
        Setting::put(['flash_sale_title' => 'Black Friday', 'flash_sale_ends_at' => now()->subMinute()->format('Y-m-d\TH:i')]);

        $this->get(route('home'))->assertDontSee('Black Friday');
    });
});

it('works out the delivery fee for a state on the delivery page', function () {
    lagosZone(fee: 250000, freeOver: 5000000);

    Livewire::test('pages::shop.delivery')
        ->set('state', 'Lagos')->assertSee('₦2,500')
        ->set('basket', '60000')->assertSee('FREE')
        ->set('state', 'Kano')->assertSee("We don't deliver to Kano yet", false);
});

it('remembers recently viewed products', function () {
    $first = simpleProduct(['name' => 'First Look Serum']);
    $second = simpleProduct(['name' => 'Second Look Cream']);

    $this->get(route('product', $first));
    $this->get(route('product', $second))->assertSee('Recently viewed')->assertSee('First Look Serum');
});

it('lists the hair department in the menu and filters by it', function () {
    $wigs = Category::factory()->create(['name' => 'Wigs', 'slug' => 'wigs', 'department' => 'hair']);
    Product::factory()->for($wigs)->create(['name' => 'Body Wave Wig']);
    simpleProduct(['name' => 'Lip Gloss']);

    $this->get(route('home'))->assertSee('Hair')->assertSee('Wigs');
    Livewire::test('pages::shop.catalog')->set('department', 'hair')->assertSee('Body Wave Wig')->assertDontSee('Lip Gloss');
});

it('exports orders to CSV for the owner only', function () {
    Order::factory()->paid()->create(['number' => 'KDS-CSV0001', 'total' => 1250000]);

    $this->actingAs(User::factory()->create())->get(route('admin.orders.export'))->assertForbidden();

    $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.orders.export'));
    $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    expect($response->streamedContent())->toContain('KDS-CSV0001')->toContain('12500');
});

it('lets the owner add a category in the new hair department', function () {
    Livewire::actingAs(User::factory()->admin()->create())->test('pages::admin.categories')
        ->call('create')
        ->set('name', 'Lace Fronts')
        ->set('department', 'hair')
        ->call('save')
        ->assertHasNoErrors();

    expect(Category::query()->where('slug', 'lace-fronts')->value('department'))->toBe('hair');
});
