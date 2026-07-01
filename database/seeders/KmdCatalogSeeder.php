<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KmdCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $categories = $this->seedCategories();
            $brands = $this->seedBrands();
            $this->seedProducts($categories, $brands);
            $this->seedServices();
            $this->seedProjects();
        });
    }

    private function seedCategories(): array
    {
        $categories = [];

        foreach ([
            'Gypsum Board',
            'Eco Block Ceiling Board',
            'Cline & Partition Frame',
            'Sanitary Ware',
            'Decor Materials',
            'Smart Home',
            'Furniture Decor',
            'Wall Systems',
            'Installation Tools',
        ] as $index => $name) {
            $category = Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'type' => 'product',
                    'sort_order' => $index,
                    'is_active' => true,
                    'is_featured' => $index < 6,
                ],
            );

            $categories[$name] = $category;
        }

        return $categories;
    }

    private function seedBrands(): array
    {
        $brands = [];

        foreach ([
            ['name' => 'Zeit', 'country_of_origin' => 'Cambodia'],
            ['name' => 'ISI Steel', 'country_of_origin' => 'Cambodia'],
            ['name' => 'Multi-brand'],
            ['name' => 'Arrow'],
            ['name' => 'KMD Smart'],
            ['name' => 'KMD Supply'],
            ['name' => 'An Cuong'],
            ['name' => 'KMD'],
        ] as $index => $data) {
            $brand = Brand::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'country_of_origin' => $data['country_of_origin'] ?? null,
                    'sort_order' => $index,
                    'is_active' => true,
                    'is_featured' => $index < 4,
                ],
            );

            $brands[$data['name']] = $brand;
        }

        return $brands;
    }

    private function seedProducts(array $categories, array $brands): void
    {
        foreach ($this->products() as $index => $data) {
            $product = Product::updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'category_id' => $categories[$data['category']]->id,
                    'brand_id' => $brands[$data['brand']]->id,
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'short_description' => $data['description'],
                    'description' => $this->buildProductDescription($data),
                    'customer_goal' => $data['customer_goal'],
                    'features' => $data['features'],
                    'applications' => $data['applications'],
                    'material_notes' => $data['notes'],
                    'lead_time' => $data['lead_time'],
                    'delivery_note' => $data['delivery'],
                    'compatible_product_slugs' => $data['compatible_product_slugs'] ?? $this->compatibleProductSlugs($data['slug']),
                    'specifications' => $data['specifications'],
                    'tags' => $data['tags'],
                    'price' => $data['price'],
                    'compare_price' => $data['compare_price'] ?? null,
                    'currency' => 'USD',
                    'unit' => $data['unit'],
                    'min_order_qty' => $data['min_order_qty'],
                    'stock_qty' => $data['stock_qty'],
                    'allow_backorder' => $data['allow_backorder'],
                    'requires_installation' => $data['requires_installation'],
                    'avg_rating' => $data['rating'],
                    'review_count' => $data['review_count'],
                    'is_featured' => $index < 6,
                    'is_new' => ($data['badge'] ?? null) === 'New',
                    'is_best_seller' => ($data['badge'] ?? null) === 'Best seller',
                    'sort_order' => $index,
                    'status' => 'published',
                    'published_at' => now()->subDay(),
                ],
            );

            $product->images()->delete();
            foreach ($data['images'] as $imageIndex => $imageUrl) {
                $product->images()->create([
                    'image_url' => $imageUrl,
                    'alt_text' => "{$data['name']} image ".($imageIndex + 1),
                    'is_primary' => $imageIndex === 0,
                    'sort_order' => $imageIndex,
                ]);
            }
        }
    }

    private function seedServices(): void
    {
        foreach ([
            [
                'name' => 'Finished Ceiling Decor',
                'slug' => 'ceiling',
                'short_description' => 'Stretch, reflective, and feature ceilings for residential and commercial interiors.',
                'image_url' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'name' => 'Partition and Wall Decor',
                'slug' => 'partition',
                'short_description' => 'Wall systems and partition solutions designed for durability, acoustics, and finish quality.',
                'image_url' => 'https://images.unsplash.com/photo-1484154218962-a197022b5858?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'name' => 'Furniture Decor',
                'slug' => 'furniture',
                'short_description' => 'Built-in counters, cabinets, shelving, and finish carpentry for project-ready spaces.',
                'image_url' => 'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'name' => 'Smart Home Control',
                'slug' => 'smart-home',
                'short_description' => 'Integrated controls for lighting, locks, and convenience features in modern interiors.',
                'image_url' => 'https://images.unsplash.com/photo-1558002038-1055907df827?auto=format&fit=crop&w=1200&q=80',
            ],
        ] as $index => $data) {
            Service::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'short_description' => $data['short_description'],
                    'description' => '<p>'.$data['short_description'].'</p>',
                    'inquiry_type' => 'quote',
                    'image_url' => $data['image_url'],
                    'sort_order' => $index,
                    'is_active' => true,
                    'is_featured' => true,
                ],
            );
        }
    }

    private function seedProjects(): void
    {
        $serviceCeiling = Service::where('slug', 'ceiling')->first();
        $servicePartition = Service::where('slug', 'partition')->first();
        $serviceFurniture = Service::where('slug', 'furniture')->first();
        $serviceSmartHome = Service::where('slug', 'smart-home')->first();

        $productGypsum = Product::where('slug', 'gypsum-board')->first();
        $productCline = Product::where('slug', 'cline-4m')->first();
        $productEcoBlock = Product::where('slug', 'eco-block-ceiling-board')->first();
        $productPartition = Product::where('slug', 'partition-frame-stick')->first();
        $productAcoustic = Product::where('slug', 'acoustic-board')->first();
        $productSmartLock = Product::where('slug', 'smart-lock')->first();
        $productCabinet = Product::where('slug', 'cabinet-board')->first();
        $productDecor = Product::where('slug', 'decor-board')->first();

        $projects = [
            [
                'title' => 'Private Residence Ceiling Upgrade',
                'slug' => 'residential-suite',
                'overview' => 'A residential ceiling direction built around warm finishes, clean perimeter lines, and integrated lighting. The reference shows how ceiling materials and lighting details can work together without making the living area feel visually heavy.',
                'setting' => 'Residential interior',
                'focus' => 'Ceiling and lighting',
                'goal' => 'Create a warmer, more refined living area with a ceiling design that supports both ambient and feature lighting.',
                'challenge' => 'The ceiling needed enough visual detail to define the room while maintaining a calm, open feeling across the main living space.',
                'response' => 'A restrained ceiling composition, coordinated edge profiles, and warm lighting zones create a clear focal point while preserving visual continuity.',
                'scope' => ['Ceiling layout direction', 'Board and perimeter profile planning', 'Lighting detail coordination', 'Finish and material alignment'],
                'outcomes' => ['Cleaner ceiling lines', 'Warmer room atmosphere', 'Coordinated lighting zones'],
                'process' => [
                    ['title' => 'Read the room', 'copy' => 'Review room proportions, existing light, furniture placement, and the intended visual mood.'],
                    ['title' => 'Define the ceiling', 'copy' => 'Set the ceiling lines, feature areas, lighting positions, and suitable board system.'],
                    ['title' => 'Coordinate the finish', 'copy' => 'Align profiles, accessories, lighting details, and installation requirements.'],
                ],
                'services' => $serviceCeiling ? [$serviceCeiling->id] : [],
                'products' => array_filter([$productGypsum?->id, $productCline?->id, $productEcoBlock?->id]),
            ],
            [
                'title' => 'Commercial Workspace Fit-Out',
                'slug' => 'workspace-fitout',
                'overview' => 'A commercial workspace direction using partitions, acoustic considerations, and restrained finishes to create focus without isolating the team. The reference connects room planning with practical wall systems and material coordination.',
                'setting' => 'Commercial workspace',
                'focus' => 'Partitions and acoustics',
                'goal' => 'Create focused work zones and meeting areas while keeping the office connected, bright, and easy to navigate.',
                'challenge' => 'The workspace needed clearer separation for concentration and meetings without losing daylight or creating a closed, fragmented environment.',
                'response' => 'Partition placement, acoustic board options, and consistent finish choices define functional zones while maintaining a coherent visual rhythm.',
                'scope' => ['Workspace zoning', 'Partition system planning', 'Acoustic material direction', 'Wall and finish coordination'],
                'outcomes' => ['Clearer work zones', 'Improved privacy planning', 'Consistent workspace finishes'],
                'process' => [
                    ['title' => 'Map the workflow', 'copy' => 'Identify focus areas, meeting spaces, circulation routes, and shared team zones.'],
                    ['title' => 'Plan the partitions', 'copy' => 'Define wall positions, heights, acoustic needs, and suitable frame and board systems.'],
                    ['title' => 'Unify the interior', 'copy' => 'Coordinate wall finishes, lighting, furniture direction, and service access.'],
                ],
                'services' => array_filter([$servicePartition?->id, $serviceFurniture?->id]),
                'products' => array_filter([$productPartition?->id, $productAcoustic?->id, $productGypsum?->id]),
            ],
            [
                'title' => 'Smart Living Upgrade',
                'slug' => 'smart-home',
                'overview' => 'A smart-living direction focused on useful control points, compatible door access, and technology that remains visually quiet. The reference prioritizes daily convenience and finish coordination over unnecessary complexity.',
                'setting' => 'Modern residence',
                'focus' => 'Access and controls',
                'goal' => 'Introduce practical smart access and control features without disrupting the interior design or everyday routines.',
                'challenge' => 'Smart products needed to fit existing doors, finishes, and user habits rather than becoming disconnected devices added after the interior work.',
                'response' => 'Compatibility checks, restrained control placement, and coordinated hardware finishes create a simpler upgrade path for the home.',
                'scope' => ['Smart access review', 'Door and hardware compatibility', 'Control-point planning', 'Finish coordination'],
                'outcomes' => ['Simpler daily access', 'Cleaner device integration', 'Better hardware compatibility'],
                'process' => [
                    ['title' => 'Understand the routine', 'copy' => 'Identify who uses the space, how access works, and which controls add practical value.'],
                    ['title' => 'Check compatibility', 'copy' => 'Review door type, lock dimensions, power needs, connectivity, and installation conditions.'],
                    ['title' => 'Integrate the details', 'copy' => 'Coordinate product selection, placement, finish, and installation requirements.'],
                ],
                'services' => array_filter([$serviceSmartHome?->id, $serviceFurniture?->id]),
                'products' => array_filter([$productSmartLock?->id, $productCabinet?->id, $productDecor?->id]),
            ],
        ];

        foreach ($projects as $index => $data) {
            $products = $data['products'];
            $services = $data['services'];
            unset($data['products'], $data['services']);

            $project = Project::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'sort_order' => $index,
                    'is_featured' => true,
                    'status' => 'published',
                    'published_at' => now()->subDay(),
                ]),
            );

            $project->services()->sync($services);
            $project->products()->sync($products);
        }
    }

    private function buildProductDescription(array $data): string
    {
        $parts = array_filter([
            '<p>'.$data['description'].'</p>',
            '<p><strong>Customer goal:</strong> '.$data['customer_goal'].'</p>',
            '<p><strong>Key features:</strong> '.implode(', ', $data['features']).'</p>',
            '<p><strong>Applications:</strong> '.implode(', ', $data['applications']).'</p>',
            '<p><strong>Material notes:</strong> '.implode(', ', $data['notes']).'</p>',
            '<p><strong>Lead time:</strong> '.$data['lead_time'].'</p>',
            '<p><strong>Delivery:</strong> '.$data['delivery'].'</p>',
        ]);

        return implode("\n", $parts);
    }

    private function compatibleProductSlugs(string $slug): array
    {
        return match ($slug) {
            'gypsum-board' => ['partition-frame-stick', 'cline-4m', 'installation-kit'],
            'partition-frame-stick' => ['gypsum-board', 'acoustic-board', 'installation-kit'],
            'cline-4m' => ['gypsum-board', 'eco-block-ceiling-board', 'installation-kit'],
            'decor-board' => ['cabinet-board', 'smart-lock'],
            'sanitary' => ['installation-kit'],
            'smart-lock' => ['cabinet-board', 'decor-board'],
            'acoustic-board' => ['partition-frame-stick', 'gypsum-board'],
            'eco-block-ceiling-board' => ['cline-4m', 'gypsum-board', 'installation-kit'],
            'cabinet-board' => ['decor-board', 'smart-lock'],
            'installation-kit' => ['gypsum-board', 'cline-4m', 'partition-frame-stick'],
            default => [],
        };
    }

    private function products(): array
    {
        return [
            [
                'slug' => 'gypsum-board',
                'name' => 'Gypsum Board (Zeit) STD Size',
                'description' => 'A smooth, practical gypsum board for interior ceilings and partition walls. Suitable for homes, offices, and renovation projects when you need a clean surface that is easy to frame, finish, and paint.',
                'brand' => 'Zeit',
                'category' => 'Gypsum Board',
                'sku' => 'ZTG-STD-1220',
                'price' => 8.50,
                'compare_price' => 10,
                'unit' => 'sheet',
                'min_order_qty' => 10,
                'stock_qty' => 120,
                'allow_backorder' => false,
                'requires_installation' => false,
                'rating' => 4.8,
                'review_count' => 28,
                'badge' => 'Best seller',
                'specifications' => ['1220mm board', 'Interior ceiling', 'Standard finish'],
                'tags' => ['gypsum', 'ceiling', 'partition'],
                'customer_goal' => 'Cover ceiling or partition surfaces with a standard interior board that is easy to plan and order.',
                'features' => ['Standard Zeit board size', 'Smooth interior finish', 'Suitable for ceiling and partition work', 'Works with frame and fixing accessories'],
                'applications' => ['Ceiling lining', 'Partition wall surface', 'Residential renovation'],
                'notes' => ['Standard board size for common ceiling work', 'Best ordered with framing and fixing accessories', 'Confirm moisture or fire rating before wet-area use'],
                'lead_time' => 'Ready stock',
                'delivery' => 'Delivery available',
                'images' => ['/products/gypsum_board.webp', '/products/gypsum_board_ziet_brand.webp'],
            ],
            [
                'slug' => 'partition-frame-stick',
                'name' => 'Partition Frame Stick 4M',
                'description' => 'A 4-meter steel profile used to create the supporting structure for gypsum and acoustic partition walls. Designed for straight, stable framing in residential rooms, offices, and commercial fit-outs.',
                'brand' => 'ISI Steel',
                'category' => 'Cline & Partition Frame',
                'sku' => 'ISI-PF-4M',
                'price' => 3.20,
                'unit' => 'stick',
                'min_order_qty' => 20,
                'stock_qty' => 200,
                'allow_backorder' => false,
                'requires_installation' => false,
                'rating' => 4.7,
                'review_count' => 19,
                'badge' => 'Contractor pick',
                'specifications' => ['4m length', 'Partition frame', 'Steel profile'],
                'tags' => ['partition', 'frame', 'steel'],
                'customer_goal' => 'Build partition wall structure before installing boards or wall finishes.',
                'features' => ['4M stick length', 'Steel profile support', 'Suitable for partition framing', 'Useful for contractor bulk orders'],
                'applications' => ['Partition framing', 'Room division', 'Commercial fit-out'],
                'notes' => ['Four-meter profile for project installation', 'Pair with wall board and fixing kit', 'Delivery planning recommended for larger quantities'],
                'lead_time' => 'Ready stock',
                'delivery' => 'Truck delivery',
                'images' => ['/products/partition.webp'],
            ],
            [
                'slug' => 'cline-4m',
                'name' => 'Cline 4M',
                'description' => 'A 4-meter ceiling profile that helps form straight perimeter lines and neat edge details. Use it with ceiling boards and framing accessories to achieve a cleaner, more consistent finish.',
                'brand' => 'Multi-brand',
                'category' => 'Cline & Partition Frame',
                'sku' => 'KMD-CLN-4M',
                'price' => 2.80,
                'unit' => 'stick',
                'min_order_qty' => 20,
                'stock_qty' => 160,
                'allow_backorder' => false,
                'requires_installation' => false,
                'rating' => 4.5,
                'review_count' => 13,
                'badge' => 'Ceiling line',
                'specifications' => ['4m length', 'Ceiling line', 'Edge finishing'],
                'tags' => ['ceiling', 'cline', 'frame'],
                'customer_goal' => 'Create a clean ceiling edge or line detail before finishing ceiling work.',
                'features' => ['4M cline profile', 'Useful for ceiling perimeter', 'Supports cleaner ceiling finish', 'Pairs with ceiling board systems'],
                'applications' => ['Ceiling edge line', 'Ceiling perimeter finish', 'Interior ceiling detail'],
                'notes' => ['Confirm ceiling design before ordering', 'Best paired with ceiling board and install accessories', 'Bulk delivery recommended for project quantities'],
                'lead_time' => 'Ready stock',
                'delivery' => 'Truck delivery',
                'images' => ['/products/cline.webp', '/products/cline_detail.webp'],
            ],
            [
                'slug' => 'decor-board',
                'name' => 'Decor Materials Board',
                'description' => 'A flexible selection of MDF, WPC, and plywood panels for cabinets, counters, shelving, and feature walls. Choose the material, thickness, color, and finish according to the room style and moisture conditions.',
                'brand' => 'Multi-brand',
                'category' => 'Decor Materials',
                'sku' => 'KMD-BOARD-MIX',
                'price' => 18,
                'unit' => 'panel',
                'min_order_qty' => 5,
                'stock_qty' => 0,
                'allow_backorder' => true,
                'requires_installation' => false,
                'rating' => 4.6,
                'review_count' => 12,
                'badge' => 'Multi-brand',
                'specifications' => ['MDF/WPC/Plywood', 'Furniture grade', 'Panel material'],
                'tags' => ['decor', 'board', 'furniture'],
                'customer_goal' => 'Select decorative board materials for furniture, wall features, counters, and interior finish work.',
                'features' => ['Multi-material options', 'Furniture and wall finish use', 'Project quote recommended', 'Supports custom finish selection'],
                'applications' => ['Cabinet fabrication', 'Feature wall finish', 'Counter and shelving work'],
                'notes' => ['Material selection depends on finish and moisture exposure', 'Confirm thickness and surface color before quote', 'Best handled through project quote for mixed materials'],
                'lead_time' => '3-7 days',
                'delivery' => 'Quote delivery',
                'images' => ['/products/wood.webp'],
            ],
            [
                'slug' => 'sanitary',
                'name' => 'Sanitary Ware',
                'description' => 'A coordinated range of bathroom fixtures for new construction, renovation, and replacement work. Select individual pieces or request a matching set based on your bathroom layout, preferred style, and available stock.',
                'brand' => 'Arrow',
                'category' => 'Sanitary Ware',
                'sku' => 'ARW-SAN-COL',
                'price' => 42,
                'compare_price' => 48,
                'unit' => 'piece',
                'min_order_qty' => 1,
                'stock_qty' => 4,
                'allow_backorder' => false,
                'requires_installation' => true,
                'rating' => 4.9,
                'review_count' => 31,
                'badge' => 'Premium',
                'specifications' => ['Bathroom fixture', 'Arrow brand', 'Project supply'],
                'tags' => ['sanitary', 'bathroom', 'fixture'],
                'customer_goal' => 'Choose bathroom fixtures for renovation, replacement, or project supply with stock confirmation.',
                'features' => ['Arrow brand fixture options', 'Suitable for bathroom upgrades', 'Low-stock confirmation', 'Quote support for matching sets'],
                'applications' => ['Bathroom renovation', 'Hotel and apartment supply', 'Fixture replacement'],
                'notes' => ['Confirm model, color, and matching accessories', 'Low stock items should be checked before checkout', 'Project buyers can request matching fixture sets'],
                'lead_time' => 'Check stock',
                'delivery' => 'Delivery available',
                'images' => ['/products/sanitery_ware.webp'],
            ],
            [
                'slug' => 'smart-lock',
                'name' => 'Smart Lock Set',
                'description' => 'A modern door-locking solution that adds convenient, keyless access to homes, rental units, and small offices. Door type and dimensions should be confirmed before ordering to ensure correct installation.',
                'brand' => 'KMD Smart',
                'category' => 'Smart Home',
                'sku' => 'KMD-SL-100',
                'price' => 95,
                'compare_price' => 115,
                'unit' => 'set',
                'min_order_qty' => 1,
                'stock_qty' => 25,
                'allow_backorder' => false,
                'requires_installation' => true,
                'rating' => 4.5,
                'review_count' => 16,
                'badge' => 'New',
                'specifications' => ['Door access', 'Smart control', 'Set package'],
                'tags' => ['smart home', 'lock', 'door'],
                'customer_goal' => 'Upgrade door access with a smart lock set for homes, rental units, or small offices.',
                'features' => ['Smart access control', 'Set package', 'Home and office use', 'Compatibility check recommended'],
                'applications' => ['Home entrance upgrade', 'Rental unit access', 'Small office door control'],
                'notes' => ['Confirm door type and lock compatibility', 'Installation advice recommended before purchase', 'Useful for smart home starter packages'],
                'lead_time' => 'Ready stock',
                'delivery' => 'Delivery available',
                'images' => ['/products/smart_lock_door.webp'],
            ],
            [
                'slug' => 'acoustic-board',
                'name' => 'Acoustic Smart Board',
                'description' => 'An acoustic wall panel for partitions and interior spaces where improved sound control is important. A practical choice for bedrooms, meeting rooms, offices, and other areas that need a clean finish with reduced noise transfer.',
                'brand' => 'Multi-brand',
                'category' => 'Wall Systems',
                'sku' => 'KMD-ACB-2440',
                'price' => 24,
                'unit' => 'panel',
                'min_order_qty' => 10,
                'stock_qty' => 0,
                'allow_backorder' => true,
                'requires_installation' => true,
                'rating' => 4.4,
                'review_count' => 9,
                'specifications' => ['Sound control', 'Wall system', 'Preorder'],
                'tags' => ['acoustic', 'wall', 'partition'],
                'customer_goal' => 'Improve room sound control while keeping a clean wall or partition finish.',
                'features' => ['Sound-control board', 'Partition and wall use', 'Preorder planning', 'Best quoted with framing'],
                'applications' => ['Meeting room partition', 'Bedroom sound control', 'Office acoustic upgrade'],
                'notes' => ['Preorder timing depends on selected board type', 'Confirm acoustic target and wall system', 'Best quoted with framing and installation scope'],
                'lead_time' => '7-14 days',
                'delivery' => 'Quote delivery',
                'images' => ['/products/wood.webp'],
            ],
            [
                'slug' => 'eco-block-ceiling-board',
                'name' => 'Eco Block Ceiling Board',
                'description' => 'A decorative ceiling board for eco-block patterns, reflected ceiling details, and integrated LED designs. Best suited to feature ceilings where the final layout, lighting, framing, and installation need to be planned together.',
                'brand' => 'KMD Supply',
                'category' => 'Eco Block Ceiling Board',
                'sku' => 'KMD-ECO-CL',
                'price' => 16,
                'unit' => 'board',
                'min_order_qty' => 5,
                'stock_qty' => 0,
                'allow_backorder' => true,
                'requires_installation' => true,
                'rating' => 4.5,
                'review_count' => 11,
                'badge' => 'Service fit',
                'specifications' => ['Eco block board', 'Ceiling decor', 'LED-ready option'],
                'tags' => ['ceiling', 'eco block', 'decor'],
                'customer_goal' => 'Create decorative ceiling features such as reflect, eco-block, or LED ceiling layouts.',
                'features' => ['Eco block ceiling use', 'LED-ready option', 'Decor ceiling fit', 'Quote recommended for design scope'],
                'applications' => ['Reflect ceiling work', 'LED ceiling decor', 'Interior feature ceiling'],
                'notes' => ['Confirm ceiling design before quote', 'Best paired with KMD ceiling service', 'Delivery and installation scope should be checked'],
                'lead_time' => '5-10 days',
                'delivery' => 'Quote delivery',
                'images' => ['/products/cline_detail.webp'],
            ],
            [
                'slug' => 'cabinet-board',
                'name' => 'Cabinet Decor Board',
                'description' => 'A furniture-grade decorative panel for cabinet doors, shelving, counters, and custom built-ins. Confirm the thickness, surface color, edge finish, and available quantity before starting fabrication.',
                'brand' => 'An Cuong',
                'category' => 'Furniture Decor',
                'sku' => 'ACG-CAB-18',
                'price' => 32,
                'unit' => 'panel',
                'min_order_qty' => 3,
                'stock_qty' => 5,
                'allow_backorder' => false,
                'requires_installation' => false,
                'rating' => 4.7,
                'review_count' => 22,
                'badge' => 'Project grade',
                'specifications' => ['18mm panel', 'Cabinet finish', 'Furniture decor'],
                'tags' => ['cabinet', 'board', 'furniture'],
                'customer_goal' => 'Prepare furniture-grade panels for cabinet, shelving, counter, or built-in interior work.',
                'features' => ['18mm panel option', 'Furniture finish use', 'Built-in project fit', 'Stock check recommended'],
                'applications' => ['Cabinet doors', 'Built-in shelving', 'Furniture finish panels'],
                'notes' => ['Confirm finish color, edge treatment, and thickness', 'Low stock should be verified before production planning', 'Useful for built-in furniture packages'],
                'lead_time' => 'Check stock',
                'delivery' => 'Delivery available',
                'images' => ['/products/wood.webp'],
            ],
            [
                'slug' => 'installation-kit',
                'name' => 'Ceiling Install Kit',
                'description' => 'A convenient set of basic fixing accessories for ceiling installation, repair, and small renovation work. Pair it with the selected board and frame system, and confirm the required quantity from the project area.',
                'brand' => 'KMD',
                'category' => 'Installation Tools',
                'sku' => 'KMD-KIT-CL',
                'price' => 12,
                'unit' => 'kit',
                'min_order_qty' => 1,
                'stock_qty' => 40,
                'allow_backorder' => false,
                'requires_installation' => false,
                'rating' => 4.3,
                'review_count' => 14,
                'specifications' => ['Fixing kit', 'Ceiling install', 'Accessory set'],
                'tags' => ['installation', 'ceiling', 'kit'],
                'customer_goal' => 'Prepare basic fixing accessories for small ceiling jobs, repairs, or installation support.',
                'features' => ['Accessory starter kit', 'Ceiling installation support', 'Pickup or delivery', 'Pairs with board and frame'],
                'applications' => ['Ceiling repair', 'Small installation work', 'Accessory preparation'],
                'notes' => ['Best paired with gypsum board, cline, or partition frame', 'Confirm required quantity by room size', 'Useful as a starter kit for small jobs'],
                'lead_time' => 'Ready stock',
                'delivery' => 'Pickup or delivery',
                'images' => ['/products/gypsum_board_cline.webp'],
            ],
        ];
    }
}
