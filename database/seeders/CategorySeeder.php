<?php

namespace Database\Seeders;

use App\Helpers\Common\NestedSetSeeder;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		$entries = [
			[
				'name'       => [
					'en' => 'Automobiles',
					'fr' => 'Auto & Moto',
					'es' => 'Automóviles',
					'ar' => 'السيارات',
					'pt' => 'Automóveis',
					'ru' => 'Автомобили',
					'tr' => 'Otomobil',
					'th' => 'รถยนต์',
					'ka' => 'ავტომობილები',
					'zh' => '汽车类',
					'ja' => '自動車',
					'it' => 'Automobili',
					'ro' => 'Automobile',
					'de' => 'Automobile und Fahrzeuge',
					'hi' => 'ऑटोमोबाइल',
					'bn' => 'অটোমোবাইল',
					'he' => 'מכוניות',
				],
				'slug'       => 'automobiles',
				'image_path' => 'app/categories/blue/car.png',
				'icon_class' => 'fa-solid fa-car',
				'type'       => 'classified',
				'children'   => [
					[
						'name'       => [
							'en' => 'Cars',
						],
						'slug'       => 'cars',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Buses & Minibus',
						],
						'slug'       => 'buses-and-minibus',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Heavy Equipment',
						],
						'slug'       => 'heavy-equipment',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Motorcycles & Scooters',
						],
						'slug'       => 'motorcycles-and-scooters',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Trucks & Trailers',
						],
						'slug'       => 'trucks-and-trailers',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Vehicle Parts & Accessories',
						],
						'slug'       => 'car-parts-and-accessories',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Watercraft & Boats',
						],
						'slug'       => 'watercraft-and-boats',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
				],
			],
			[
				'name'       => [
					'en' => 'Phones & Tablets',
					'fr' => 'Smartphone & Tablettes',
					'es' => 'Smartphone y Tabletas',
					'ar' => 'الهواتف والأجهزة اللوحية',
					'pt' => 'Telemóveis e Tablets',
					'ru' => 'Телефоны и планшеты',
					'tr' => 'Telefonlar ve Tabletler',
					'th' => 'โทรศัพท์และแท็บเล็ต',
					'ka' => 'ტელეფონები და ტაბლეტები',
					'zh' => '手机和平板电脑',
					'ja' => '携帯電話とタブレット',
					'it' => 'Telefoni e tablet',
					'ro' => 'Telefoane și tablete',
					'de' => 'Telefone & Tablets',
					'hi' => 'फ़ोन और टेबलेट',
					'bn' => 'ফোন এবং ট্যাবলেট',
					'he' => 'טלפונים וטאבלטים',
				],
				'slug'       => 'phones-and-tablets',
				'image_path' => 'app/categories/blue/mobile-alt.png',
				'icon_class' => 'fa-solid fa-mobile-screen-button',
				'type'       => 'classified',
				'children'   => [
					[
						'name'       => [
							'en' => 'Mobile Phones',
						],
						'slug'       => 'mobile-phones',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Accessories for Mobile Phones & Tablets',
						],
						'slug'       => 'mobile-phones-tablets-accessories',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Smart Watches & Trackers',
						],
						'slug'       => 'smart-watches-and-trackers',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Tablets',
						],
						'slug'       => 'tablets',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
				],
			],
			[
				'name'       => [
					'en' => 'Electronics',
					'fr' => 'Hi-Tech',
					'es' => 'Electrónica',
					'ar' => 'إلكترونيات',
					'pt' => 'Eletrônicos',
					'ru' => 'Электроника',
					'tr' => 'Elektronik',
					'th' => 'อิเล็กทรอนิกส์',
					'ka' => 'ელექტრონიკა',
					'zh' => '电子产品',
					'ja' => 'エレクトロニクス',
					'it' => 'Elettronica',
					'ro' => 'Electronică',
					'de' => 'Elektronik',
					'hi' => 'इलेक्ट्रानिक्स',
					'bn' => 'ইলেকট্রনিক্স',
					'he' => 'מכשירי חשמל',
				],
				'slug'       => 'electronics',
				'image_path' => 'app/categories/blue/fa-laptop.png',
				'icon_class' => 'fa-solid fa-laptop',
				'type'       => 'classified',
				'children'   => [
					[
						'name'       => [
							'en' => 'Accessories & Supplies for Electronics',
						],
						'slug'       => 'accessories-supplies-for-electronics',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Laptops & Computers',
						],
						'slug'       => 'laptops-and-computers',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'TV & DVD Equipment',
						],
						'slug'       => 'tv-dvd-equipment',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Audio & Music Equipment',
						],
						'slug'       => 'audio-music-equipment',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Computer Accessories',
						],
						'slug'       => 'computer-accessories',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Computer Hardware',
						],
						'slug'       => 'computer-hardware',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Computer Monitors',
						],
						'slug'       => 'computer-monitors',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Headphones',
						],
						'slug'       => 'headphones',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Networking Products',
						],
						'slug'       => 'networking-products',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Photo & Video Cameras',
						],
						'slug'       => 'photo-video-cameras',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Printers & Scanners',
						],
						'slug'       => 'printers-and-scanners',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Security & Surveillance',
						],
						'slug'       => 'security-and-surveillance',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Software',
						],
						'slug'       => 'software',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Video Games',
						],
						'slug'       => 'video-games',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Game Consoles',
						],
						'slug'       => 'video-game-consoles',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
				],
			],
			[
				'name'       => [
					'en' => 'Furniture & Appliances',
					'fr' => 'Meubles & Electroménagers',
					'es' => 'Muebles y Electrodomésticos',
					'ar' => 'الأثاث والأجهزة',
					'pt' => 'Móveis e Eletrodomésticos',
					'ru' => 'Мебель и техника',
					'tr' => 'Mobilya ve Ev Aletleri',
					'th' => 'เฟอร์นิเจอร์และเครื่องใช้ไฟฟ้า',
					'ka' => 'ავეჯი და ტექნიკა',
					'zh' => '家居，家具和电器',
					'ja' => '家庭、家具、電化製品',
					'it' => 'Mobili ed elettrodomestici',
					'ro' => 'Mobilier și electrocasnice',
					'de' => 'Möbel & Geräte',
					'hi' => 'फर्नीचर और उपकरण',
					'bn' => 'আসবাবপত্র ও যন্ত্রপাতি',
					'he' => 'ריהוט ומוצרי חשמל',
				],
				'slug'       => 'furniture-appliances',
				'image_path' => 'app/categories/blue/couch.png',
				'icon_class' => 'fa-solid fa-couch',
				'type'       => 'classified',
				'children'   => [
					[
						'name'       => [
							'en' => 'Furniture - Tableware',
						],
						'slug'       => 'furniture-tableware',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Antiques - Art - Decoration',
						],
						'slug'       => 'antiques-art-decoration',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Appliances',
						],
						'slug'       => 'appliances',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Garden',
						],
						'slug'       => 'garden',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Toys - Games - Figurines',
						],
						'slug'       => 'toys-games-figurines',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Wine & Gourmet - Recipes',
						],
						'slug'       => 'wine-gourmet-recipes',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
				],
			],
			[
				'name'       => [
					'en' => 'Real estate',
					'fr' => 'Immobilier',
					'es' => 'Bienes raíces',
					'ar' => 'العقارات',
					'pt' => 'Imobiliária',
					'ru' => 'Недвижимость',
					'tr' => 'Emlak',
					'th' => 'อสังหาริมทรัพย์',
					'ka' => 'Უძრავი ქონება',
					'zh' => '房地产',
					'ja' => '不動産',
					'it' => 'Immobiliare',
					'ro' => 'Proprietate imobiliara',
					'de' => 'Grundeigentum',
					'hi' => 'रियल एस्टेट',
					'bn' => 'আবাসন',
					'he' => 'נדל"ן',
				],
				'slug'       => 'real-estate',
				'image_path' => 'app/categories/blue/fa-home.png',
				'icon_class' => 'fa-solid fa-house',
				'type'       => 'classified',
				'children'   => [
					[
						'name'       => [
							'en' => 'Houses & Apartments For Rent',
						],
						'slug'       => 'houses-apartments-for-rent',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Houses & Apartments For Sale',
						],
						'slug'       => 'houses-apartments-for-sale',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Land & Plots for Rent',
						],
						'slug'       => 'land-and-plots-for-rent',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Land & Plots For Sale',
						],
						'slug'       => 'land-and-plots-for-sale',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Commercial Property For Rent',
						],
						'slug'       => 'commercial-property-for-rent',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Commercial Property For Sale',
						],
						'slug'       => 'commercial-properties',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Event centres, Venues and Workstations',
						],
						'slug'       => 'event-centers-and-venues',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Short Rental',
						],
						'slug'       => 'temporary-and-vacation-rentals',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
				],
			],
			[
				'name'       => [
					'en' => 'Animals & Pets',
					'fr' => 'Animaux',
					'es' => 'Animales y Mascotas',
					'ar' => 'الحيوانات',
					'pt' => 'Animais e Mascotes',
					'ru' => 'Животные',
					'tr' => 'Hayvanlar',
					'th' => 'สัตว์และสัตว์เลี้ยง',
					'ka' => 'ცხოველები',
					'zh' => '动物与宠物',
					'ja' => '動物とペット',
					'it' => 'Animali',
					'ro' => 'Animale',
					'de' => 'Tiere & Haustiere',
					'hi' => 'पशु और पालतू जानवर',
					'bn' => 'প্রাণী এবং পোষা প্রাণী',
					'he' => 'בעלי חיים וחיות מחמד',
				],
				'slug'       => 'animals-and-pets',
				'image_path' => 'app/categories/blue/paw.png',
				'icon_class' => 'fa-solid fa-paw',
				'type'       => 'classified',
				'children'   => [
					[
						'name'       => [
							'en' => 'Birds',
						],
						'slug'       => 'birds',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Cats & Kittens',
						],
						'slug'       => 'cats-and-kittens',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Dogs',
						],
						'slug'       => 'dogs-and-puppies',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Fish',
						],
						'slug'       => 'fish',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Pet\'s Accessories',
						],
						'slug'       => 'pets-accessories',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Reptiles',
						],
						'slug'       => 'reptiles',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Other Animals',
						],
						'slug'       => 'other-animals',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
				],
			],
			[
				'name'       => [
					'en' => 'Fashion',
					'fr' => 'Mode',
					'es' => 'Moda',
					'ar' => 'موضه',
					'pt' => 'Moda',
					'ru' => 'Мода',
					'tr' => 'Moda',
					'th' => 'แฟชั่น',
					'ka' => 'მოდა',
					'zh' => '时尚',
					'ja' => 'ファッション',
					'it' => 'Moda',
					'ro' => 'Modă',
					'de' => 'Mode',
					'hi' => 'पहनावा',
					'bn' => 'ফ্যাশন',
					'he' => 'אופנה',
				],
				'slug'       => 'fashion',
				'image_path' => 'app/categories/blue/tshirt.png',
				'icon_class' => 'fa-solid fa-shirt',
				'type'       => 'classified',
				'children'   => [
					[
						'name'       => [
							'en' => 'Bags',
						],
						'slug'       => 'bags',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Clothing',
						],
						'slug'       => 'clothing',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Clothing Accessories',
						],
						'slug'       => 'clothing-accessories',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Jewelry',
						],
						'slug'       => 'jewelry',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Shoes',
						],
						'slug'       => 'shoes',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Watches',
						],
						'slug'       => 'watches',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Wedding Wear & Accessories',
						],
						'slug'       => 'wedding-wear-accessories',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
				],
			],
			[
				'name'       => [
					'en' => 'Beauty & Well being',
					'fr' => 'Beauté & Bien-être',
					'es' => 'Belleza y Bienestar',
					'ar' => 'الجمال والرفاهية',
					'pt' => 'Beleza e Bem estar',
					'ru' => 'Красота и благополучие',
					'tr' => 'Güzellik ve Sağlık',
					'th' => 'ความงามและความเป็นอยู่ที่ดี',
					'ka' => 'სილამაზე და კეთილდღეობა',
					'zh' => '美容与健康',
					'ja' => '美容と幸福',
					'it' => 'Bellezza e benessere',
					'ro' => 'Frumusețe și bunăstare',
					'de' => 'Schönheit & Wohlbefinden',
					'hi' => 'सौंदर्य और भलाई',
					'bn' => 'সৌন্দর্য ও সুস্থতা',
					'he' => 'יופי ורווחה',
				],
				'slug'       => 'beauty-well-being',
				'image_path' => 'app/categories/blue/spa.png',
				'icon_class' => 'fa-solid fa-spa',
				'type'       => 'classified',
				'children'   => [
					[
						'name'       => [
							'en' => 'Bath & Body',
						],
						'slug'       => 'bath-and-body',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Fragrance',
						],
						'slug'       => 'fragrance',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Hair Beauty',
						],
						'slug'       => 'hair-beauty',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Makeup',
						],
						'slug'       => 'makeup',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Sexual Wellness',
						],
						'slug'       => 'sexual-wellness',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Skin Care',
						],
						'slug'       => 'care',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Tobacco Accessories',
						],
						'slug'       => 'tobacco-accessories',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Tools & Accessories',
						],
						'slug'       => 'tools-and-accessories',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Vitamins & Supplements',
						],
						'slug'       => 'vitamins-and-supplements',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Pro Massage',
						],
						'slug'       => 'pro-massage',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
				],
			],
			[
				'name'       => [
					'en' => 'Jobs',
					'fr' => 'Emplois',
					'es' => 'Trabajos',
					'ar' => 'وظائف',
					'pt' => 'Empregos',
					'ru' => 'Вакансии',
					'tr' => 'Meslekler',
					'th' => 'งาน',
					'ka' => 'სამუშაო ადგილები',
					'zh' => '职位',
					'ja' => 'ジョブズ',
					'it' => 'Lavori',
					'ro' => 'Locuri de munca',
					'de' => 'Arbeitsplätze',
					'hi' => 'नौकरियां',
					'bn' => 'চাকরি',
					'he' => 'מקומות תעסוקה',
				],
				'slug'       => 'jobs',
				'image_path' => 'app/categories/blue/mfglabs-users.png',
				'icon_class' => 'fa-solid fa-briefcase',
				'type'       => 'job-offer',
				'children'   => [
					[
						'name'       => [
							'en' => 'Agriculture - Environment',
						],
						'slug'       => 'agriculture-environment',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Assistantship - Secretariat - Helpdesk',
						],
						'slug'       => 'assistantship-secretariat-helpdesk',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Automotive - Mechanic',
						],
						'slug'       => 'automotive-mechanic',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'BTP - Construction - Building',
						],
						'slug'       => 'btp-construction-building',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Trade - Business Services',
						],
						'slug'       => 'trade-business-services',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Commercial - Sale Jobs',
						],
						'slug'       => 'commercial-sale-jobs',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Accounting - Management - Finance',
						],
						'slug'       => 'accounting-management-finance',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Steering - Manager',
						],
						'slug'       => 'steering-manager',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Aesthetics - Hair - Beauty',
						],
						'slug'       => 'aesthetics-hair-beauty',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Public Service Jobs',
						],
						'slug'       => 'public-service-jobs',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Real Estate Jobs',
						],
						'slug'       => 'real-estate-jobs',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Independent - Freelance - Telecommuting',
						],
						'slug'       => 'independent-freelance-telecommuting',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Computers - Internet - Telecommunications',
						],
						'slug'       => 'computers-internet-telecommunications',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Industry, Production & engineering',
						],
						'slug'       => 'industry-production-engineering',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Marketing - Communication',
						],
						'slug'       => 'marketing-communication',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Babysitting - Nanny Work',
						],
						'slug'       => 'babysitting-nanny-work',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'HR - Training - Education',
						],
						'slug'       => 'hr-training-education',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Medical - Healthcare - Social',
						],
						'slug'       => 'medical-healthcare-social',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Security - Guarding',
						],
						'slug'       => 'security-guarding',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Household Services - Housekeeping',
						],
						'slug'       => 'household-services-housekeeping',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Tourism - Hotels - Restaurants - Leisure',
						],
						'slug'       => 'tourism-hotels-restaurants-leisure',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Transportation - Logistics',
						],
						'slug'       => 'transportation-logistics',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
					[
						'name'       => [
							'en' => 'Others Jobs Offer',
						],
						'slug'       => 'others-jobs-offer',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'job-offer',
					],
				],
			],
			[
				'name'       => [
					'en' => 'Services',
					'fr' => 'Services',
					'es' => 'Servicios',
					'ar' => 'خدمات',
					'pt' => 'Serviços',
					'ru' => 'Сервисы',
					'tr' => 'Hizmetler',
					'th' => 'บริการ',
					'ka' => 'მომსახურება',
					'zh' => '服务',
					'ja' => 'サービス',
					'it' => 'Servizi',
					'ro' => 'Servicii',
					'de' => 'Dienstleistungen',
					'hi' => 'सेवाएं',
					'bn' => 'সেবা',
					'he' => 'שירותים',
				],
				'slug'       => 'services',
				'image_path' => 'app/categories/blue/ion-clipboard.png',
				'icon_class' => 'fa-solid fa-clipboard-list',
				'type'       => 'classified',
				'children'   => [
					[
						'name'       => [
							'en' => 'Casting, Model, Photographer',
						],
						'slug'       => 'casting-model-photographer',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Carpooling',
						],
						'slug'       => 'carpooling',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Moving, Furniture Guard',
						],
						'slug'       => 'moving-furniture-guard',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Destocking - Commercial',
						],
						'slug'       => 'destocking-commercial',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Industrial Equipment',
						],
						'slug'       => 'industrial-equipment',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Aesthetics, Hairstyling',
						],
						'slug'       => 'aesthetics-hairstyling',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Materials and Equipment Pro',
						],
						'slug'       => 'materials-and-equipment-pro',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Event Organization Services',
						],
						'slug'       => 'event-organization-services',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Service Provision',
						],
						'slug'       => 'service-provision',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Health, Beauty',
						],
						'slug'       => 'health-beauty',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Artisan, Troubleshooting, Handyman',
						],
						'slug'       => 'artisan-troubleshooting-handyman',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Computing Services',
						],
						'slug'       => 'computing-services',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Tourism and Travel Services',
						],
						'slug'       => 'tourism-and-travel-services',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Translation, Writing',
						],
						'slug'       => 'translation-writing',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Construction - Renovation - Carpentry',
						],
						'slug'       => 'construction-renovation-carpentry',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Other services',
						],
						'slug'       => 'other-services',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
				],
			],
			[
				'name'       => [
					'en' => 'Learning',
					'fr' => 'Apprentissage',
					'es' => 'Aprendizaje',
					'ar' => 'تعلم',
					'pt' => 'Aprendendo',
					'ru' => 'Обучение',
					'tr' => 'Öğrenme',
					'th' => 'การเรียนรู้',
					'ka' => 'სწავლა',
					'zh' => '学习',
					'ja' => '学習',
					'it' => 'Apprendimento',
					'ro' => 'Învăţare',
					'de' => 'Lernen',
					'hi' => 'सीखना',
					'bn' => 'শেখা',
					'he' => 'לְמִידָה',
				],
				'slug'       => 'learning',
				'image_path' => 'app/categories/blue/fa-graduation-cap.png',
				'icon_class' => 'fa-solid fa-graduation-cap',
				'type'       => 'classified',
				'children'   => [
					[
						'name'       => [
							'en' => 'Language Classes',
						],
						'slug'       => 'language-classes',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Computer Courses',
						],
						'slug'       => 'computer-courses',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Tutoring, Private Lessons',
						],
						'slug'       => 'tutoring-private-lessons',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Vocational Training',
						],
						'slug'       => 'vocational-training',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Maths, Physics, Chemistry',
						],
						'slug'       => 'maths-physics-chemistry',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Music, Theatre, Dance',
						],
						'slug'       => 'music-theatre-dance',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'School support',
						],
						'slug'       => 'school-support',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
				],
			],
			[
				'name'       => [
					'en' => 'Local Events',
					'fr' => 'Evénements',
					'es' => 'Eventos',
					'ar' => 'الأحداث',
					'pt' => 'Eventos',
					'ru' => 'События',
					'tr' => 'Etkinlikler',
					'th' => 'เหตุการณ์',
					'ka' => 'Ივენთი',
					'zh' => '当地活动',
					'ja' => 'ローカルイベント',
					'it' => 'Eventi locali',
					'ro' => 'Evenimente locale',
					'de' => 'Lokale Veranstaltungen',
					'hi' => 'स्थानीय कार्यक्रम',
					'bn' => 'স্থানীয় ঘটনা',
					'he' => 'אירועים מקומיים',
				],
				'slug'       => 'local-events',
				'image_path' => 'app/categories/blue/calendar-alt.png',
				'icon_class' => 'fa-regular fa-calendar-days',
				'type'       => 'classified',
				'children'   => [
					[
						'name'       => [
							'en' => 'Concerts & Festivals',
						],
						'slug'       => 'concerts-and-festivals',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Networking & Meetups',
						],
						'slug'       => 'networking-and-meetups',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Sports & Outdoors',
						],
						'slug'       => 'sports-and-outdoors',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Trade Shows & Conventions',
						],
						'slug'       => 'trade-shows-conventions',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Training & Seminars',
						],
						'slug'       => 'training-and-seminars',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Ceremonies',
						],
						'slug'       => 'ceremonies',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Conferences',
						],
						'slug'       => 'conferences',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Weddings',
						],
						'slug'       => 'weddings',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Birthdays',
						],
						'slug'       => 'birthdays',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Family Events',
						],
						'slug'       => 'family-events',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'Nightlife',
						],
						'slug'       => 'nightlife',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
					[
						'name'       => [
							'en' => 'All others events',
						],
						'slug'       => 'all-others-events',
						'image_path' => 'app/default/categories/fa-folder-blue.png',
						'icon_class' => null,
						'type'       => 'classified',
					],
				],
			],
		];
		
		// Add or update columns
		$entries = $this->processCategoryEntries($entries);
		
		$tableName = (new Category())->getTable();
		
		$startPosition = NestedSetSeeder::getNextRgtValue($tableName);
		NestedSetSeeder::insertEntries($tableName, $entries, $startPosition);
	}
	
	/**
	 * Recursively process category entries and their children
	 *
	 * @param array $entries The category entries to process
	 * @param int $depth The current depth level (0 for root)
	 * @return array Processed entries
	 */
	private function processCategoryEntries(array $entries, int $depth = 0): array
	{
		$processedEntries = [];
		
		foreach ($entries as $key => $entry) {
			// Set common properties for current entry
			$entry['description'] = null;
			$entry['is_for_permanent'] = 0;
			$entry['parent_id'] = null;
			$entry['lft'] = 0;
			$entry['rgt'] = 0;
			$entry['depth'] = $depth;
			$entry['active'] = 1;
			
			// Process children recursively if they exist
			$children = $entry['children'] ?? [];
			if (!empty($children) && is_array($children)) {
				$entry['children'] = $this->processCategoryEntries($children, $depth + 1);
			}
			
			$processedEntries[$key] = $entry;
		}
		
		return $processedEntries;
	}
	
}
