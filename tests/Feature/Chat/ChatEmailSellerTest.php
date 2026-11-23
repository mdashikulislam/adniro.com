<?php

namespace Tests\Feature\Chat;

use App\Models\Post;
use App\Notifications\ListingEnquiry;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/*
 * "Email the seller" (adniro): a listing posted without a member account cannot be chatted with, so a member's
 * message is emailed to the listing's address.
 */
class ChatEmailSellerTest extends ChatTestCase
{
	private const QUESTION = 'Is the bike still available this week?';

	private Post $guestListing;

	protected function setUp(): void
	{
		parent::setUp();
		$this->guestListing = $this->stockListing('Blue city bike', ['user_id' => null, 'email' => 'owner@example.test']);
	}

	public function test_a_listing_without_an_account_offers_email_instead_of_chat(): void
	{
		// Act
		$page = $this->actingAs($this->buyer)->get(urlGen()->post($this->guestListing));

		// Assert
		$page->assertOk()->assertSee('Email the seller')->assertDontSee('Chat with seller')
			->assertSee('id="adnEmailSeller"', false)
			->assertDontSee('owner@example.test');
	}

	public function test_a_listing_with_an_account_keeps_chat_and_has_no_email_form(): void
	{
		// Act
		$page = $this->actingAs($this->buyer)->get(urlGen()->post($this->listing));

		// Assert
		$page->assertOk()->assertSee('Chat with seller')->assertDontSee('Email the seller')->assertDontSee('id="adnEmailSeller"', false);
	}

	public function test_a_members_message_is_emailed_to_the_listing_with_their_address_to_reply_to(): void
	{
		// Arrange
		Notification::fake();

		// Act
		$response = $this->actingAs($this->buyer)->postJson('/chat/email-seller/' . $this->guestListing->id, [
			'message' => self::QUESTION . "\nCan you do <500 for it?\n[Click here](https://evil.example) <b>now</b>",
		]);

		// Assert
		$response->assertCreated()->assertJsonPath('sent', true);
		Notification::assertSentTo(new AnonymousNotifiable(), ListingEnquiry::class, function (ListingEnquiry $mail, array $channels, AnonymousNotifiable $notifiable) {
			$message = $mail->toMail($notifiable);
			$lines = implode(' ', $message->introLines);

			return $notifiable->routes['mail'] === 'owner@example.test'
				&& $message->replyTo === [[$this->buyer->email, 'Member ' . self::BUYER_ID]]
				&& str_contains($message->subject, 'Blue city bike')
				&& str_contains($lines, self::QUESTION . '<br> Can you do &lt;500 for it?<br> ')
				&& str_contains($lines, '&lt;b&gt;now&lt;/b&gt;')
				&& !str_contains($lines, '](')
				&& !str_contains($lines, 'owner@example.test');
		});
		$this->assertSame(0, DB::table('threads')->count(), 'Nothing is stored: there is nobody to chat with.');
	}

	public function test_only_a_signed_in_member_can_email_and_only_a_listing_without_an_account(): void
	{
		// Arrange
		Notification::fake();
		$url = '/chat/email-seller/';

		// Act & Assert
		$this->postJson($url . $this->guestListing->id, ['message' => self::QUESTION])->assertUnauthorized();
		$this->actingAs($this->buyer)->postJson($url . $this->listing->id, ['message' => self::QUESTION])->assertForbidden();
		$this->travel(2)->minutes();
		$this->actingAs($this->buyer)->postJson($url . $this->guestListing->id, ['message' => 'Hi'])->assertStatus(422);
		$this->travel(2)->minutes();
		$this->actingAs($this->buyer)->postJson($url . '999999', ['message' => self::QUESTION])->assertNotFound();
		Notification::assertNothingSent();
	}

	public function test_a_listing_can_be_emailed_only_a_couple_of_times_a_day_by_the_same_member(): void
	{
		// Arrange
		Notification::fake();
		$this->travel(2)->days(); // the members are no longer on their first day, which has tighter limits
		$url = '/chat/email-seller/' . $this->guestListing->id;

		// Act
		$first = $this->actingAs($this->buyer)->postJson($url, ['message' => self::QUESTION]);
		$this->travel(2)->minutes();
		$second = $this->actingAs($this->buyer)->postJson($url, ['message' => self::QUESTION]);
		$this->travel(2)->minutes();
		$third = $this->actingAs($this->buyer)->postJson($url, ['message' => self::QUESTION]);

		// Assert
		$first->assertCreated();
		$second->assertCreated();
		$third->assertStatus(429);
		Notification::assertSentTimes(ListingEnquiry::class, 2);
	}

	public function test_several_accounts_cannot_flood_one_listing_with_emails(): void
	{
		// Arrange: five members have each used their two emails to this listing today
		Notification::fake();
		$url = '/chat/email-seller/' . $this->guestListing->id;
		$senders = array_map(fn (int $id) => $this->stockMember($id), [201, 202, 203, 204, 205, 206]);
		$this->travel(2)->days();
		foreach (array_slice($senders, 0, 5) as $sender) {
			$this->actingAs($sender)->postJson($url, ['message' => self::QUESTION])->assertCreated();
			$this->actingAs($sender)->postJson($url, ['message' => self::QUESTION])->assertCreated();
		}

		// Act: a sixth member writes to the same listing
		$response = $this->actingAs($senders[5])->postJson($url, ['message' => self::QUESTION]);

		// Assert
		$response->assertStatus(429);
		Notification::assertSentTimes(ListingEnquiry::class, 10);
	}
}
