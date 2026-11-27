<?php

namespace App\Services\ListingCleanUp;

/*
 * The fixed rules of the listings clean-up (adniro), used by "php artisan listings:clean-up".
 *
 * They were written from the listings found on the live site on 2026-11-27 and checked against
 * every match there. They are meant for that back catalogue: new listings are checked by the AI
 * moderation (App\Services\Ai\Moderation), which reads the whole listing instead of keywords.
 *
 * The rule's name is stored in post_moderations.rule; the categories are the moderation policy's
 * own (App\Services\Ai\Moderation\Policy) and are what the owner is shown as the reason.
 */

final class CleanUpRules
{
	public const PROHIBITED = 'cleanup_prohibited';
	public const CRYPTO_SOFTWARE = 'cleanup_crypto_software';
	public const PRICE_TEMPLATE = 'cleanup_price_template';
	public const WRONG_COUNTRY = 'cleanup_wrong_country';
	public const DUPLICATE = 'cleanup_duplicate';

	/** In the order they are tried: a listing is hidden by the first rule it matches. */
	public const ALL = [
		self::PROHIBITED,
		self::CRYPTO_SOFTWARE,
		self::PRICE_TEMPLATE,
		self::WRONG_COUNTRY,
		self::DUPLICATE,
	];

	/** The category of the three rules that are about promotion, not about prohibited goods. */
	public const PROMOTION_CATEGORIES = ['spam_misleading'];

	/** Countries where a title naming an Indian or Pakistani place means the listing is misfiled. */
	private const ENGLISH_SPEAKING_COUNTRIES = ['US', 'GB', 'AU', 'CA'];

	/** Kinds of prohibited content, read in the title and the text: [moderation category, pattern]. */
	private const PROHIBITED_PATTERNS = [
		['illegal_drugs', '~\b(fioricet|butalbital|codeine|hydrocodone|oxycodone|oxycontin|percocets?|adderall|ritalin|xanax|alprazolam|tramadol|ambien|zolpidem|valium|diazepam|klonopin|clonazepam|ativan|lorazepam|carisoprodol|vicodin|norco|dilaudid|methadone|suboxone|subutex|phentermine|adipex|modafinil|armodafinil|viagra|cialis|sildenafil|tadalafil|tadarise|tadora|tadalis|lovegra|dapoxetine|levitra|kamagra|cenforce|vidalista|fildena|tapentadol|gabapentin|pregabalin|lyrica|ozempic|mounjaro|semaglutide|tirzepatide|wegovy|saxenda|abortion pills?|mifepristone|misoprostol|mtp kit|cytotec|ivermectin|roxicodone|opana|demerol|morphine|fentanyl|lunesta|zopiclone|etizolam|nembutal|pentobarbital|cocaine)\b|\b(without|no) (a )?(prescription|rx)\b|\b(weight loss|ed) (medication|medicines?|injection)s? online\b~iu'],
		['hazardous_regulated', '~\b(casinos?|betting|sportsbook|poker|rummy|satta|matka|teen ?patti|slot (games?|machines?)|online slots?|cricket id|betting id|baccarat|roulette|lottery|jackpot|bookie|aviator game|pragmatic play|1xbet|4rabet|mostbet|igaming|crash game|prediction (game|market)s?)\b~iu'],
		['scam_fraud', '~\b(customer|technical|tech) (support|service|care|helpline) (phone |contact |toll[- ]free )?numbers?\b|\bairlines? refund\b|\b(refund|helpline|support|toll[- ]free) (24 hours )?(phone )?numbers?\b|\b(speak|talk) (to|with) (a |an )?(live |real )?(person|human|agent|representative)\b~iu'],
		['scam_fraud', '~\bflash usdt\b|\bbinary options?\b|\b(crypto|bitcoin|funds?|asset) recovery\b|\b(recover(y|ing)?|retrieve|reclaim)\b.{0,40}\b(crypto(currency)?|bitcoin|btc|usdt|stolen funds|lost funds|scammed)\b|\bhire a hacker\b|\bhackers? for hire\b|\bguaranteed (returns?|profits?)\b|\bforex signals?\b|\bdouble your (money|investment)\b~iu'],
		['spam_misleading', '~\b(payday loans?|short[- ]term loans?|same[- ]day loans?|instant (cash )?loans?|loans? (with )?(no|without) credit checks?|bad credit loans?|cash advance)\b~iu'],
		['spam_misleading', '~\b(vashikaran|black magic|kala jadu|love (problem|marriage|back|vashikaran) (solution|specialist|expert|healing)|love spells?|lost love|(get|bring) (your |my )?(ex|love|lover) back|tantrik|voodoo|spell casters?|intercaste marriage (solution|specialist)|molvi|aghori)\b~iu'],
		['counterfeit_fake_docs', '~\b(fake|counterfeit|novelty|replica) (passports?|ids?|id cards?|driver.{0,3}s? licen[sc]es?|diplomas?|degrees?|certificates?|money|notes|bills|banknotes|bank statements?|transcripts?)\b|\bbuy (real|registered|genuine|original) (passports?|driver|ielts|id cards?|visa|residence permit)|\b(ielts|pte|toefl|nebosh) (certificate )?without exam\b|\bssd (chemical|solution)\b|\bcloned? cards?\b|\bdumps with pin\b~iu'],
		['hacking_personal_data', '~\b(traders?|hni|mobile number|phone number|email|whatsapp number) (database|data ?base|lists?) (provider|for sale)|\bdatabase provider\b|\bphone number lookup\b|\bfind (person|someone) (by|using) (a )?phone number\b~iu'],
	];

