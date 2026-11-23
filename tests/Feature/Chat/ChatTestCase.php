<?php

namespace Tests\Feature\Chat;

use App\Models\Chat\ChatThread;
use App\Models\Post;
use App\Models\User;
use App\Services\Chat\ChatConversations;
use App\Services\Chat\ChatMessages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * Live chat tests (adniro): a seller with a listing, a buyer, and a member who is in no conversation.
 */
abstract class ChatTestCase extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	protected const SELLER_ID = 81;
	protected const BUYER_ID = 82;
	protected const OUTSIDER_ID = 83;

	protected User $seller;
	protected User $buyer;
	protected User $outsider;
	protected Post $listing;

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();
		$this->seller = $this->stockMember(self::SELLER_ID);
		$this->buyer = $this->stockMember(self::BUYER_ID);
		$this->outsider = $this->stockMember(self::OUTSIDER_ID);
		$this->listing = $this->stockListing('Grey corner sofa', ['user_id' => self::SELLER_ID]);
	}

	/** A conversation the buyer started about the listing: the buyer and the seller write in turn. */
	protected function conversation(array $bodies = ['Is this still available?']): ChatThread
	{
		$thread = ChatConversations::findOrCreate($this->listing, $this->buyer);
		foreach ($bodies as $index => $body) {
			$senderId = $index % 2 === 0 ? self::BUYER_ID : self::SELLER_ID;
			$thread = $thread->fresh('participants');
			if (!empty($thread->last_message_id)) {
				// As the open chat does: what is on screen is marked read before a reply is written
				ChatMessages::markRead($thread, $senderId, (int)$thread->last_message_id);
			}
			// The first message is sent from the listing ("Chat with seller")
			ChatMessages::send($thread, $senderId, $body, null, null, $index === 0 ? $this->listing : null);
		}

		return $thread->fresh('participants');
	}

	/** A conversation's page: its coded address (ChatLink). */
	protected function chatPage(ChatThread $thread): string
	{
		return \App\Services\Chat\ChatLink::url((int)$thread->id);
	}

	protected function unreadOf(int $threadId, int $userId): int
	{
		return (int)\DB::table('threads_participants')->where('thread_id', $threadId)->where('user_id', $userId)->value('unread_count');
	}
}
