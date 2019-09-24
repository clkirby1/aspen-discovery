<?php

require_once ROOT_DIR . '/Action.php';
class Help_IP extends Action
{
	function launch()
	{
		global $interface;

		$ip_address = Location::getActiveIp();
		$interface->assign('ip_address', $ip_address);

		$this->display('ip.tpl', 'IP Address');
	}
}