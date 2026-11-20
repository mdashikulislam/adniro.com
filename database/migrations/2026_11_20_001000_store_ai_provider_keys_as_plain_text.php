<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * AI provider API keys are stored as plain text from now on, at the owner's request (adniro:ai).
 * Keys encrypted with the current APP_KEY are decrypted. Values that cannot be decrypted (saved
 * under another APP_KEY) are useless and are cleared, so the admin screen asks for the key again.
 */
return new class extends Migration
{
	public function up(): void
	{
		if (!Schema::hasTable('ai_providers')) {
			return;
		}
		
		foreach (DB::table('ai_providers')->whereNotNull('api_key')->get(['id', 'api_key']) as $row) {
			$value = (string)$row->api_key;
			if (!$this->looksEncrypted($value)) {
				continue; // already plain text
			}
			
			try {
				$plain = Crypt::decryptString($value);
			} catch (Throwable $e) {
				$plain = null;
			}
			
			DB::table('ai_providers')->where('id', $row->id)->update(['api_key' => $plain]);
		}
	}
	
	public function down(): void
	{
		if (!Schema::hasTable('ai_providers')) {
			return;
		}
		
		foreach (DB::table('ai_providers')->whereNotNull('api_key')->get(['id', 'api_key']) as $row) {
			if (!$this->looksEncrypted((string)$row->api_key)) {
				DB::table('ai_providers')->where('id', $row->id)->update(['api_key' => Crypt::encryptString((string)$row->api_key)]);
			}
		}
	}
	
	/**
	 * A Laravel encryption payload: base64 of JSON with iv, value and mac.
	 */
	private function looksEncrypted(string $value): bool
	{
		$payload = json_decode((string)base64_decode($value, true), true);
		
		return is_array($payload) && isset($payload['iv'], $payload['value'], $payload['mac']);
	}
};
