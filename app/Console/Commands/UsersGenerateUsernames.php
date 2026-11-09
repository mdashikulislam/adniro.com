<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UsersGenerateUsernames extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'users:generate-usernames {--dry-run : List the usernames without saving them}';
	
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Generate the missing usernames of the users & of the posters of the listings published without account (key of the seller\'s public page: /user/{username}).';
	
	/**
	 * Execute the console command.
	 *
	 * @return int
	 */
	public function handle(): int
	{
		$isDryRun = (bool)$this->option('dry-run');
		$usersTable = (new User())->getTable();
		$done = 0;
		
		// Plain query builder: no model event, no notification & the "updated_at" column is left as it is
		DB::table($usersTable)
			->where(fn ($query) => $query->whereNull('username')->orWhere('username', ''))
			->orderBy('id')
			->select(['id', 'name'])
			->chunkById(500, function ($users) use ($usersTable, $isDryRun, &$done) {
				foreach ($users as $user) {
					$username = User::generateUsername($user->name, (int)$user->id);
					
					if ($isDryRun) {
						$this->line($user->id . ' => ' . $username);
					} else {
						DB::table($usersTable)->where('id', $user->id)->update(['username' => $username]);
					}
					$done++;
				}
			});
		
		$this->info($done . ' user username(s) ' . ($isDryRun ? 'to generate.' : 'generated.'));
		
		$this->generateGuestUsernames($isDryRun);
		
		return self::SUCCESS;
	}
	
	/**
	 * Listings published without account: one username per poster
	 * (same contact name with the same email address, or the same phone number when there is no email address).
	 *
	 * @param bool $isDryRun
	 * @return void
	 */
	private function generateGuestUsernames(bool $isDryRun): void
	{
		$postsTable = (new Post())->getTable();
		$posters = 0;
		$listings = 0;
		
		// In a dry run nothing is saved: remember the posters already seen in this run
		$seen = [];
		
		DB::table($postsTable)
			->whereNull('user_id')
			->where(fn ($query) => $query->whereNull('guest_username')->orWhere('guest_username', ''))
			->orderBy('id')
			->select(['id', 'email', 'phone', 'contact_name'])
			->chunkById(500, function ($posts) use ($postsTable, $isDryRun, &$posters, &$listings, &$seen) {
				// The listings of a poster are all updated with the first one of them:
				// skip those already done earlier in this chunk
				$doneInChunk = [];
				
				foreach ($posts as $post) {
					$email = trim((string)$post->email);
					$phone = trim((string)$post->phone);
					$contactName = trim((string)$post->contact_name);
					$contact = match (true) {
						($email !== '') => ['email', $email],
						($phone !== '') => ['phone', $phone],
						default => null,
					};
					$contactKey = !empty($contact) ? mb_strtolower($contact[0] . ':' . $contact[1] . '|' . $contactName) : null;
					
					if (!empty($contactKey) && isset($doneInChunk[$contactKey])) {
						continue;
					}
					
					if ($isDryRun) {
						$listings++;
						if (!empty($contactKey) && isset($seen[$contactKey])) {
							continue;
						}
						$username = Post::guestUsernameFor($email, $phone, $contactName);
						if (!empty($contactKey)) {
							$seen[$contactKey] = true;
						}
						$this->line('listing ' . $post->id . ' (' . $post->contact_name . ') => ' . $username);
						$posters++;
						continue;
					}
					
					$username = Post::guestUsernameFor($email, $phone, $contactName);
					
					// Plain query builder: no model event & the "updated_at" column is left as it is
					$query = DB::table($postsTable)
						->whereNull('user_id')
						->where(fn ($query) => $query->whereNull('guest_username')->orWhere('guest_username', ''));
					if (!empty($contact)) {
						$query->where($contact[0], $contact[1])->where('contact_name', $contactName);
						$doneInChunk[$contactKey] = true;
					} else {
						$query->where('id', $post->id);
					}
					$listings += $query->update(['guest_username' => $username]);
					$posters++;
				}
			});
		
		$this->info(
			$posters . ' guest poster username(s) ' . ($isDryRun ? 'to generate' : 'generated')
			. ' for ' . $listings . ' listing(s) published without account.'
		);
	}
}
