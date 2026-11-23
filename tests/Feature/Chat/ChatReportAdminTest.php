<?php

namespace Tests\Feature\Chat;

use App\Models\Chat\ThreadReport;
use App\Models\Permission;
use App\Models\User;
use App\Notifications\ChatReported;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/*
 * Admin > Users > Chat reports (adniro): the list, one report with its conversation, closing and reopening.
 */
class ChatReportAdminTest extends ChatTestCase
{
	private User $admin;

	protected function setUp(): void
	{
		parent::setUp();
		$this->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

		$permissions = array_merge(Permission::getSuperAdminPermissions(), Permission::getStaffPermissions());
		foreach ($permissions as $name) {
			Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
		}
		app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
		$this->admin = $this->stockMember(90);
		$this->admin->givePermissionTo($permissions);
	}

	/** The buyer reports the seller, who asked for a deposit and then took the message back. */
	private function reportedConversation(): ThreadReport
	{
		$thread = $this->conversation(['Is this still available?', 'Pay me a deposit by gift card first']);
		$messageId = (int)DB::table('threads_messages')->where('thread_id', $thread->id)->max('id');
		$this->actingAs($this->seller)->deleteJson('/chat/api/threads/' . $thread->id . '/messages/' . $messageId)->assertOk();
		$this->actingAs($this->buyer)->postJson('/chat/api/threads/' . $thread->id . '/report', ['reason' => 'scam', 'note' => 'Asked for gift cards.'])->assertCreated();

		return ThreadReport::query()->firstOrFail();
	}

	public function test_members_cannot_open_the_reports(): void
	{
		// Arrange
		$report = $this->reportedConversation();

		// Act & Assert
		foreach (['/admin/chat-reports', '/admin/chat-reports/' . $report->id] as $url) {
			$this->assertContains($this->actingAs($this->seller)->get($url)->status(), [302, 403, 404]);
		}
		$this->assertContains($this->actingAs($this->seller)->post('/admin/chat-reports/' . $report->id . '/close')->status(), [302, 403, 404]);
		$this->assertSame(ThreadReport::STATUS_OPEN, $report->fresh()->status);
	}

	public function test_an_admin_sees_the_open_reports_with_names(): void
	{
		// Arrange
		$report = $this->reportedConversation();

		// Act
		$page = $this->actingAs($this->admin)->get('/admin/chat-reports');

		// Assert
		$page->assertOk()->assertSee('Chat reports')->assertSee('Scam or fraud')
			->assertSee('Member ' . self::SELLER_ID)->assertSee('Member ' . self::BUYER_ID)
			->assertSee('admin/chat-reports/' . $report->id, false);
	}

	public function test_an_admin_reads_the_whole_conversation_even_what_was_taken_back(): void
	{
		// Arrange
		$report = $this->reportedConversation();

		// Act
		$page = $this->actingAs($this->admin)->get('/admin/chat-reports/' . $report->id);

		// Assert
		$page->assertOk()
			->assertSee('Is this still available?')
			->assertSee('Pay me a deposit by gift card first')
			->assertSee('The sender deleted this message')
			->assertSee('Asked for gift cards.')
			->assertSee('users/' . self::SELLER_ID . '/edit', false);
	}

	public function test_an_admin_closes_a_report_and_can_reopen_it(): void
	{
		// Arrange
		$report = $this->reportedConversation();

		// Act & Assert: close
		$this->actingAs($this->admin)->post('/admin/chat-reports/' . $report->id . '/close')->assertRedirect();
		$closed = $report->fresh();
		$this->assertSame(ThreadReport::STATUS_CLOSED, $closed->status);
		$this->assertSame((int)$this->admin->id, (int)$closed->closed_by);
		$this->assertNotNull($closed->closed_at);
		$this->actingAs($this->admin)->get('/admin/chat-reports?status=closed')->assertOk()->assertSee('admin/chat-reports/' . $report->id, false);

		// Act & Assert: reopen
		$this->actingAs($this->admin)->post('/admin/chat-reports/' . $report->id . '/reopen')->assertRedirect();
		$this->assertSame(ThreadReport::STATUS_OPEN, $report->fresh()->status);
		$this->assertNull($report->fresh()->closed_by);
	}

	public function test_the_report_email_names_both_people_and_links_to_the_report(): void
	{
		// Arrange
		Notification::fake();
		config(['settings.app.email' => 'admin@example.test']);

		// Act
		$report = $this->reportedConversation();

		// Assert
		Notification::assertSentTo(new AnonymousNotifiable(), ChatReported::class, function (ChatReported $mail, array $channels, AnonymousNotifiable $notifiable) use ($report) {
			$message = $mail->toMail($notifiable);
			$lines = implode(' ', $message->introLines);

			return str_contains($lines, 'Member ' . self::BUYER_ID . ' #' . self::BUYER_ID . ' reported Member ' . self::SELLER_ID . ' #' . self::SELLER_ID)
				&& str_contains((string)$message->actionUrl, 'chat-reports/' . $report->id);
		});
	}
}
