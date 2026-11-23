<?php

namespace Tests\Feature\Chat;

use App\Events\Chat\ChatEvent;
use App\Models\Chat\ChatMessage;
use App\Models\Chat\ChatThread;
use App\Models\Chat\UserBlock;
use App\Notifications\ChatReported;
use App\Services\Chat\ChatMessages;
use App\Services\Chat\ChatUnread;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

/*
 * Offers in a chat (adniro): a price proposed for a listing, accepted or declined by the other person.
 */
class ChatOfferTest extends ChatTestCase
{
	public function test_a_buyer_makes_an_offer_on_a_listing_and_the_seller_is_told(): void
	{
		// Arrange: no conversation yet
		Event::fake([ChatEvent::class]);

		// Act
		$response = $this->actingAs($this->buyer)->postJson($this->offerUrl(), ['amount' => '120']);

		// Assert
		$response->assertCreated()
			->assertJsonPath('message.type', 'offer')
			->assertJsonPath('message.body', '')
			->assertJsonPath('message.offer.amount', '$120')
			->assertJsonPath('message.offer.status', 'pending')
			->assertJsonPath('message.offer.post_id', (int)$this->listing->id)
			->assertJsonPath('message.post.title', 'Grey corner sofa')
			->assertJsonPath('thread.last.type', 'offer')
			->assertJsonPath('thread.last.text', '$120')
			->assertJsonPath('thread.post.can_offer', true)
			->assertJsonPath('thread.post.owner_id', self::SELLER_ID)
			->assertJsonPath('thread.post.currency', '$');
		$this->assertSame(1, ChatUnread::total(self::SELLER_ID));
		foreach ([self::BUYER_ID, self::SELLER_ID] as $memberId) {
			Event::assertDispatched(ChatEvent::class, fn (ChatEvent $event) => $event->name === ChatEvent::MESSAGE
				&& $event->userId === $memberId && $event->payload['message']['offer']['amount'] === '$120');
		}
	}

	public function test_an_amount_keeps_its_cents_and_a_retry_does_not_send_twice(): void
	{
		// Arrange
		$payload = ['amount' => '1200.50', 'client_uuid' => '3f0e6a52-5a8b-4c59-9d53-0b1f5e9f7a10'];

		// Act
		$first = $this->actingAs($this->buyer)->postJson($this->offerUrl(), $payload);
		$again = $this->actingAs($this->buyer)->postJson($this->offerUrl(), $payload);

		// Assert
		$first->assertCreated()->assertJsonPath('message.offer.amount', '$1,200.50');
		$again->assertOk()->assertJsonPath('message.id', $first->json('message.id'))->assertJsonPath('message.offer.status', 'pending');
		$this->assertSame(1, DB::table('threads_messages')->where('type', 'offer')->count());
	}

	public function test_a_newer_offer_replaces_the_open_one_and_the_seller_can_answer_with_their_own_price(): void
	{
		// Arrange
		$first = $this->offer('100');

		// Act: the buyer raises, then the seller names their own price in that conversation
		$second = $this->offer('110');
		$counter = $this->actingAs($this->seller)->postJson($this->offerUrl(), ['amount' => '140', 'thread' => $first['thread_id']]);

		// Assert
		$counter->assertCreated()->assertJsonPath('message.user_id', self::SELLER_ID)->assertJsonPath('message.offer.status', 'pending');
		$this->assertSame('replaced', $this->statusOf($first['id']));
		$this->assertSame('replaced', $this->statusOf($second['id']));
		$this->assertSame(2, ChatUnread::total(self::SELLER_ID));
		$this->assertSame(1, ChatUnread::total(self::BUYER_ID));
		// The buyer accepts the seller's price
		$this->actingAs($this->buyer)->postJson($this->answerUrl($first['thread_id'], (int)$counter->json('message.id')), ['answer' => 'accept'])
			->assertCreated()->assertJsonPath('message.offer.status', 'accepted');
	}

