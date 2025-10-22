<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library;

use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Access;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\AutoFocus;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\AutoSet;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Buttons;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Columns;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Create;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Delete;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Errors;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\FakeColumns;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\FakeFields;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Fields;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Filters;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Nested;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\PanelExtended;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Query;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Read;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Reorder;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\RequiredFields;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Search;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Tabs;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Update;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Views;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Schema;

class Panel
{
	use PanelExtended, Nested;
	use Read, Create, Update, Delete;
	use Search, Filters, Query;
	use Columns, Fields, Buttons, AutoSet, AutoFocus, FakeColumns, FakeFields;
	use Access, Reorder;
	use Tabs, Errors, Views, RequiredFields;
	
	// ----------------
	// xPanel variables
	// ----------------
	// These variables are passed to the PANEL views, inside the $panel variable.
	// All variables are public, so they can be modified from your EntityController.
	// All functions and methods are also public, so they can be used in your EntityController to modify these variables.
	
	public Model $model; // what's the namespace for your entity's model
	public string $route = ''; // what route have you defined for your entity? used for links.
	public array $parameters = [];
	public string $entityName = 'entry'; // what name will show up on the buttons, in singular (ex: Add entity)
	public string $entityNamePlural = 'entries'; // what name will show up on the buttons, in plural (ex: Delete 5 entities)
	
	public Request $request;
	
	public array $access = ['list', 'create', 'update', 'delete', /*'show'*/];
	
	public bool $reorder = false;
	public $reorderLabel = false;
	public int $reorderMaxLevel = 3;
	
	public bool $detailsRow = false;
	public bool $exportButtons = false;
	public bool $hideSearchBar = false;
	
	public array $columns = []; // Define the columns for the table view as an array;
	public array $createFields = []; // Define the fields for the "Add new entry" view as an array;
	public array $updateFields = []; // Define the fields for the "Edit entry" view as an array;
	
	public Builder $query;
	public $entry = null;
	public $buttons;
	public array $dbColumnTypes = [];
	public int|bool $defaultPageLength = false;
	
	// TONE FIELDS - TODO: find out what he did with them, replicate or delete
	public array $sort = [];
	
	public bool $disableSyncPivot = false;
	
	// The following methods are used in CrudController or your EntityCrudController to manipulate the variables above.
	
	public function __construct()
	{
		$this->setErrorDefaults();
		$this->initButtons();
	}
	
	// ------------------------------------------------------
	// BASICS - model, route, entityName, entityNamePlural
	// ------------------------------------------------------
	
	/**
	 * This function binds the CRUD to its corresponding Model (which extends Eloquent).
	 * All Create-Read-Update-Delete operations are done using that Eloquent Collection.
	 *
	 * @param $modelNamespace
	 */
	public function setModel($modelNamespace): void
	{
		if (!class_exists($modelNamespace)) {
			abort(500, "The model '{$modelNamespace}' does not exist.");
		}
		
		$this->model = new $modelNamespace();
		$this->query = $this->model->select('*');
		$this->entry = null;
	}
	
	/**
	 * Get the corresponding Eloquent Model for the CrudController, as defined with the setModel() function;.
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function getModel(): Model
	{
		return $this->model;
	}
	
	/**
	 * Get the database connection, as specified in the .env file or overwritten by the property on the model.
	 *
	 * @return \Illuminate\Database\Schema\Builder
	 */
	private function getSchema()
	{
		return Schema::connection($this->getModel()->getConnection()->getName());
	}
	
	/**
	 * Set the route for this CRUD.
	 * Ex: admin/article.
	 *
	 * @param [string] Route name.
	 * @param [array] Parameters.
	 */
	public function setRoute($route): void
	{
		$this->route = $route;
		$this->initButtons();
	}
	
	public function setParameters(array $parameters = []): void
	{
		$this->parameters = $parameters;
	}
	
	/**
	 * Set the route for this CRUD using the route name.
	 * Ex: admin.article.
	 *
	 * @param $route
	 * @param array $parameters
	 * @return void
	 * @throws \Exception
	 */
	public function setRouteName($route, array $parameters = []): void
	{
		$completeRoute = $route . '.index';
		
		if (!Route::has($completeRoute)) {
			throw new \Exception('There are no routes for this route name.', 404);
		}
		
		$this->route = route($completeRoute, $parameters);
		$this->initButtons();
	}
	
