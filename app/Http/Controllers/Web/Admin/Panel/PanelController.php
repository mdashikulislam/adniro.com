<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

namespace App\Http\Controllers\Web\Admin\Panel;

use App\Http\Controllers\Web\Admin\Controller;
use App\Http\Controllers\Web\Admin\Panel\Library\Panel;
use App\Http\Controllers\Web\Admin\Panel\Operations\BulkActionsOperation;
use App\Http\Controllers\Web\Admin\Panel\Operations\CreateOperation;
use App\Http\Controllers\Web\Admin\Panel\Operations\DeleteOperation;
use App\Http\Controllers\Web\Admin\Panel\Operations\ListOperation;
use App\Http\Controllers\Web\Admin\Panel\Operations\ReorderOperation;
use App\Http\Controllers\Web\Admin\Panel\Operations\SaveActionsOperation;
use App\Http\Controllers\Web\Admin\Panel\Operations\ShowOperation;
use App\Http\Controllers\Web\Admin\Panel\Operations\UpdateOperation;

class PanelController extends Controller
{
	use CreateOperation, DeleteOperation, ListOperation, ShowOperation, UpdateOperation;
	use BulkActionsOperation, ReorderOperation, SaveActionsOperation;
	
	public ?Panel $xPanel = null;
	public array $data = [];
	
	protected bool $onIndexPage = false;
	protected bool $onCreatePage = false;
	protected bool $onEditPage = false;
	protected bool $onDestroyAction = false;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->onIndexPage = routeActionHas(['@index', '@search']);
		$this->onCreatePage = routeActionHas(['@create', '@store']);
		$this->onEditPage = routeActionHas(['@edit', '@update']);
		$this->onDestroyAction = routeActionHas(['@destroy']);
		
		if (!$this->xPanel) {
			$this->xPanel = new Panel();
			$this->request = request();
			$this->xPanel->request = $this->request;
			$this->setup();
		}
	}
	
	/**
	 * Allow custom configuration options for a CRUD controller.
	 */
	public function setup()
	{
	}
}