	public function test_the_seller_accepts_an_offer_once_and_the_buyer_is_told(): void
	{
		// Arrange
		$offer = $this->offer('120');
		Event::fake([ChatEvent::class]);
		$url = $this->answerUrl($offer['thread_id'], $offer['id']);

		// Act
		$response = $this->actingAs($this->seller)->postJson($url, ['answer' => 'accept']);

		// Assert: the answer is a message of its own, and the offer is closed
		$response->assertCreated()
			->assertJsonPath('message.type', 'offer_answer')
			->assertJsonPath('message.user_id', self::SELLER_ID)
			->assertJsonPath('message.offer.status', 'accepted')
			->assertJsonPath('message.offer.amount', '$120')
			->assertJsonPath('message.offer.offer_id', $offer['id'])
			->assertJsonPath('message.reply_to', null)
			->assertJsonPath('thread.last.type', 'offer_accepted')
			->assertJsonPath('thread.last.text', '$120');
		$this->assertSame('accepted', $this->statusOf($offer['id']));
		$this->assertSame(1, ChatUnread::total(self::BUYER_ID));
		Event::assertDispatched(ChatEvent::class, fn (ChatEvent $event) => $event->name === ChatEvent::MESSAGE
			&& $event->userId === self::BUYER_ID && $event->payload['message']['offer']['offer_id'] === $offer['id']);
		$this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $offer['thread_id'])
			->assertJsonPath('messages.0.offer.status', 'accepted')
			->assertJsonPath('messages.1.type', 'offer_answer');

