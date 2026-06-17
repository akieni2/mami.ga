<?php

namespace Database\Seeders;

use App\Modules\Municipality\Models\EconomicOperatorCategory;
use Illuminate\Database\Seeder;

class EconomicOperatorCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'boutique', 'name' => 'Boutique', 'icon' => 'store'],
            ['slug' => 'restaurant', 'name' => 'Restaurant / Maquis', 'icon' => 'restaurant'],
            ['slug' => 'atelier', 'name' => 'Atelier', 'icon' => 'build'],
            ['slug' => 'salon', 'name' => 'Salon de coiffure / Beauté', 'icon' => 'content_cut'],
            ['slug' => 'pharmacie', 'name' => 'Pharmacie / Parapharmacie', 'icon' => 'local_pharmacy'],
            ['slug' => 'superette', 'name' => 'Superette / Épicerie', 'icon' => 'shopping_basket'],
            ['slug' => 'garage', 'name' => 'Garage / Mécanique', 'icon' => 'car_repair'],
            ['slug' => 'pme', 'name' => 'PME', 'icon' => 'business'],
            ['slug' => 'marche_plein_air', 'name' => 'Étal marché', 'icon' => 'storefront'],
            ['slug' => 'autre', 'name' => 'Autre activité', 'icon' => 'more_horiz'],
        ];

        foreach ($categories as $category) {
            EconomicOperatorCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }
    }
}
