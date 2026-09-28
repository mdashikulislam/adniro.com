<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Assign a relevant Font Awesome icon to every sub-category.
 *
 * Run with: php artisan db:seed --class=CategoryIconsSeeder
 *
 * Sub-categories are matched by slug. Any sub-category without a mapping
 * inherits its parent's icon, so every category ends up with an icon.
 * Existing icons are only replaced when they are empty, unless FORCE is true.
 */
class CategoryIconsSeeder extends Seeder
{
	/** Replace icons that are already set */
	private const FORCE = true;
	
	/** slug => icon class (must exist in the site's icon set) */
	private array $icons = [
		// Automobiles
		'cars'                                 => 'fa-solid fa-car',
		'buses-and-minibus'                    => 'fa-solid fa-bus',
		'heavy-equipment'                      => 'fa-solid fa-tractor',
		'motorcycles-and-scooters'             => 'fa-solid fa-motorcycle',
		'trucks-and-trailers'                  => 'fa-solid fa-truck',
		'car-parts-and-accessories'            => 'fa-solid fa-gears',
		'watercraft-and-boats'                 => 'fa-solid fa-ship',
		
		// Phones & Tablets
		'mobile-phones'                        => 'fa-solid fa-mobile-screen',
		'mobile-phones-tablets-accessories'    => 'fa-solid fa-headphones',
		'smart-watches-and-trackers'           => 'fa-solid fa-clock',
		'tablets'                              => 'fa-solid fa-tablet-screen-button',
		
		// Electronics
		'accessories-supplies-for-electronics' => 'fa-solid fa-plug',
		'laptops-and-computers'                => 'fa-solid fa-laptop',
		'tv-dvd-equipment'                     => 'fa-solid fa-tv',
		'audio-music-equipment'                => 'fa-solid fa-music',
		'computer-accessories'                 => 'fa-solid fa-keyboard',
		'computer-hardware'                    => 'fa-solid fa-microchip',
		'computer-monitors'                    => 'fa-solid fa-desktop',
		'headphones'                           => 'fa-solid fa-headphones',
		'networking-products'                  => 'fa-solid fa-network-wired',
		'photo-video-cameras'                  => 'fa-solid fa-camera',
		'printers-and-scanners'                => 'fa-solid fa-print',
		'security-and-surveillance'            => 'fa-solid fa-video',
		'software'                             => 'fa-solid fa-code',
		'video-games'                          => 'fa-solid fa-gamepad',
		'video-game-consoles'                  => 'fa-solid fa-gamepad',
		
		// Furniture & Appliances
		'furniture-tableware'                  => 'fa-solid fa-couch',
		'antiques-art-decoration'              => 'fa-solid fa-palette',
		'appliances'                           => 'fa-solid fa-blender',
		'garden'                               => 'fa-solid fa-seedling',
		'toys-games-figurines'                 => 'fa-solid fa-puzzle-piece',
		'wine-gourmet-recipes'                 => 'fa-solid fa-wine-glass',
		
		// Real estate
		'houses-apartments-for-rent'           => 'fa-solid fa-key',
		'houses-apartments-for-sale'           => 'fa-solid fa-house',
		'land-and-plots-for-rent'              => 'fa-solid fa-map',
		'land-and-plots-for-sale'              => 'fa-solid fa-map-location-dot',
		'commercial-property-for-rent'         => 'fa-solid fa-building',
		'commercial-properties'                => 'fa-solid fa-city',
		'event-centers-and-venues'             => 'fa-solid fa-champagne-glasses',
		'temporary-and-vacation-rentals'       => 'fa-solid fa-umbrella-beach',
		
		// Animals & Pets
		'birds'                                => 'fa-solid fa-dove',
		'cats-and-kittens'                     => 'fa-solid fa-cat',
		'dogs-and-puppies'                     => 'fa-solid fa-dog',
		'fish'                                 => 'fa-solid fa-fish',
		'pets-accessories'                     => 'fa-solid fa-bone',
		'reptiles'                             => 'fa-solid fa-dragon',
		'other-animals'                        => 'fa-solid fa-paw',
		
		// Fashion
		'bags'                                 => 'fa-solid fa-bag-shopping',
		'clothing'                             => 'fa-solid fa-shirt',
		'clothing-accessories'                 => 'fa-solid fa-glasses',
		'jewelry'                              => 'fa-solid fa-gem',
		'shoes'                                => 'fa-solid fa-shoe-prints',
		'watches'                              => 'fa-solid fa-clock',
		'wedding-wear-accessories'             => 'fa-solid fa-ring',
		
		// Beauty & Well being
		'bath-and-body'                        => 'fa-solid fa-bath',
		'fragrance'                            => 'fa-solid fa-spray-can-sparkles',
		'hair-beauty'                          => 'fa-solid fa-scissors',
		'makeup'                               => 'fa-solid fa-wand-magic-sparkles',
		'sexual-wellness'                      => 'fa-solid fa-heart',
		'care'                                 => 'fa-solid fa-hand-holding-heart',
		'tobacco-accessories'                  => 'fa-solid fa-smoking',
		'tools-and-accessories'                => 'fa-solid fa-toolbox',
		'vitamins-and-supplements'             => 'fa-solid fa-pills',
		'pro-massage'                          => 'fa-solid fa-spa',
		
		// Jobs
		'agriculture-environment'              => 'fa-solid fa-leaf',
		'assistantship-secretariat-helpdesk'   => 'fa-solid fa-headset',
		'automotive-mechanic'                  => 'fa-solid fa-wrench',
		'btp-construction-building'            => 'fa-solid fa-helmet-safety',
		'trade-business-services'              => 'fa-solid fa-briefcase',
		'commercial-sale-jobs'                 => 'fa-solid fa-handshake',
		'accounting-management-finance'        => 'fa-solid fa-calculator',
		'steering-manager'                     => 'fa-solid fa-user-tie',
		'aesthetics-hair-beauty'               => 'fa-solid fa-scissors',
		'public-service-jobs'                  => 'fa-solid fa-landmark',
		'real-estate-jobs'                     => 'fa-solid fa-city',
		'independent-freelance-telecommuting'  => 'fa-solid fa-house-laptop',
		'computers-internet-telecommunications' => 'fa-solid fa-network-wired',
		'industry-production-engineering'      => 'fa-solid fa-industry',
		'marketing-communication'              => 'fa-solid fa-bullhorn',
		'babysitting-nanny-work'               => 'fa-solid fa-baby',
		'hr-training-education'                => 'fa-solid fa-graduation-cap',
		'medical-healthcare-social'            => 'fa-solid fa-user-nurse',
		'security-guarding'                    => 'fa-solid fa-shield-halved',
		'household-services-housekeeping'      => 'fa-solid fa-broom',
		'tourism-hotels-restaurants-leisure'   => 'fa-solid fa-utensils',
		'transportation-logistics'             => 'fa-solid fa-truck-fast',
		'others-jobs-offer'                    => 'fa-solid fa-user-tie',
		
		// Services
		'casting-model-photographer'           => 'fa-solid fa-clapperboard',
		'carpooling'                           => 'fa-solid fa-car-side',
		'moving-furniture-guard'               => 'fa-solid fa-people-carry-box',
		'destocking-commercial'                => 'fa-solid fa-boxes-stacked',
		'industrial-equipment'                 => 'fa-solid fa-warehouse',
		'aesthetics-hairstyling'               => 'fa-solid fa-scissors',
		'materials-and-equipment-pro'          => 'fa-solid fa-screwdriver-wrench',
		'event-organization-services'          => 'fa-solid fa-calendar-check',
		'service-provision'                    => 'fa-solid fa-hand-holding',
		'health-beauty'                        => 'fa-solid fa-spa',
		'artisan-troubleshooting-handyman'     => 'fa-solid fa-hammer',
		'computing-services'                   => 'fa-solid fa-laptop-code',
		'tourism-and-travel-services'          => 'fa-solid fa-plane',
		'translation-writing'                  => 'fa-solid fa-language',
		'construction-renovation-carpentry'    => 'fa-solid fa-helmet-safety',
		'other-services'                       => 'fa-solid fa-concierge-bell',
		
		// Learning
		'language-classes'                     => 'fa-solid fa-language',
		'computer-courses'                     => 'fa-solid fa-laptop-code',
		'tutoring-private-lessons'             => 'fa-solid fa-chalkboard-user',
		'vocational-training'                  => 'fa-solid fa-user-graduate',
		'maths-physics-chemistry'              => 'fa-solid fa-flask',
		'music-theatre-dance'                  => 'fa-solid fa-music',
		'school-support'                       => 'fa-solid fa-book-open',
		
		// Local Events
		'concerts-and-festivals'               => 'fa-solid fa-music',
		'networking-and-meetups'               => 'fa-solid fa-users',
		'sports-and-outdoors'                  => 'fa-solid fa-person-running',
		'trade-shows-conventions'              => 'fa-solid fa-store',
		'training-and-seminars'                => 'fa-solid fa-chalkboard-user',
		'ceremonies'                           => 'fa-solid fa-award',
		'conferences'                          => 'fa-solid fa-microphone',
		'weddings'                             => 'fa-solid fa-ring',
		'birthdays'                            => 'fa-solid fa-cake-candles',
		'family-events'                        => 'fa-solid fa-people-group',
		'nightlife'                            => 'fa-solid fa-moon',
		'all-others-events'                    => 'fa-solid fa-star',
	];
	
	public function run(): void
	{
		$subCategories = Category::query()
			->whereNotNull('parent_id')
			->with('parent')
			->get();
		
		$updated = 0;
		$inherited = 0;
		
		foreach ($subCategories as $category) {
			$current = $category->getRawOriginal('icon_class');
			if (!self::FORCE && !empty($current)) {
				continue;
			}
			
			$icon = $this->icons[$category->slug] ?? null;
			if (empty($icon)) {
				// No mapping: inherit the parent's icon so the category still has one
				$icon = $category->parent?->getRawOriginal('icon_class');
				if (empty($icon)) {
					continue;
				}
				$inherited++;
			}
			
			if ($current !== $icon) {
				Category::query()->where('id', $category->id)->update(['icon_class' => $icon]);
				$updated++;
			}
		}
		
		// The sidebar category tree is cached
		Artisan::call('cache:clear');
		
		$this->command?->info("Sub-category icons: {$updated} updated ({$inherited} inherited from parent).");
	}
}