	/** Read in the title only: in a text, "adult" wording also describes ordinary shops. */
	private const ADULT_TITLE_PATTERN = '~\b(escorts?|call girls?|adult (services?|toys?|dating)|online dating service|sex (toys?|dolls?|chat|cam)|erotic|nuru|body to body massage|happy ending|porn|xxx|hookups?|sugar (daddy|mommy|baby))\b~iu';

	private const CRYPTO_SOFTWARE_PATTERN = '~\b(clone (script|software)|token development|crypto(currency)? exchange (development|script|software)|(defi|nft|web3|blockchain|meme ?coin|trading bot|mlm software|dex|wallet) (development|token|platform))~iu';

	/** "<product> Price in <place>", not an ordinary "best price in town". */
	private const PRICE_TEMPLATE_PATTERN = '~(?<!best )(?<!lowest )(?<!low )(?<!great )(?<!good )(?<!cheapest )(?<!affordable )(?<!reasonable )\bprice in [a-z]{3}~iu';

	/** "Indian" is left out on purpose: Indian food, art and clothes are sold everywhere. */
	private const INDIA_OR_PAKISTAN_PATTERN = '~\b(india|pakistan|hyderabad|chennai|bangalore|bengaluru|delhi|mumbai|pune|kolkata|noida|gurgaon|gurugram|ahmedabad|jaipur|lucknow|chandigarh|kerala|kochi|coimbatore|indore|surat|nagpur|patna|lahore|karachi|islamabad|kukatpally|punjab|haryana|gujarat|tamil ?nadu|telangana)\b~iu';

	/**
	 * The rule one listing breaks on its own, or null. (Copies need the other listings:
	 * see ListingCleanUp.)
	 *
	 * @return array{rule: string, categories: string[]}|null
	 */
	public static function match(string $title, ?string $description, ?string $countryCode): ?array
	{
		$categories = self::prohibitedCategories($title, self::plainText($description));
		if (!empty($categories)) {
			return ['rule' => self::PROHIBITED, 'categories' => $categories];
		}

		$promotionRule = self::promotionRule($title, self::plainText($description), strtoupper((string)$countryCode));

		return is_null($promotionRule) ? null : ['rule' => $promotionRule, 'categories' => self::PROMOTION_CATEGORIES];
	}

	/**
	 * A listing's text as a person reads it: no tags, no entities, single spaces.
	 */
	public static function plainText(?string $html): string
	{
		$spaced = preg_replace('~<(br|/p|/li|/h[1-6]|/div|/tr)\b[^>]*>~i', ' ', (string)$html) ?? '';
		$text = html_entity_decode(strip_tags($spaced), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$text = str_replace("\u{00A0}", ' ', $text);

		return trim(preg_replace('~\s+~u', ' ', $text) ?? '');
	}

	/**
	 * @return string[]
	 */
	private static function prohibitedCategories(string $title, string $text): array
	{
		$everything = $title . ' || ' . $text;

		$categories = [];
		foreach (self::PROHIBITED_PATTERNS as [$category, $pattern]) {
			if (preg_match($pattern, $everything) === 1) {
				$categories[] = $category;
			}
		}
		if (preg_match(self::ADULT_TITLE_PATTERN, $title) === 1) {
			$categories[] = 'adult_sexual';
		}

		return array_values(array_unique($categories));
	}

	private static function promotionRule(string $title, string $text, string $countryCode): ?string
	{
		if (preg_match(self::CRYPTO_SOFTWARE_PATTERN, $title . ' || ' . $text) === 1) {
			return self::CRYPTO_SOFTWARE;
		}

		if (!in_array($countryCode, self::ENGLISH_SPEAKING_COUNTRIES, true)) {
			return null;
		}

		if (preg_match(self::PRICE_TEMPLATE_PATTERN, $title) === 1) {
			return self::PRICE_TEMPLATE;
		}

		return preg_match(self::INDIA_OR_PAKISTAN_PATTERN, $title) === 1 ? self::WRONG_COUNTRY : null;
	}
}
