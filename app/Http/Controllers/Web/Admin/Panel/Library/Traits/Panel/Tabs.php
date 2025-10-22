<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

trait Tabs
{
	public bool $tabsEnabled = false;
	public string $tabsType = 'horizontal';
	
	public function enableTabs()
	{
		$this->tabsEnabled = true;
		$this->setTabsType(config('larapen.admin.tabs_type', 'horizontal'));
		
		return $this->tabsEnabled;
	}
	
	public function disableTabs()
	{
		$this->tabsEnabled = false;
		
		return $this->tabsEnabled;
	}
	
	/**
	 * @return bool
	 */
	public function tabsEnabled()
	{
		return $this->tabsEnabled;
	}
	
	/**
	 * @return bool
	 */
	public function tabsDisabled()
	{
		return !$this->tabsEnabled;
	}
	
	public function setTabsType($type)
	{
		$this->tabsType = $type;
		
		return $this->tabsType;
	}
	
	/**
	 * @return string
	 */
	public function getTabsType()
	{
		return $this->tabsType;
	}
	
	public function enableVerticalTabs()
	{
		return $this->setTabsType('vertical');
	}
	
	public function disableVerticalTabs()
	{
		return $this->setTabsType('horizontal');
	}
	
	public function enableHorizontalTabs()
	{
		return $this->setTabsType('horizontal');
	}
	
	public function disableHorizontalTabs()
	{
		return $this->setTabsType('vertical');
	}
	
	/**
	 * @param string $label
	 *
	 * @return bool
	 */
	public function tabExists(string $label)
	{
		$tabs = $this->getTabs();
		
		return in_array($label, $tabs);
	}
	
	/**
	 * @return bool|string
	 */
	public function getLastTab()
	{
		$tabs = $this->getTabs();
		
		if (count($tabs)) {
			return last($tabs);
		}
		
		return false;
	}
	
	/**
	 * @param $label
	 *
	 * @return bool
	 */
	public function isLastTab($label)
	{
		return $this->getLastTab() == $label;
	}
	
	/**
	 * @return \Illuminate\Support\Collection
	 */
	public function getFieldsWithoutATab()
	{
		$allFields = $this->getCurrentFields();
		
		return collect($allFields)
			->filter(function ($value) {
				return empty($value['tab']);
			});
	}
	
	/**
	 * @param $label
	 *
	 * @return array|\Illuminate\Support\Collection
	 */
	public function getTabFields($label)
	{
		if ($this->tabExists($label)) {
			$allFields = $this->getCurrentFields();
			
			return collect($allFields)
				->filter(function ($value) use ($label) {
					return isset($value['tab']) && $value['tab'] == $label;
				});
		}
		
		return [];
	}
	
	/**
	 * @return array
	 */
	public function getTabs()
	{
		$tabs = [];
		$fields = $this->getCurrentFields();
		
		$fieldsWithTabs = collect($fields)
			->filter(function ($value) {
				return isset($value['tab']);
			})
			->each(function ($value) use (&$tabs) {
				$tabName = $value['tab'];
				if (!in_array($tabName, $tabs)) {
					$tabs[] = $tabName;
				}
			});
		
		return $tabs;
	}
}
