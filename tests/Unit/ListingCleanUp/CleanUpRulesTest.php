<?php

namespace Tests\Unit\ListingCleanUp;

use App\Services\ListingCleanUp\CleanUpRules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/*
 * The fixed rules of the listings clean-up (adniro): which listing is hidden, by which rule,
 * and which real listings are left alone. The examples are shaped like the listings found on
 * the live site on 2026-11-27.
 */

class CleanUpRulesTest extends TestCase
{
	#[DataProvider('prohibitedListings')]
	public function test_prohibited_content_is_matched_with_its_category(string $title, string $description, string $category): void
	{
		// Act
		$match = CleanUpRules::match($title, $description, 'US');
		
		// Assert
		$this->assertNotNull($match, $title);
		$this->assertSame(CleanUpRules::PROHIBITED, $match['rule']);
		$this->assertContains($category, $match['categories']);
	}
	
	public static function prohibitedListings(): array
	{
		return [
			'prescription medicine in the title' => ['Shop Codeine Online with Fast Delivery', 'Order today.', 'illegal_drugs'],
			'prescription medicine in the text'  => ['Women’s Health Medicines Online', '<p>We offer Lovegra 100 and abortion pills online.</p>', 'illegal_drugs'],
			'gambling software'                  => ['1xbet Clone - Ready-to-Launch Sportsbook', 'A betting platform.', 'hazardous_regulated'],
			'dating service'                     => ['Online Dating Service in Toronto Join Free', 'Meet singles.', 'adult_sexual'],
			'refund phone line'                  => ['Copa Airlines Refund Assistance', 'Call our airlines refund desk.', 'scam_fraud'],
			'scam finance tool'                  => ['The Smarter Way to Handle Payments - Flash USDT Software', 'Send flash usdt.', 'scam_fraud'],
			'payday loans'                       => ['Same Day Payday Loans: Getting Money without Security', 'Apply now.', 'spam_misleading'],
			'occult services'                    => ['Black Magic Spells for Love', 'Bring your lost love back.', 'spam_misleading'],
			'fake documents'                     => ['Buy registered passport and fake diplomas', 'Real documents.', 'counterfeit_fake_docs'],
			'personal data'                      => ['Find Person by Phone Number Using a Simple Search Tool', 'Lookup anyone.', 'hacking_personal_data'],
		];
	}
	
	public function test_a_listing_with_two_kinds_of_prohibited_content_gets_both_categories(): void
	{
		// Act
		$match = CleanUpRules::match('Casino jackpot and codeine online', 'No prescription needed.', 'US');
		
		// Assert
		$this->assertSame(['illegal_drugs', 'hazardous_regulated'], $match['categories']);
	}
	
	public function test_adult_wording_counts_in_the_title_only(): void
	{
		// Act: a shop franchise that only mentions adult toys in its text
		$match = CleanUpRules::match('Lingerie Store Franchise Opportunity', 'Are you operating an adult toy store?', 'US');
		
		// Assert
		$this->assertNull($match);
	}
	
	public function test_crypto_and_exchange_software_adverts_are_matched(): void
	{
		// Act
		$match = CleanUpRules::match('P2P Crypto Exchange Development Company', 'We build DEX platforms.', 'US');
		
		// Assert
		$this->assertSame(CleanUpRules::CRYPTO_SOFTWARE, $match['rule']);
		$this->assertSame(['spam_misleading'], $match['categories']);
	}
	
	public function test_a_price_in_a_place_title_is_matched_in_the_four_english_speaking_countries(): void
	{
		// Arrange
		$title = 'Sardar Jee Hair Color Gel Price In Sialkot';
		
		// Act & Assert
		$this->assertSame(CleanUpRules::PRICE_TEMPLATE, CleanUpRules::match($title, 'Order now.', 'US')['rule']);
		$this->assertSame(CleanUpRules::PRICE_TEMPLATE, CleanUpRules::match($title, 'Order now.', 'GB')['rule']);
		$this->assertNull(CleanUpRules::match('Internet Leased Line Price in Haryana', 'Fast internet.', 'IN'));
		$this->assertNull(CleanUpRules::match('Biometric Machine Price in UAE', 'For offices.', 'AE'));
	}
	
	public function test_a_listing_filed_in_the_wrong_country_is_matched(): void
	{
		// Act
		$match = CleanUpRules::match('Python Online Training Real-time support from Hyderabad', 'Join us.', 'US');
		
		// Assert
		$this->assertSame(CleanUpRules::WRONG_COUNTRY, $match['rule']);
		$this->assertNull(CleanUpRules::match('Python Online Training Real-time support from Hyderabad', 'Join us.', 'IN'), 'right country');
		$this->assertNull(CleanUpRules::match('Python Online Training', 'Our office is in Hyderabad.', 'US'), 'the title decides, not the text');
	}
	
	#[DataProvider('realListings')]
	public function test_real_listings_are_left_alone(string $title, string $description, string $country): void
	{
		$this->assertNull(CleanUpRules::match($title, $description, $country), $title);
	}
	
	public static function realListings(): array
	{
		return [
			'second-hand phone'       => ['Apple iPhone 13 128GB Blue', 'With its box, small scratch on the back.', 'US'],
			'indian food in the US'   => ['Where Can You Find the Best Indian Food Los Angeles Has to Offer?', 'Our restaurant in LA.', 'US'],
			'indian clothes in the US' => ['Nihal Fashion | Indian Ethnic Wear for Every Celebration', 'Sarees and lehengas.', 'US'],
			'a flat in Delhi, in India' => ['2 BHK flat for rent in Delhi', 'Near the metro, available now.', 'IN'],
			'a best-price offer'      => ['Sofa at the best price in town', 'Grey fabric sofa.', 'IN'],
			'a card table'            => ['Folding card table, seats eight', 'Green felt, for family game nights.', 'US'],
			'a best price in the US'  => ['Grey sofa at the best price in town', 'Fabric sofa, no stains.', 'US'],
			'a pharmacy job'          => ['Pharmacy assistant wanted', 'Full time, experience with prescriptions preferred.', 'GB'],
		];
	}
	
	public function test_prohibited_content_wins_over_the_other_rules(): void
	{
		// Act: a pills advert that is also filed in the wrong country
		$match = CleanUpRules::match('Buy Tadalafil Online from India', 'Fast shipping.', 'US');
		
		// Assert
		$this->assertSame(CleanUpRules::PROHIBITED, $match['rule']);
	}
	
	public function test_the_text_of_a_listing_is_read_without_its_tags(): void
	{
		// Act & Assert
		$this->assertSame('First line Second line & more', CleanUpRules::plainText('<p>First line</p><p>Second&nbsp;line &amp; more</p>'));
	}
}
