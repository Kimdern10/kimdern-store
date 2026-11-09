<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Sample categories, products, delivery zones, coupons, banners and reviews.
 * Photos are Unsplash links until the owner uploads real product photos in the admin.
 */
class ShopSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedZones();
        $this->seedCoupons();
        $this->seedBanners();

        $categories = collect([
            ['Skincare', 'beauty', 'Cleansers, serums, moisturisers and sun care for glowing skin.', '1598440947619-2c35fc9aa908'],
            ['Makeup', 'beauty', 'Foundation, lipsticks, palettes and everything for a flawless finish.', '1522335789203-aabd1fc54bc9'],
            ['Hair Care', 'beauty', 'Shampoos, treatments and tools for every hair type.', '1527799820374-dcf8d9d4a388'],
            ['Fragrance', 'beauty', 'Signature scents for him and her.', '1592945403244-b3fbafd7f539'],
            ['Women', 'fashion', 'Dresses, tops and everyday pieces you will love.', '1595777457583-95e059d581b8'],
            ['Men', 'fashion', 'Shirts, tees, jackets and smart looks.', '1617137968427-85924c800a22'],
            ['Shoes', 'fashion', 'Sneakers, heels and shoes for every day.', '1549298916-b41d501d3772'],
            ['Bags & Accessories', 'fashion', 'Handbags, watches and sunglasses to finish the look.', '1584917865442-de89df76afd3'],
        ])->mapWithKeys(fn (array $c, int $i) => [$c[0] => Category::query()->updateOrCreate(['slug' => Str::slug($c[0])], [
            'name' => $c[0],
            'department' => $c[1],
            'description' => $c[2],
            'image' => $this->photo($c[3], 900),
            'sort_order' => $i,
            'is_active' => true,
        ])]);

        $sizes = ['S', 'M', 'L', 'XL'];
        $shoeSizes = ['38', '39', '40', '41', '42', '43'];

        $products = [
            // [category, name, brand, price ₦, compare ₦|null, photos, featured, variants, short description]
            ['Skincare', 'Vitamin C Brightening Serum', 'Glow Lab', 18500, 22000, ['1620916566398-39f1143ab7be', '1617897903246-719242758050'], true, null, 'A lightweight serum with 15% vitamin C that fades dark spots and evens skin tone.'],
            ['Skincare', 'Hydrating Gel Cleanser', 'Glow Lab', 9500, null, ['1556228578-8c89e6adf883'], false, null, 'A gentle, non-drying cleanser for normal to oily skin.'],
            ['Skincare', 'Daily Moisturising Cream', 'Shea & Co', 12000, null, ['1601049541289-9b1b7bbbfe19', '1598440947619-2c35fc9aa908'], true, null, 'Rich shea butter cream that keeps skin soft all day without feeling greasy.'],
            ['Skincare', 'Botanical Face Oil', 'Shea & Co', 15500, 18000, ['1608571423902-eed4a5ad8108', '1611930022073-b7a4ba5fcccd'], false, null, 'A blend of rosehip, jojoba and marula oils for deep night-time nourishment.'],
            ['Skincare', 'Complete Skincare Set', 'Glow Lab', 42000, 50000, ['1571781926291-c477ebfd024b', '1612817288484-6f916006741a'], true, null, 'Cleanser, serum and moisturiser in one gift-ready box.'],
            ['Skincare', 'SPF 50 Invisible Sunscreen', 'Glow Lab', 11500, null, ['1612817288484-6f916006741a'], false, null, 'No white cast, made for melanin-rich skin. Wear it every day.'],
            ['Makeup', 'Velvet Matte Lipstick', 'Kimdern Beauty', 6500, null, ['1586495777744-4413f21062fa'], true, ['colours' => ['Ruby Red' => '#b5122b', 'Cocoa' => '#6b3a2a', 'Nude Rose' => '#c98a7d', 'Berry' => '#7a1f3d']], 'Long-wear matte colour that stays put for up to 12 hours.'],
            ['Makeup', 'Golden Hour Eyeshadow Palette', 'Kimdern Beauty', 14500, 17000, ['1583241800698-e8ab01830a07', '1512496015851-a90fb38ba796'], true, null, '12 rich, blendable shades from soft nudes to bold bronze.'],
            ['Makeup', 'Flawless Finish Foundation', 'Kimdern Beauty', 13500, null, ['1522335789203-aabd1fc54bc9'], false, ['colours' => ['Caramel' => '#a86b3c', 'Mocha' => '#7b4a2a', 'Espresso' => '#4a2c1d', 'Honey' => '#c48a55']], 'Medium-to-full cover foundation with a natural, skin-like finish.'],
            ['Makeup', 'Pro Makeup Brush Set', 'Kimdern Beauty', 16000, 20000, ['1596462502278-27bfdc403348'], false, null, '10 soft synthetic brushes for face and eyes, with a travel pouch.'],
            ['Makeup', 'Gel Nail Polish Trio', 'Kimdern Beauty', 7500, null, ['1599948128020-9a44505b0d1b'], false, null, 'Three bright shades with a glossy gel finish and no lamp needed.'],
            ['Hair Care', 'Repair Hair Mask', 'Curl Theory', 9800, null, ['1608248543803-ba4f8c70ae0b'], false, null, 'Deep conditioning mask for dry, damaged and relaxed hair.'],
            ['Hair Care', 'Moisture Shampoo & Conditioner', 'Curl Theory', 14000, 16500, ['1535585209827-a15fcdbc4c2d'], true, null, 'Sulphate-free duo that cleans without stripping natural oils.'],
            ['Hair Care', 'Salon Brush & Comb Kit', 'Curl Theory', 8500, null, ['1527799820374-dcf8d9d4a388'], false, null, 'Paddle brush, detangling comb and rat-tail comb for everyday styling.'],
            ['Hair Care', 'Growth Oil for Natural Hair', 'Curl Theory', 7200, null, ['1617897903246-719242758050'], false, null, 'Rosemary and castor oil blend that feeds the scalp and edges.'],
            ['Fragrance', 'Rose Noir Eau de Parfum', 'Maison Kimdern', 38000, 45000, ['1592945403244-b3fbafd7f539', '1594035910387-fea47794261f'], true, ['sizes' => ['50ml' => 38000, '100ml' => 62000]], 'Velvety rose and oud with a warm vanilla base.'],
            ['Fragrance', 'Amber Gold Perfume', 'Maison Kimdern', 35000, null, ['1588405748880-12d1d2a59f75'], false, ['sizes' => ['50ml' => 35000, '100ml' => 58000]], 'A sweet, glowing amber scent that lasts from morning to night.'],
            ['Fragrance', 'Midnight Blue for Men', 'Maison Kimdern', 40000, null, ['1523293182086-7651a899d37f'], true, ['sizes' => ['50ml' => 40000, '100ml' => 65000]], 'Fresh citrus and cedarwood for a clean, confident finish.'],
            ['Women', 'Flowing Maxi Dress', 'Lagos Lane', 32000, 38000, ['1595777457583-95e059d581b8', '1572804013309-59a88b7e92f1'], true, ['sizes' => $sizes, 'colours' => ['Red' => '#c81e1e']], 'A floor-length dress with a flattering waist and soft flowing skirt.'],
            ['Women', 'Floral Wrap Dress', 'Lagos Lane', 24500, null, ['1572804013309-59a88b7e92f1'], false, ['sizes' => $sizes, 'colours' => ['Red Print' => '#d0342c']], 'Easy wrap dress in a bright floral print, perfect for owambe and brunch.'],
            ['Women', 'Cosy Lounge Set', 'Lagos Lane', 21000, null, ['1515886657613-9f3515b0c78f'], false, ['sizes' => $sizes, 'colours' => ['Mustard' => '#e0a526', 'Black' => '#111111']], 'Matching hoodie and joggers in soft brushed cotton.'],
            ['Women', 'Classic Trench Coat', 'Lagos Lane', 45000, 52000, ['1485462537746-965f33f7f6a7'], false, ['sizes' => $sizes, 'colours' => ['Blush' => '#e8b4a8']], 'A timeless belted coat that dresses up any outfit.'],
            ['Women', 'Graphic Print Tee', 'Lagos Lane', 9500, null, ['1503342217505-b0a15ec3261c'], false, ['sizes' => $sizes, 'colours' => ['Black' => '#111111', 'White' => '#f5f5f5']], 'Relaxed-fit cotton tee with a bold front print.'],
            ['Men', 'Essential Cotton T-Shirt', 'Northline', 8500, null, ['1521572163474-6864f9cf17ab', '1618354691373-d851c5c3a990'], true, ['sizes' => $sizes, 'colours' => ['White' => '#f5f5f5', 'Black' => '#111111']], 'Heavyweight 100% cotton tee that keeps its shape wash after wash.'],
            ['Men', 'Slim Fit Oxford Shirt', 'Northline', 18500, null, ['1603252109303-2751441dd157'], false, ['sizes' => $sizes, 'colours' => ['White' => '#ffffff', 'Sky Blue' => '#9cc3e6']], 'Crisp oxford cotton shirt for work and weekends.'],
            ['Men', 'Denim Button-Up Shirt', 'Northline', 19500, 23000, ['1596755094514-f87e34085b2c'], false, ['sizes' => $sizes], 'Soft chambray shirt with a subtle print.'],
            ['Men', 'Classic Bomber Jacket', 'Northline', 36000, null, ['1591047139829-d91aecb6caea'], true, ['sizes' => $sizes, 'colours' => ['Rust' => '#b5653d']], 'Lightweight bomber with ribbed cuffs, ideal for cool evenings.'],
            ['Men', 'Straight Leg Jeans', 'Northline', 22000, null, ['1542272604-787c3835535d'], false, ['sizes' => ['30', '32', '34', '36'], 'colours' => ['Indigo' => '#2b3a67']], 'Mid-rise straight jeans in sturdy stretch denim.'],
            ['Shoes', 'Everyday Leather Sneakers', 'Stride', 39000, 45000, ['1549298916-b41d501d3772', '1600185365926-3a2ce3cdb9eb'], true, ['sizes' => $shoeSizes], 'Comfortable low-top sneakers in tan leather with cushioned soles.'],
            ['Shoes', 'Pastel Court Sneakers', 'Stride', 34000, null, ['1595950653106-6c9ebd614d3a'], false, ['sizes' => ['37', '38', '39', '40', '41']], 'Playful pastel colour-block sneakers for everyday wear.'],
            ['Shoes', 'Runner Sports Shoes', 'Stride', 42000, null, ['1542291026-7eec264c27ff'], false, ['sizes' => $shoeSizes, 'colours' => ['Red' => '#c81e1e']], 'Light, breathable running shoes with a springy sole.'],
            ['Shoes', 'Printed Stiletto Heels', 'Stride', 28000, 33000, ['1543163521-1bf539c55dd2'], false, ['sizes' => ['37', '38', '39', '40', '41']], 'Statement heels with a bold print for special occasions.'],
            ['Bags & Accessories', 'Structured Top Handle Bag', 'Kimdern Atelier', 48000, 55000, ['1584917865442-de89df76afd3'], true, ['colours' => ['Red' => '#c0392b', 'Teal' => '#1f6f78']], 'Polished top handle bag with a detachable shoulder strap.'],
            ['Bags & Accessories', 'Woven Summer Bag', 'Kimdern Atelier', 26000, null, ['1590874103328-eac38a683ce7'], false, null, 'Hand-woven bag with leather trims, perfect for weekends.'],
            ['Bags & Accessories', 'Mini Chain Clutch', 'Kimdern Atelier', 19500, null, ['1566150905458-1bf1fc113f0d'], false, null, 'A slim pastel clutch with a gold chain strap.'],
            ['Bags & Accessories', 'Steel Chronograph Watch', 'Kimdern Atelier', 65000, 75000, ['1523170335258-f5ed11844a49'], false, null, 'Water-resistant stainless steel watch with a deep blue dial.'],
            ['Bags & Accessories', 'Round Metal Sunglasses', 'Kimdern Atelier', 12500, null, ['1511499767150-a48a237f0083'], false, null, 'UV400 lenses in a light gold metal frame.'],
        ];

        foreach ($products as $i => [$categoryName, $name, $brand, $price, $compare, $photos, $featured, $variants, $short]) {
            $product = Product::query()->updateOrCreate(['slug' => Str::slug($name)], [
                'category_id' => $categories[$categoryName]->id,
                'name' => $name,
                'sku' => 'KDS-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'brand' => $brand,
                'short_description' => $short,
                'description' => $this->description($name, $short, $categoryName),
                'price' => $price * 100,
                'compare_at_price' => $compare ? $compare * 100 : null,
                'stock' => $variants ? 0 : [24, 18, 6, 40, 3, 15][$i % 6],
                'is_active' => true,
                'is_featured' => $featured,
            ]);

            $product->forceFill(['sales_count' => (37 * ($i + 3)) % 90, 'created_at' => now()->subDays(($i * 11) % 120)])->saveQuietly();

            $product->images()->delete();
            foreach ($photos as $order => $photo) {
                $product->images()->create(['path' => $this->photo($photo), 'alt' => $name, 'sort_order' => $order]);
            }

            $product->variants()->delete();
            foreach ($this->variantRows($variants, $i) as $row) {
                $product->variants()->create($row);
            }
        }

        $this->seedReviews();
    }

    /**
     * @param  array<string, mixed>|null  $variants
     * @return list<array<string, mixed>>
     */
    private function variantRows(?array $variants, int $seed): array
    {
        if (! $variants) {
            return [];
        }

        $sizes = $variants['sizes'] ?? [null];
        $colours = $variants['colours'] ?? [null => null];
        $rows = [];

        foreach ($sizes as $sizeKey => $size) {
            $price = null;

            if (is_string($sizeKey)) {
                [$size, $price] = [$sizeKey, $size * 100];
            }

            foreach ($colours as $colour => $hex) {
                $rows[] = [
                    'size' => $size,
                    'colour' => $colour === '' ? null : $colour,
                    'colour_hex' => $hex,
                    'price' => $price,
                    'stock' => [8, 12, 0, 5, 3, 10, 7][($seed + count($rows)) % 7],
                ];
            }
        }

        return $rows;
    }

    private function description(string $name, string $short, string $category): string
    {
        return "{$short}\n\n{$name} is part of our hand-picked {$category} range. Every item at Kimdern Store is 100% genuine and checked before it leaves our Lagos warehouse.\n\n- Fast delivery to all 36 states and Abuja\n- Pay securely with card, bank transfer or USSD\n- Easy returns within 7 days if something is not right";
    }

    private function photo(string $id, int $width = 1200): string
    {
        return "https://images.unsplash.com/photo-{$id}?w={$width}&q=80&auto=format&fit=crop";
    }

    private function seedZones(): void
    {
        $zones = [
            ['Lagos', ['Lagos'], 250000, 5000000, '1–2 working days'],
            ['South West', ['Ogun', 'Oyo', 'Osun', 'Ondo', 'Ekiti'], 350000, 10000000, '2–3 working days'],
            ['South East & South South', ['Abia', 'Anambra', 'Ebonyi', 'Enugu', 'Imo', 'Akwa Ibom', 'Bayelsa', 'Cross River', 'Delta', 'Edo', 'Rivers'], 450000, 10000000, '3–5 working days'],
        ];

        $covered = collect($zones)->pluck(1)->flatten()->all();
        $zones[] = ['North & Abuja', array_values(array_diff(DeliveryZone::STATES, $covered)), 550000, null, '3–6 working days'];

        foreach ($zones as $i => [$name, $states, $fee, $freeOver, $eta]) {
            DeliveryZone::query()->updateOrCreate(['name' => $name], [
                'states' => $states, 'fee' => $fee, 'free_over' => $freeOver, 'eta' => $eta, 'is_active' => true, 'sort_order' => $i,
            ]);
        }
    }

    private function seedCoupons(): void
    {
        Coupon::query()->updateOrCreate(['code' => 'WELCOME10'], [
            'description' => '10% off your first order', 'type' => 'percent', 'value' => 10, 'min_subtotal' => 0, 'is_active' => true,
        ]);

        Coupon::query()->updateOrCreate(['code' => 'GLOW5K'], [
            'description' => '₦5,000 off orders above ₦40,000', 'type' => 'fixed', 'value' => 500000, 'min_subtotal' => 4000000, 'is_active' => true,
        ]);
    }

    private function seedBanners(): void
    {
        $banners = [
            ['New season', 'Glow up your everyday routine', 'Skincare and makeup loved by thousands of Nigerian women, delivered to your door.', '1612817288484-6f916006741a', 'Shop beauty', '/category/skincare'],
            ['Fresh drops', 'Style that turns heads', 'Dresses, sneakers and bags picked for Lagos weekends and owambe Saturdays.', '1483985988355-763728e1935b', 'Shop fashion', '/shop?department=fashion'],
            ['Signature scents', 'Find your new favourite perfume', 'Long-lasting fragrances for him and her, from ₦35,000.', '1594035910387-fea47794261f', 'Shop fragrance', '/category/fragrance'],
        ];

        foreach ($banners as $i => [$eyebrow, $title, $subtitle, $photo, $button, $link]) {
            Banner::query()->updateOrCreate(['title' => $title], [
                'eyebrow' => $eyebrow, 'subtitle' => $subtitle, 'image' => $this->photo($photo, 1800), 'button_text' => $button, 'link' => $link, 'sort_order' => $i, 'is_active' => true,
            ]);
        }
    }

    private function seedReviews(): void
    {
        $reviews = [
            ['vitamin-c-brightening-serum', 'Adaeze O.', 'Enugu', 5, 'My dark spots are fading!', 'Three weeks in and my skin is so much brighter. Delivery to Enugu took just 3 days.'],
            ['vitamin-c-brightening-serum', 'Funmi A.', 'Lagos', 4, 'Really good serum', 'Absorbs fast and does not sting. I just wish the bottle was bigger.'],
            ['daily-moisturising-cream', 'Chiamaka E.', 'Port Harcourt', 5, 'Best moisturiser for harmattan', 'My skin stays soft all day even in this dry weather. Will buy again.'],
            ['velvet-matte-lipstick', 'Tolu B.', 'Ibadan', 5, 'The Ruby Red is everything', 'Lasted through a whole wedding, eating and all. Packaging is lovely too.'],
            ['golden-hour-eyeshadow-palette', 'Halima S.', 'Abuja', 5, 'So pigmented', 'The shades are perfect for dark skin. Blends like butter.'],
            ['rose-noir-eau-de-parfum', 'Ngozi K.', 'Lagos', 5, 'I get compliments everywhere', 'Smells expensive and lasts from morning till night. Original product, very happy.'],
            ['midnight-blue-for-men', 'Emeka U.', 'Owerri', 4, 'Fresh and classy', 'My go-to office scent now. Fast delivery and well packaged.'],
            ['flowing-maxi-dress', 'Blessing I.', 'Benin City', 5, 'Fits like a dream', 'I ordered M and it fits perfectly. The fabric is soft and flows beautifully.'],
            ['essential-cotton-t-shirt', 'Tunde F.', 'Lagos', 5, 'Quality tee', 'Thick cotton, did not shrink after washing. Bought 3 more.'],
            ['everyday-leather-sneakers', 'Kelechi N.', 'Aba', 4, 'Very comfortable', 'Size 42 fits true. Good leather and very comfy for long days.'],
            ['structured-top-handle-bag', 'Amaka J.', 'Asaba', 5, 'Classy bag', 'Looks even better in person. Customer service was quick to answer on WhatsApp.'],
            ['moisture-shampoo-conditioner', 'Zainab M.', 'Kano', 5, 'My curls love it', 'Leaves my natural hair soft and easy to detangle. No more breakage.'],
        ];

        foreach ($reviews as $i => [$slug, $name, $location, $rating, $title, $body]) {
            $product = Product::query()->where('slug', $slug)->first();

            if (! $product) {
                continue;
            }

            Review::query()->updateOrCreate(['product_id' => $product->id, 'name' => $name], [
                'location' => $location, 'rating' => $rating, 'title' => $title, 'body' => $body,
                'is_verified_buyer' => true, 'is_approved' => true, 'created_at' => now()->subDays($i * 3 + 2),
            ]);
        }
    }
}
