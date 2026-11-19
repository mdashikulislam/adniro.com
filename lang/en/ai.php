<?php

/*
 * AI features (adniro:ai). Other locales fall back to English until translated.
 */

return [
	
	// Entities
	'provider'  => 'AI provider',
	'providers' => 'AI providers',
	'model'     => 'AI model',
	'models'    => 'AI models',
	'models_count' => '{0} No models|{1} :count model|[2,*] :count models',
	
	// Statuses
	'status_off'      => 'Off',
	'status_untested' => 'Not tested',
	'status_working'  => 'Working',
	'status_failed'   => 'Failed',
	
	// API keys
	'key_missing'  => 'No API key',
	'key_from_env' => 'Key from .env',
	
	// Capabilities and prices
	'cap_vision'        => 'Images',
	'cap_json'          => 'JSON',
	'cap_transcription' => 'Speech to text',
	'price_unknown'     => 'No price',
	
	// Tests
	'test_connection'     => 'Test connection',
	'test_model'          => 'Test model',
	'test_add_model_first' => 'Add a model to this provider first, then test it.',
	'test_ok'             => 'The test worked: :provider replied with :model in :ms ms.',
	'test_failed'         => 'The test failed. :reason',
	
	// Columns
	'col_name'         => 'Name',
	'col_driver'       => 'Service',
	'col_key'          => 'API key',
	'col_status'       => 'Status',
	'col_models'       => 'Models',
	'col_model'        => 'Model',
	'col_provider'     => 'Provider',
	'col_capabilities' => 'Supports',
	'col_prices'       => 'Price per 1M tokens (in / out)',
	
	// Provider fields
	'field_name'          => 'Name',
	'field_name_hint'     => 'A name for your own reference, for example "Anthropic main account".',
	'field_driver'        => 'Service',
	'field_driver_hint'   => 'The AI service this key belongs to. Use "OpenAI-compatible" for any service with an OpenAI-style API and a custom URL.',
	'field_api_key'       => 'API key',
	'field_api_key_hint_new'  => 'Paste the key from the provider\'s dashboard. It is stored encrypted and never shown again.',
	'field_api_key_hint_edit' => 'Current key: :masked. Leave empty to keep it, or paste a new key to replace it.',
	'field_base_url'      => 'Base URL',
	'field_base_url_hint' => 'Only needed for Ollama, OpenAI-compatible services, gateways or proxies. Leave empty to use the provider\'s standard address.',
	'field_active'        => 'Active',
	'field_active_hint'   => 'When off, none of this provider\'s models are used.',
	
	// Model fields
	'field_provider'        => 'Provider',
	'field_model_id'        => 'Model ID',
	'field_model_id_hint'   => 'The exact model name the provider expects, for example claude-opus-5-5, gpt-6-sol or gemini-3-pro. Copy it from the provider\'s documentation.',
	'field_label'           => 'Label',
	'field_label_hint'      => 'Optional friendly name shown in the model pickers.',
	'field_supports_vision' => 'Understands images',
	'field_supports_vision_hint' => 'Required for listing moderation and for drafting a listing from photos.',
	'field_supports_structured_output' => 'Returns structured JSON',
	'field_supports_structured_output_hint' => 'Required for moderation and quick post. Most current text models support it.',
	'field_supports_transcription' => 'Speech to text',
	'field_supports_transcription_hint' => 'Only for transcription models, used later for server-side voice input.',
	'field_input_price'     => 'Input price (USD per 1M tokens)',
	'field_output_price'    => 'Output price (USD per 1M tokens)',
	'field_price_hint'      => 'From the provider\'s pricing page. Used for the spend figures and the daily budget. Leave empty if unknown.',
	'field_options'         => 'Provider options (JSON)',
	'field_options_hint'    => 'Optional. Sent with every request to this model. Example for Claude: {"output_config": {"effort": "low"}}. Example for OpenAI: {"reasoning": {"effort": "low"}}.',
	'field_options_invalid' => 'The provider options must be a valid JSON object.',
	'field_model_active_hint' => 'When off, this model is not used, even if it is assigned to a feature.',
	'model_unique'          => 'This provider already has a model with this ID.',
	'model_retest_needed'   => 'The model settings changed. Run "Test model" again before using it.',
	
	// Settings > AI
	'setting_tab_general' => 'General',
	'setting_tab_models'  => 'Models',
	'setting_tab_budget'  => 'Budget',
	'setting_general_info' => 'Add your AI providers and their models first, test them, then assign a model to each feature on the Models tab.',
	'setting_usage_today' => 'Today: :calls AI calls, about $:cost.',
	'setting_enabled_label' => 'Enable AI features',
	'setting_enabled_hint'  => 'Master switch. When off, no AI request is sent, whatever else is configured.',
	'setting_models_info'   => 'Only active models that passed "Test model" are listed. Image features only list models that understand images. If the main model fails, the fallback model is tried once.',
	'setting_moderation_model_label' => 'Listing moderation model',
	'setting_moderation_model_hint'  => 'Reads new and edited listings, text and photos. Leave empty to keep moderation off.',
	'setting_quickpost_model_label'  => 'Quick post model',
	'setting_quickpost_model_hint'   => 'Drafts a listing from the user\'s photos, voice or text. Leave empty to keep quick post off.',
	'setting_fallback_model_label'   => 'Fallback model',
	'setting_fallback_model_hint'    => 'Optional. Used once when the main model errors, times out or is rate limited.',
	'setting_transcription_model_label' => 'Speech-to-text model',
	'setting_transcription_model_hint'  => 'Optional, for a later phase. Browser speech input works without it.',
	'setting_daily_budget_label' => 'Daily budget (USD)',
	'setting_daily_budget_hint'  => 'All AI features together. When today\'s spend reaches it, no more AI calls are made until midnight. 0 means no budget limit.',
	'setting_daily_call_limit_label' => 'Daily call limit',
	'setting_daily_call_limit_hint'  => 'Maximum AI calls per day across the site. Also the safety net for models without prices. 0 means no limit.',
	'setting_tab_moderation' => 'Moderation',
	'setting_moderation_info' => 'Each new or edited listing is read by the moderation model about 15 seconds after it is saved. Unverified listings and edits made in this admin panel are not sent. Run "php artisan ai:moderation-eval" on a model before relying on it.',
	'setting_moderation_mode_label' => 'Moderation mode',
	'setting_moderation_mode_off'    => 'Off',
	'setting_moderation_mode_shadow' => 'Shadow: check and log only, hide nothing',
	'setting_moderation_mode_hint'   => 'Shadow mode records what the AI would decide, so you can compare it with your own judgement before enforcing. Needs a queue worker in production.',
	'setting_moderation_user_limit_label' => 'Checks per user per day',
	'setting_moderation_user_limit_hint'  => 'Per member, or per IP address for guests. Above it, listings wait for an admin. 0 means no limit.',
	'setting_moderation_post_limit_label' => 'Checks per listing per day',
	'setting_moderation_post_limit_hint'  => 'Stops a listing that is edited again and again from using the budget. 0 means no limit.',
	
	// Moderation categories, shown to users and admins
	'moderation_category_illegal_drugs'           => 'Illegal drugs',
	'moderation_category_scam_fraud'              => 'Scam or fraud',
	'moderation_category_counterfeit_fake_docs'   => 'Counterfeit goods or fake documents',
	'moderation_category_stolen_goods'            => 'Stolen goods',
	'moderation_category_adult_sexual'            => 'Adult or sexual content',
	'moderation_category_child_safety'            => 'Child safety',
	'moderation_category_human_exploitation'      => 'Human exploitation',
	'moderation_category_hate_violence_extremism' => 'Hate, violence or extremism',
	'moderation_category_hacking_personal_data'   => 'Hacking or personal data',
	'moderation_category_weapons_explosives'      => 'Weapons or explosives',
	'moderation_category_protected_wildlife'      => 'Protected wildlife',
	'moderation_category_hazardous_regulated'     => 'Regulated or hazardous goods',
	'moderation_category_spam_misleading'         => 'Spam or misleading listing',
	'moderation_category_manipulation_attempt'    => 'Attempt to manipulate the review',
];
