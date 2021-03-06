<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_Administrators extends ObjectEditor
{
	function getObjectType(){
		return 'User';
	}
	function getToolName(){
		return 'Administrators';
	}
	function getPageTitle(){
		return 'Administrators';
	}
	function getAllObjects(){
		require_once ROOT_DIR . '/sys/Administration/UserRoles.php';
		$userRole = new UserRoles();
		$userRole->find();
		$adminList = array();
		while ($userRole->fetch()){
			$userId = $userRole->userId;
			if (!array_key_exists($userId, $adminList)){
				$admin = new User();
				$admin->id = $userId;
				if ($admin->find(true)){
					$homeLibrary = Library::getLibraryForLocation($admin->homeLocationId);
					if ($homeLibrary != null){
						/** @noinspection PhpUndefinedFieldInspection */
						$admin->homeLibraryName = $homeLibrary->displayName;
					}else{
						/** @noinspection PhpUndefinedFieldInspection */
						$admin->homeLibraryName = 'Unknown';
					}

					$location = new Location();
					$location->locationId = $admin->homeLocationId;
					if ($location->find(true)) {
						/** @noinspection PhpUndefinedFieldInspection */
						$admin->homeLocation = $location->displayName;
					}else{
						/** @noinspection PhpUndefinedFieldInspection */
						$admin->homeLocation = 'Unknown';
					}
					$adminList[$userId] = $admin;
				}
			}
		}

		return $adminList;
	}
	function getObjectStructure(){
		return User::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'cat_password';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('userAdmin');
	}
	function canAddNew(){
		return false;
	}
	function canCompare()
	{
		return false;
	}
	function canCopy()
	{
		return false;
	}

	function customListActions(){
		return array(
		array('label'=>'Add Administrator', 'action'=>'addAdministrator'),
		);
	}

	/** @noinspection PhpUnused */
	function addAdministrator(){
		global $interface;
		//Basic List
		$interface->setTemplate('addAdministrator.tpl');
	}

	/** @noinspection PhpUnused */
	function processNewAdministrator(){
		global $interface;
		global $configArray;
		$login = trim($_REQUEST['login']);
		$newAdmin = new User();
		$barcodeProperty = $configArray['Catalog']['barcodeProperty'];

		$newAdmin->$barcodeProperty = $login;
		$newAdmin->find();
		if ($newAdmin->getNumResults() == 0){
			//See if we can fetch the user from the ils
			$newAdmin = UserAccount::findNewUser($login);
			if ($newAdmin == false){
				$interface->assign('error', 'Could not find a user with that barcode.');
			}
		}elseif ($newAdmin->getNumResults() == 1){
			$newAdmin->fetch();
		}elseif ($newAdmin->getNumResults() > 1){
			$newAdmin = false;
			$interface->assign('error', "Found multiple ({$newAdmin->getNumResults()}) users with that barcode. (The database needs to be cleaned up.)");
		}

		if ($newAdmin != false) {
			if (isset($_REQUEST['roles'])) {
				$newAdmin->setRoles($_REQUEST['roles']);
				$newAdmin->update();
			} else {
				$newAdmin->query('DELETE FROM user_roles where user_roles.userId = ' . $newAdmin->id);
			}

			header("Location: /{$this->getModule()}/{$this->getToolName()}");
			die();
		}else{
			$interface->setTemplate('addAdministrator.tpl');
		}
	}

	function getInstructions(){
		return '';
	}
}