	/**
	 * Get the current CrudController route.
	 *
	 * Can be defined in the CrudController with:
	 * - $this->crud->setRoute(urlGen()->adminUri('article'))
	 * - $this->crud->setRouteName(urlGen()->adminUri().'.article')
	 * - $this->crud->route = urlGen()->adminUri("article")
	 *
	 * @return string
	 */
	public function getRoute(): string
	{
		return $this->route;
	}
	
	/**
	 * Set the entity name in singular and plural.
	 * Used all over the CRUD interface (header, add button, reorder button, breadcrumbs).
	 *
	 * @param $singular
	 * @param $plural
	 */
	public function setEntityNameStrings($singular, $plural): void
	{
		$this->entityName = $singular;
		$this->entityNamePlural = $plural;
	}
	
	// -----------------------------------------------
	// ACTIONS - the current operation being processed
	// -----------------------------------------------
	
	/**
	 * Get the action being performed by the controller,
	 * including middleware names, route name, method name,
	 * namespace, prefix, etc.
	 *
	 * @return string The EntityCrudController route action array.
	 */
	public function getAction()
	{
		return $this->request->route()->getAction();
	}
	
	/**
	 * Get the full name of the controller method
	 * currently being called (including namespace).
	 *
	 * @return string The EntityCrudController full method name with namespace.
	 */
	public function getActionName()
	{
		return $this->request->route()->getActionName();
	}
	
	/**
	 * Get the name of the controller method
	 * currently being called.
	 *
	 * @return string The EntityCrudController method name.
	 */
	public function getActionMethod()
	{
		return $this->request->route()->getActionMethod();
	}
	
	/**
	 * Check if the controller method being called
	 * matches a given string.
	 *
	 * @param string $methodName Name of the method (ex: index, create, update)
	 * @return bool
	 */
	public function actionIs(string $methodName)
	{
		return $methodName === $this->getActionMethod();
	}
	
	/**
	 * Disable syncPivot() feature in the update() method
	 */
	public function disableSyncPivot(): void
	{
		$this->disableSyncPivot = true;
	}
	
	/**
	 * @return bool
	 */
	public function isEnabledSyncPivot(): bool
	{
		return !($this->disableSyncPivot == true);
	}
	
	// ----------------------------------
	// Miscellaneous functions or methods
	// ----------------------------------
	
	/**
	 * Return the first element in an array that has the given 'type' attribute.
	 *
	 * @param $type
	 * @param $array
	 * @return \Closure|mixed|null
	 */
	public function getFirstOfItsTypeInArray($type, $array)
	{
		return Arr::first($array, function ($item) use ($type) {
			return $item['type'] == $type;
		});
	}
	
	// ------------
	// TONE FUNCTIONS - UNDOCUMENTED, UNTESTED, SOME MAY BE USED IN THIS FILE
	// ------------
	//
	// TODO:
	// - figure out if they are really needed
	// - comments inside the function to explain how they work
	// - write docblock for them
	// - place in the correct section above (CREATE, READ, UPDATE, DELETE, ACCESS, MANIPULATION)
	
	public function sync($type, $fields, $attributes): void
	{
		if (!empty($this->{$type})) {
			$this->{$type} = array_map(function ($field) use ($fields, $attributes) {
				if (in_array($field['name'], (array)$fields)) {
					$field = array_merge($field, $attributes);
				}
				
				return $field;
			}, $this->{$type});
		}
	}
	
	public function setSort($items, $order): void
	{
		$this->sort[$items] = $order;
	}
	
	public function sort($items)
	{
		if (array_key_exists($items, $this->sort)) {
			$elements = [];
			
			foreach ($this->sort[$items] as $item) {
				if (is_numeric($key = array_search($item, array_column($this->{$items}, 'name')))) {
					$elements[] = $this->{$items}[$key];
				}
			}
			
			return $this->{$items} = array_merge($elements, array_filter($this->{$items}, function ($item) use ($items) {
				return !in_array($item['name'], $this->sort[$items]);
			}));
		}
		
		return $this->{$items};
	}
	
