{{-- checkbox --}}
@php
	use App\Http\Controllers\Web\Admin\UserController;
	use App\Models\Permission;
	use App\Models\User;
	
	$isDeleteAccessAllowed = (isset($xPanel) && !$xPanel->hasAccess('delete'));
	// Security for Admin Users
	$isOnUserControllerPage = routeActionHas(UserController::class);
	$userHasPermission = (isset($entry) && $entry instanceof User && $entry->can(Permission::getStaffPermissions()));
	
	$disabled = '';
	if (
		$isDeleteAccessAllowed
		|| ($isOnUserControllerPage && $userHasPermission)
	) {
		$disabled = 'disabled="disabled"';
	}
@endphp
<span class="dt-checkboxes-cell">
	<input name="entryId[]" type="checkbox" value="{{ $entry->{$column['name']} }}" class="dt-checkboxes" {!! $disabled !!}>
</span>