		// Act & Assert: it cannot be answered again, either way
		$this->actingAs($this->seller)->postJson($url, ['answer' => 'decline'])->assertStatus(409);
		$this->assertSame('accepted', $this->statusOf($offer['id']));
	}

	public function test_only_the_person_who_received_an_offer_can_answer_it(): void
	{
		// Arrange
		$offer = $this->offer('120');
		$url = $this->answerUrl($offer['thread_id'], $offer['id']);

		// Act & Assert
		$this->actingAs($this->buyer)->postJson($url, ['answer' => 'accept'])->assertNotFound();
		$this->actingAs($this->outsider)->postJson($url, ['answer' => 'accept'])->assertNotFound();
		$this->actingAs($this->seller)->postJson($url, ['answer' => 'maybe'])->assertStatus(422);
		$this->assertSame('pending', $this->statusOf($offer['id']));

		// A plain message is not an offer
		$textId = (int)$this->actingAs($this->buyer)->postJson('/chat/api/threads/' . $offer['thread_id'] . '/messages', ['body' => 'Deal?'])->json('message.id');
		$this->actingAs($this->seller)->postJson($this->answerUrl($offer['thread_id'], $textId), ['answer' => 'accept'])->assertNotFound();
	}

	public function test_an_offer_on_a_listing_that_went_offline_can_be_declined_but_not_accepted(): void
	{
		// Arrange
		$offer = $this->offer('120');
		DB::table('posts')->where('id', $this->listing->id)->update(['archived_at' => now()]);
		$url = $this->answerUrl($offer['thread_id'], $offer['id']);

		// Act & Assert
		$this->actingAs($this->seller)->postJson($url, ['answer' => 'accept'])->assertStatus(422);
		$this->assertSame('pending', $this->statusOf($offer['id']));
		$this->actingAs($this->seller)->postJson($url, ['answer' => 'decline'])
			->assertCreated()->assertJsonPath('message.offer.status', 'declined')->assertJsonPath('thread.last.type', 'offer_declined');
	}

	public function test_an_offer_can_be_withdrawn_until_it_is_answered(): void
	{
		// Arrange
		$withdrawn = $this->offer('100');
		$answered = $this->offer('110');
		$messages = '/chat/api/threads/' . $answered['thread_id'] . '/messages/';

		// Act: the buyer takes the open offer back; the seller accepts the other one
		$this->actingAs($this->seller)->postJson($this->answerUrl($answered['thread_id'], $answered['id']), ['answer' => 'accept'])->assertCreated();
		$answerId = (int)DB::table('threads_messages')->where('type', 'offer_answer')->value('id');

		// Assert: a replaced or answered offer stays; so does an answer
		$this->actingAs($this->buyer)->deleteJson($messages . $withdrawn['id'])->assertStatus(422);
		$this->actingAs($this->buyer)->deleteJson($messages . $answered['id'])->assertStatus(422);
		$this->actingAs($this->seller)->deleteJson($messages . $answerId)->assertStatus(422);

		// An open offer can be taken back, and is then closed to the other person
		$open = $this->offer('130');
		$this->actingAs($this->buyer)->deleteJson($messages . $open['id'])->assertOk()->assertJsonPath('message.unsent', true)->assertJsonPath('message.offer', null);
		$this->actingAs($this->seller)->postJson($this->answerUrl($open['thread_id'], $open['id']), ['answer' => 'accept'])->assertStatus(409);
	}

	public function test_offers_are_refused_where_they_make_no_sense(): void
	{
		// Arrange
		$free = $this->stockListing('Free moving boxes', ['user_id' => self::SELLER_ID, 'price' => 0]);
		$jobCategoryId = (int)DB::table('categories')->where('type', 'job-offer')->whereNotNull('parent_id')->value('id');
		$job = $this->stockListing('Delivery driver wanted', ['user_id' => self::SELLER_ID, 'category_id' => $jobCategoryId]);

		// Act & Assert: nothing to bargain over
		$this->actingAs($this->buyer)->postJson($this->offerUrl((int)$free->id), ['amount' => '5'])->assertStatus(422);
		$this->actingAs($this->buyer)->postJson($this->offerUrl((int)$job->id), ['amount' => '5'])->assertStatus(422);
		// Not a price
		foreach (['', 'abc', '0', '-5', '1.234', '1e3', '12345678901'] as $amount) {
			$this->travel(1)->minutes(); // refused offers count towards the limit too
			$this->actingAs($this->buyer)->postJson($this->offerUrl(), ['amount' => $amount])->assertStatus(422);
		}
		// The owner has nobody to offer to without a conversation, and cannot use someone else's
		$this->actingAs($this->seller)->postJson($this->offerUrl(), ['amount' => '100'])->assertNotFound();
		// Guests
		auth()->logout();
		$this->postJson($this->offerUrl(), ['amount' => '100'])->assertUnauthorized();
		$this->assertSame(0, DB::table('threads_messages')->count());
	}

	public function test_offers_stop_across_a_block_and_when_an_admin_turns_them_off(): void
	{
		// Arrange
		$offer = $this->offer('120');
		UserBlock::query()->create(['blocker_id' => self::SELLER_ID, 'blocked_id' => self::BUYER_ID]);

		// Act & Assert: blocked
		$this->actingAs($this->buyer)->postJson($this->offerUrl(), ['amount' => '125'])->assertForbidden();
		$this->actingAs($this->seller)->postJson($this->answerUrl($offer['thread_id'], $offer['id']), ['answer' => 'accept'])->assertForbidden();
		$this->actingAs($this->seller)->postJson($this->offerUrl(), ['amount' => '140', 'thread' => $offer['thread_id']])->assertForbidden();

		// Act & Assert: turned off in Admin > Settings > Chat
		UserBlock::query()->delete();
		$this->travel(1)->minutes();
		config(['settings.chat.offers_enabled' => '0']);
		$this->actingAs($this->buyer)->postJson($this->offerUrl(), ['amount' => '125'])->assertStatus(422);
		$this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $offer['thread_id'])->assertJsonPath('thread.post.can_offer', false);
		$this->assertSame(1, DB::table('threads_messages')->where('type', ChatMessage::TYPE_OFFER)->count());
	}

	public function test_offers_have_their_own_limit(): void
	{
		// Act: a new account sends offers one after the other
		$statuses = [];
		foreach (['100', '105', '110', '115'] as $amount) {
			$statuses[] = $this->actingAs($this->buyer)->postJson($this->offerUrl(), ['amount' => $amount])->status();
		}

		// Assert
		$this->assertSame([201, 201, 201, 429], $statuses);
	}

	public function test_an_offer_can_be_quoted_and_survives_the_listing_it_was_about(): void
	{
		// Arrange
		$offer = $this->offer('120');

		// Act: the seller replies to the offer with words, then deletes the listing
		$reply = $this->actingAs($this->seller)->postJson('/chat/api/threads/' . $offer['thread_id'] . '/messages', ['body' => 'Too low, sorry.', 'reply_to' => $offer['id']]);
		$this->listing->delete();

		// Assert
		$reply->assertCreated()->assertJsonPath('message.reply_to.type', 'offer')->assertJsonPath('message.reply_to.text', '$120');
		$this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $offer['thread_id'])
			->assertOk()
			->assertJsonPath('messages.0.type', 'offer')
			->assertJsonPath('messages.0.offer.amount', '$120')
			->assertJsonPath('messages.0.offer.post_id', 0)
			->assertJsonPath('messages.0.post', null);
	}

	public function test_a_seller_names_a_price_only_for_a_listing_the_conversation_is_about(): void
	{
		// Arrange: the two talk about the sofa; the seller also has a table nobody asked about
		$offer = $this->offer('120');
		$table = $this->stockListing('Oak dining table', ['user_id' => self::SELLER_ID]);

		// Act & Assert
		$this->actingAs($this->seller)->postJson($this->offerUrl((int)$table->id), ['amount' => '90', 'thread' => $offer['thread_id']])->assertStatus(422);

		// Once the buyer has asked about the table, the seller can
		$this->travel(1)->minutes();
		$this->actingAs($this->buyer)->postJson('/chat/listings/' . $table->id, ['body' => 'And the table?'])->assertCreated();
		$this->actingAs($this->seller)->postJson($this->offerUrl((int)$table->id), ['amount' => '90', 'thread' => $offer['thread_id']])->assertCreated();
	}

	public function test_an_offer_deleted_with_the_conversation_can_no_longer_be_answered(): void
	{
		// Arrange: the seller deletes the conversation from their list
		$offer = $this->offer('120');
		$this->actingAs($this->seller)->deleteJson('/chat/api/threads/' . $offer['thread_id'])->assertOk();

		// Act & Assert
		$this->actingAs($this->seller)->postJson($this->answerUrl($offer['thread_id'], $offer['id']), ['answer' => 'accept'])->assertNotFound();
		$this->assertSame('pending', $this->statusOf($offer['id']));
	}

	public function test_an_offer_answered_at_the_same_moment_is_not_taken_back(): void
	{
		// Arrange: the buyer's request to take the offer back was checked just before the seller accepted
		$offer = $this->offer('120');
		$thread = ChatThread::query()->with('participants')->findOrFail($offer['thread_id']);
		$checkedBefore = ChatMessage::query()->findOrFail($offer['id']);
		$this->actingAs($this->seller)->postJson($this->answerUrl($offer['thread_id'], $offer['id']), ['answer' => 'accept'])->assertCreated();

		// Act
		$result = ChatMessages::unsend($thread, $checkedBefore);

		// Assert: the answer stands
		$this->assertFalse($result->isUnsent());
		$this->assertNull(DB::table('threads_messages')->where('id', $offer['id'])->value('unsent_at'));
		$this->assertSame('accepted', $this->statusOf($offer['id']));
	}

	public function test_an_offer_to_someone_new_counts_as_starting_a_conversation(): void
	{
		// Arrange: three sellers the buyer has never talked to
		$this->stockMember(84);
		$urls = [
			$this->offerUrl(),
			$this->offerUrl((int)$this->stockListing('Oak dining table', ['user_id' => self::OUTSIDER_ID])->id),
			$this->offerUrl((int)$this->stockListing('Floor lamp', ['user_id' => 84])->id),
		];

		// Act: a new account may start two conversations a minute
		$statuses = array_map(fn (string $url) => $this->actingAs($this->buyer)->postJson($url, ['amount' => '50'])->status(), $urls);

		// Assert
		$this->assertSame([201, 201, 429], $statuses);
		$this->assertSame(2, DB::table('threads')->count());
	}

	public function test_the_report_email_says_what_an_offer_was(): void
	{
		// Arrange
		Notification::fake();
		config(['settings.app.email' => 'admin@example.test']);
		$offer = $this->offer('120');
		$this->actingAs($this->seller)->postJson($this->answerUrl($offer['thread_id'], $offer['id']), ['answer' => 'decline'])->assertCreated();

		// Act
		$this->actingAs($this->seller)->postJson('/chat/api/threads/' . $offer['thread_id'] . '/report', ['reason' => 'scam'])->assertSuccessful();

		// Assert
		Notification::assertSentTo(new AnonymousNotifiable(), ChatReported::class, function (ChatReported $mail, array $channels, AnonymousNotifiable $notifiable) {
			$lines = implode(' ', $mail->toMail($notifiable)->introLines);

			return str_contains($lines, '[Offer: $120]') && str_contains($lines, '[Offer declined: $120]');
		});
	}

	/** @return array{id: int, thread_id: int} the buyer's offer on the listing */
	private function offer(string $amount): array
	{
		$this->travel(1)->minutes(); // a new account may send few offers a minute
		$response = $this->actingAs($this->buyer)->postJson($this->offerUrl(), ['amount' => $amount])->assertCreated();

		return ['id' => (int)$response->json('message.id'), 'thread_id' => (int)$response->json('thread.id')];
	}

	private function offerUrl(?int $postId = null): string
	{
		return '/chat/listings/' . ($postId ?? $this->listing->id) . '/offers';
	}

	private function answerUrl(int $threadId, int $messageId): string
	{
		return '/chat/api/threads/' . $threadId . '/offers/' . $messageId;
	}

	private function statusOf(int $messageId): string
	{
		return (string)DB::table('threads_messages')->where('id', $messageId)->value('offer_status');
	}
}