	/**
	 * Get the Eloquent Model name from the given relation definition string.
	 *
	 * Note:
	 * - Accepts relation string. A dot notation can be used to chain multiple relations.
	 * - Return relation model name.
	 *
	 * Examples:
	 * - For a given string 'company' and a relation between App/Models/User and App/Models/Company, defined by a
	 *   company() method on the user model, the 'App/Models/Company' string will be returned.
	 * - For a given string 'company.address' and a relation between App/Models/User, App/Models/Company and
	 *   App/Models/Address defined by a company() method on the user model and an address() method on the
	 *   company model, the 'App/Models/Address' string will be returned.
	 *
	 * @param string $relationString Relation string. A dot notation can be used to chain multiple relations.
	 * @param int|null $length Optionally specify the number of relations to omit from the start of the relation string.
	 *                         If the provided length is negative,
	 *                         then that many relations will be omitted from the end of the relation string.
	 * @param \Illuminate\Database\Eloquent\Model|null $model Optionally specify a different model than the one in the crud object.
	 * @return string
	 */
	private function getRelationModel(string $relationString, int $length = null, Model $model = null): string
	{
		$relationArray = explode('.', $relationString);
		
		if (!isset($length)) {
			$length = count($relationArray);
		}
		
		if (!isset($model)) {
			$model = $this->model;
		}
		
		$result = array_reduce(
			array_splice($relationArray, 0, $length),
			fn ($obj, $method) => $obj->$method()->getRelated(),
			$model
		);
		
		return get_class($result);
	}
	
	/**
	 * Get the given attribute from a model or models resulting from the specified relation string (eg: the list of streets from
	 * the many addresses of the company of a given user).
	 *
	 * @param \Illuminate\Database\Eloquent\Model $model Model (eg: user).
	 * @param string $relationString Model relation. Can be a string representing the name of a relation method in the given
	 *                               Model or one from a different Model through multiple relations. A dot notation can be used to specify
	 *                               multiple relations (eg: user.company.address).
	 * @param string $attribute The attribute from the relation model (eg: the street attribute from the address model).
	 * @return array An array containing a list of attributes from the resulting model.
	 */
	public function getModelAttributeFromRelation(Model $model, string $relationString, string $attribute)
	{
		$endModels = $this->getRelationModelInstances($model, $relationString);
		$attributes = [];
		foreach ($endModels as $model) {
			if (is_array($model) && isset($model[$attribute])) {
				$attributes[] = $model[$attribute];
			} else if ($model->{$attribute}) {
				$attributes[] = $model->{$attribute};
			}
		}
		
		return $attributes;
	}
	
	/**
	 * Traverse the tree of relations for the given model, defined by the given relation string, and return the ending
	 * associated model instance or instances.
	 *
	 * @param \Illuminate\Database\Eloquent\Model $model The CRUD model.
	 * @param string $relationString Relation string. A dot notation can be used to chain multiple relations.
	 * @return array An array of the associated model instances defined by the relation string.
	 */
	private function getRelationModelInstances(Model $model, string $relationString)
	{
		$relationArray = explode('.', $relationString);
		$firstRelationName = array_first($relationArray);
		$relation = $model->{$firstRelationName};
		
		$results = [];
		if (!empty($relation)) {
			if ($relation instanceof Collection) {
				$currentResults = $relation->toArray();
			} else {
				$currentResults[] = $relation;
			}
			
			array_shift($relationArray);
			
			if (!empty($relationArray)) {
				foreach ($currentResults as $currentResult) {
					$results = array_merge($results, $this->getRelationModelInstances($currentResult, implode('.', $relationArray)));
				}
			} else {
				$results = $currentResults;
			}
		}
		
		return $results;
	}
	
	// -----------------------------------------------
	// BASE URL - Get crud base URL
	// -----------------------------------------------
	
	/**
	 * @param string|null $path
	 * @param array $parameters
	 * @return string
	 */
	public function getUrl(?string $path = null, array $parameters = []): string
	{
		$path = !empty($path) ? "/$path" : '';
		
		$routeParameters = urlBuilder($this->route)->getAllParameters();
		$route = urlBuilder($this->route)->removeAllParameters()->toString();
		
		$uri = $route . $path;
		$url = url($uri);
		
		$parameters = !empty($parameters) ? $parameters : $this->parameters;
		$parameters = array_merge($routeParameters, $parameters);
		
		return urlBuilder($url)->setParameters($parameters)->toString();
	}
	
	/**
	 * @param string|null $path
	 * @param array $parameters
	 * @return string
	 */
	public function getParentUrl(?string $path = null, array $parameters = []): string
	{
		$path = !empty($path) ? "/$path" : '';
		
		$routeParameters = urlBuilder($this->parentRoute)->getAllParameters();
		$route = urlBuilder($this->parentRoute)->removeAllParameters()->toString();
		
		$uri = $route . $path;
		$url = url($uri);
		
		$parameters = !empty($parameters) ? $parameters : $this->parameters;
		$parameters = array_merge($routeParameters, $parameters);
		
		return urlBuilder($url)->setParameters($parameters)->toString();
	}
}
