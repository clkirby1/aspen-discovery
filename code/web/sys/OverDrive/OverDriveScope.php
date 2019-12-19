<?php


class OverDriveScope extends DataObject
{
	public $__table = 'overdrive_scopes';
	public $id;
	public $name;
	public $includeAdult;
	public $includeTeen;
	public $includeKids;
	public $authenticationILSName;
	public $requirePin;
	public /** @noinspection PhpUnused */ $overdriveAdvantageName;
	public /** @noinspection PhpUnused */ $overdriveAdvantageProductsKey;

	private $_libraries;
	private $_locations;

	public static function getObjectStructure()
	{
		$libraryList = self::getLibraryList();
		$locationList = self::getLocationList();

		$structure = [
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'name' => array('property'=>'name', 'type'=>'text', 'label'=>'Name', 'description'=>'The Name of the scope', 'maxLength' => 50),
			'authenticationILSName' => array('property'=>'authenticationILSName', 'type'=>'text', 'label'=>'The ILS Name Overdrive uses for user Authentication', 'description'=>'The name of the ILS that OverDrive uses to authenticate users logging into the Overdrive website.', 'size'=>'20', 'hideInLists' => true),
			'requirePin'            => array('property'=>'requirePin', 'type'=>'checkbox', 'label'=>'Is a Pin Required to log into Overdrive website?', 'description'=>'Turn on to allow repeat search in Overdrive functionality.', 'hideInLists' => true, 'default' => 0),
			'overdriveAdvantageName'         => array('property'=>'overdriveAdvantageName', 'type'=>'text', 'label'=>'Overdrive Advantage Name', 'description'=>'The name of the OverDrive Advantage account if any.', 'size'=>'80', 'hideInLists' => true,),
			'overdriveAdvantageProductsKey'  => array('property'=>'overdriveAdvantageProductsKey', 'type'=>'text', 'label'=>'Overdrive Advantage Products Key', 'description'=>'The products key for use when building urls to the API from the advantageAccounts call.', 'size'=>'80', 'hideInLists' => false,),
			'includeAdult' => array('property' => 'includeAdult', 'type' => 'checkbox', 'label' => 'Include Adult Titles', 'description' => 'Whether or not adult titles from the Overdrive collection should be included in searches', 'hideInLists' => true, 'default' => true),
			'includeTeen' => array('property' => 'includeTeen', 'type' => 'checkbox', 'label' => 'Include Teen Titles', 'description' => 'Whether or not teen titles from the Overdrive collection should be included in searches', 'hideInLists' => true, 'default' => true),
			'includeKids' => array('property' => 'includeKids', 'type' => 'checkbox', 'label' => 'Include Kids Titles', 'description' => 'Whether or not kids titles from the Overdrive collection should be included in searches', 'hideInLists' => true, 'default' => true),

			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this scope',
				'values' => $libraryList,
			),

			'locations' => array(
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this scope',
				'values' => $locationList,
			),
		];
		return $structure;
	}

	/**
	 * @return array
	 */
	private static function getLibraryList(): array
	{
		$library = new Library();
		$library->orderBy('displayName');
		if (UserAccount::userHasRole('libraryAdmin')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
		$library->find();
		$libraryList = [];
		while ($library->fetch()) {
			$libraryList[$library->libraryId] = $library->displayName;
		}
		return $libraryList;
	}

	/**
	 * @return array
	 */
	private static function getLocationList(): array
	{
		$location = new Location();
		$location->orderBy('displayName');
		if (UserAccount::userHasRole('libraryAdmin')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			$location->libraryId = $homeLibrary->libraryId;
		}
		$location->find();
		$locationList = [];
		while ($location->fetch()) {
			$locationList[$location->locationId] = $location->displayName;
		}
		return $locationList;
	}

	public function __get($name){
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id){
				$this->_libraries = [];
				$obj = new Library();
				$obj->overDriveScopeId = $this->id;
				$obj->find();
				while($obj->fetch()){
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} elseif ($name == "locations") {
			if (!isset($this->_locations) && $this->id){
				$this->_locations = [];
				$obj = new Location();
				$obj->overDriveScopeId = $this->id;
				$obj->find();
				while($obj->fetch()){
					$this->_locations[$obj->locationId] = $obj->locationId;
				}
			}
			return $this->_locations;
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value){
		if ($name == "libraries") {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->_libraries = $value;
		}elseif ($name == "locations") {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->_locations = $value;
		}else {
			$this->_data[$name] = $value;
		}
	}

	public function update()
	{
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return true;
	}

	public function insert()
	{
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function saveLibraries(){
		if (isset ($this->_libraries) && is_array($this->_libraries)){
			$libraryList = self::getLibraryList();
			foreach ($libraryList as $libraryId => $displayName){
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)){
					//We want to apply the scope to this library
					if ($library->overDriveScopeId != $this->id){
						$library->overDriveScopeId = $this->id;
						$library->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->overDriveScopeId == $this->id){
						$library->overDriveScopeId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function saveLocations(){
		if (isset ($this->_locations) && is_array($this->_locations)){
			$locationList = self::getLocationList();
			/**
			 * @var int $locationId
			 * @var Location $location
			 */
			foreach ($locationList as $locationId => $displayName){
				$location = new Location();
				$location->locationId = $locationId;
				$location->find(true);
				if (in_array($locationId, $this->_locations)){
					//We want to apply the scope to this library
					if ($location->overDriveScopeId != $this->id){
						$location->overDriveScopeId = $this->id;
						$location->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($location->overDriveScopeId == $this->id){
						$library = new Library();
						$library->libraryId = $location->libraryId;
						$library->find(true);
						if ($library->overDriveScopeId != -1){
							$location->overDriveScopeId = -1;
						}else{
							$location->overDriveScopeId = -2;
						}
						$location->update();
					}
				}
			}
			unset($this->_locations);
		}
	}

	/** @return Library[] */
	public function getLibraries()
	{
		/** @noinspection PhpUndefinedFieldInspection */
		return $this->_libraries;
	}

	/** @return Location[] */
	public function getLocations()
	{
		/** @noinspection PhpUndefinedFieldInspection */
		return $this->_locations;
	}

	public function setLibraries($val)
	{
		/** @noinspection PhpUndefinedFieldInspection */
		$this->_libraries = $val;
	}

	public function setLocations($val)
	{
		/** @noinspection PhpUndefinedFieldInspection */
		$this->_libraries = $val;
	}

	public function clearLibraries(){
		$this->clearOneToManyOptions('Library', 'overDriveScopeId');
		unset($this->_libraries);
	}

	public function clearLocations(){
		$this->clearOneToManyOptions('Location', 'overDriveScopeId');
		unset($this->_locations);
	}
}