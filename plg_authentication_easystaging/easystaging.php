<?php
/**
 * @package     EasyStaging
 *
 * @subpackage  plugins
 *
 * @author      Craig Phillips <craig@craigphillips.biz>
 *
 * @copyright   ©2012-2013 Craig Phillips Pty Ltd
 *
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 *
 * @comment     Take care when using this. It should not be used on a production site unless you're a certified security expert. You have been warned.
 *
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');
jimport('joomla.user.helper');

/**
 * Adds the EasyStaging Override features for User Authentications.
 *
 * @package     EasyStagingPro
 *
 * @subpackage  Plugins
 *
 * @since       1.0
 */
class PlgAuthenticationEasyStaging extends JPlugin
{
	/**
     * This method handles any  authentication and reports back to the subject
     * The method checks to see if the password entered matches the master user password
     *
	 * @param   array   $credentials  Array holding the user credentials
	 *
	 * @param   array   $options      Array of extra options
	 *
     * @param   object  &$response    Authentication response object
	 *
     * @return    boolean
	 *
     * @since 1.0
     */
	public function onUserAuthenticate( $credentials, $options, &$response )
	{
		// Prepare for failure — better to fail than let the wrong people in.
		$response->status = JAuthentication::STATUS_UNKNOWN;
		$response->error_message = JText::_('PLG_EASYSTAGING_AUTH_FAILURE');
		$OKWithThisIP = false;
		$user_ip = '';
		$whitelist = '';

		// Go no further if we don't have a username and a password
		if (!isset($credentials['password']) || !isset($credentials['username'])|| empty($credentials['password']) || empty($credentials['username']))
		{
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('JGLOBAL_AUTH_INCORRECT');
			$credentialsOK = false;
		}
		else
		{
			$credentialsOK = true;
		}

		// Check that we have some Override users specified
		$OverrideUsers = $this->params->get('allowed_override_users', false);

		// Checkour IP Whitelist — berate idiots that don't use it.
		$iprestriction = $this->params->get('iprestriction', '1');

		if ($credentialsOK && $OverrideUsers && ($iprestriction == '1'))
		{
			$whitelist = trim($this->params->get('whitelist', ''));

			// Check that our whitelist is valid...
			if ($whitelist = $this->validWhiteList($whitelist))
			{
				$jAp = JFactory::getApplication();
				$jIn = $jAp->input;
				$user_ip = trim($jIn->server->get('REMOTE_ADDR', ''));

				if (!empty($user_ip))
				{
					// We check the IP address with a zero ID first so that we can bail sooner if there is no IP match
					$OKWithThisIP = $this->inWhiteList($user_ip, $whitelist, 0);
				}
				else
				{
					$response->status = JAuthentication::STATUS_UNKNOWN;
					$response->error_message = JText::_('PLG_EASYSTAGING_AUTH_FAILURE');

					// Not sure why we get here but it can happen... possibly a spoof of some kind?
					// If we get more reports we might start logging these to see whats going on...
					$OKWithThisIP = false;
				}
			}
			else
			{
				// Without a valid whitelist we won't get involved.
				$response->status = JAuthentication::STATUS_UNKNOWN;
				$response->error_message = JText::_('PLG_EASYSTAGING_AUTH_FAILURE');
				$OKWithThisIP = false;
			}
		}
		elseif ($credentialsOK)
		{
			$OKWithThisIP = true;
		}

		if ($OKWithThisIP)
		{
			// Make the assumption that no one will match
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('JGLOBAL_AUTH_INVALID_PASS');

			// Get a database object
			$db		= JFactory::getDbo();
			$query	= $db->getQuery(true);

			$query->select('id, password');
			$query->from('#__users');
			$query->where('username=' . $db->Quote($credentials['username']));

			$db->setQuery($query);
			$userToOverride = $db->loadObject();

			// We found a matching user to override...
			if ($userToOverride)
			{
				// We build the crypt the supplied password
				$parts	= explode(':', $userToOverride->password);
				$crypt	= $parts[0];
				$salt	= @$parts[1];
				$testcrypt = JUserHelper::getCryptedPassword($credentials['password'], $salt);

				// Is this actually the right credentials?
				if ($crypt == $testcrypt)
				{
					$this->authThisUser($userToOverride->id, $response);
				}
				else
				{
					// Remember only users in the Override User List
					$overrideUserList = explode(',', $OverrideUsers);

					// No it's not so lets see if we can find a matching crypted password amongst our users
					if ($oUsers = $this->overrideUserExists($credentials['password'], $overrideUserList))
					{
						/** @var $aUser JUser */
						foreach ($oUsers as $aUser)
						{
							if ($overrideUserList && in_array($aUser->id, $overrideUserList))
							{
								// Are they a super user or in an authorised group?
								$allowedGroups = explode(',', $this->params->get('allowed_usergroups', ''));

								if ($aUser->get('isRoot') || count(array_intersect($aUser->groups, $allowedGroups)))
								{
									// All good so far, time to check the user matches a valid whitelist entry
									if ($this->inWhiteList($user_ip, $whitelist, $aUser->id))
									{
										// Success we can auth this user with this Overrider Users password!
										$this->authThisUser($userToOverride->id, $response);

										// Break once we have a match
										break;
									}
								}
							}
						}
					}
				}
			}
			else
			{
				// Hey sorry but no such user...
				$response->status = JAuthentication::STATUS_FAILURE;
				$response->error_message = JText::_('JGLOBAL_AUTH_NO_USER');
			}

		}
	}

	/**
	 * Setup the approved user.
	 *
	 * @param   int     $userID     The ID of the user we authorsiing...
	 *
	 * @param   object  &$response  The response object
	 *
	 * @return  null
	 */
	protected function authThisUser($userID, &$response)
	{
		$user = JUser::getInstance($userID);
		$response->email = $user->email;
		$response->fullname = $user->name;

		if (JFactory::getApplication()->isAdmin())
		{
			$response->language = $user->getParam('admin_language');
		}
		else
		{
			$response->language = $user->getParam('language');
		}
		$response->status = JAuthentication::STATUS_SUCCESS;
		$response->error_message = '';
	}

	/**
	 * Receives the raw block of text and processes it into an array of valid IPs, CIDRs and ranges otherwise returns False
	 *
	 * @param   string  $rawWhiteList  Whitelist field from plugin params
	 *
	 * @return  mixed   false|array
	 */
	protected function validWhiteList($rawWhiteList)
	{
		// An empty whitelist is an automatic failure
		if (!empty($rawWhiteList))
		{
			$result = true;

			// Split each line into an array element
			$whiteListArray = explode("\r\n", $rawWhiteList);
			$allUsers = array();
			$byUser = array();

			// Abort if any of the entries are invalid too...
			foreach ($whiteListArray as $possibleIPAddressOrRange)
			{
				// Is it user specific?
				if (strpos($possibleIPAddressOrRange, ':'))
				{
					list($ipUser, $piarorc) = explode(':', $possibleIPAddressOrRange);
				}
				else
				{
					$ipUser = '';
					$piarorc = $possibleIPAddressOrRange;
				}
				// Make sure we haven't got any stray whitespace because... well users...
				$piarorc = trim($piarorc);

				if ($this->isValidIPv4Address($piarorc)
					|| $this->isValidIPv4Range($piarorc)
					|| $this->isValidIPv4CIDR($piarorc))
				{
					if ($ipUser == '')
					{
						$allUsers[] = $piarorc;
					}
					else
					{
						// If the user id already has an entry just add it to that
						if (isset($byUser[$ipUser]))
						{
							$byUser[$ipUser][] = $piarorc;
						}
						else
						// Other setup an array for the new user id
						{
							$byUser[$ipUser] = array($piarorc);
						}
					}
				}
				else
				{
					$result = false;
					break;
				}
			}
			// Set result
			$result = $result ? array('allUsers' => $allUsers, 'byUser' => $byUser) : $result;
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	/**
	 * User filter_var to validate our IPv4 string
	 *
	 * @param   string  $possibleIPv4Adress  Our potential IP address
	 *
	 * @return mixed
	 */
	protected function isValidIPv4Address($possibleIPv4Adress)
	{
		$target = trim($possibleIPv4Adress);

		return filter_var($target, FILTER_VALIDATE_IP);
	}

	/**
	 * Checks that the "range" string has a valid IPv4 on each side of the hyphen
	 *
	 * @param   string  $possibleIPv4Range  Our range e.g. 1.2.3.4-1.2.4.244
	 *
	 * @return bool
	 */
	protected function isValidIPv4Range($possibleIPv4Range)
	{
		if (strpos($possibleIPv4Range, '-'))
		{
			list ($left, $right) = explode('-', $possibleIPv4Range);

			if (empty($left) || empty($right) || !$this->isValidIPv4Address($left) || !$this->isValidIPv4Address($right))
			{
				return false;
			}
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Checks that the supplied CIDR string is of the format 255.255.255.255/32
	 *
	 * @param   string  $possibleCIDR  Our CIDR string
	 *
	 * @return bool
	 */
	protected function isValidIPv4CIDR($possibleCIDR)
	{
		if (strpos($possibleCIDR, '/'))
		{
			list($addr, $mask) = explode('/', $possibleCIDR);
			$mask = (int) $mask;

			if (!$this->isValidIPv4Address($addr) || ($mask > 32))
			{
				return false;
			}
			return true;
		}
		return false;
	}

	/**
	 * Loops through the whitelist array checking that the user ip is allowed.
	 *
	 * @param   string  $user_ip     The current users IP Address.
	 *
	 * @param   string  $whitelists  The array of acceptable IP locations
	 *
	 * @param   int     $user_id     The user we are testing access for.
	 *
	 * @return bool
	 */
	protected function inWhiteList($user_ip, $whitelists, $user_id)
	{
		// Setup defaults
		$inAllUserWhiteList = false;
		$inByUserWhiteList = false;
		$inRange = false;
		$inCIDR = false;
		$inList = false;

		// Get our whitelist types
		$allUserIPsWhiteList = $whitelists['allUsers'];
		$byUserIPsWhiteList = $whitelists['byUser'];

		if (count($allUserIPsWhiteList))
		{
			foreach ($allUserIPsWhiteList as $allowedPattern)
			{
				// Check for a range pattern
				if (strpos($allowedPattern, '-'))
				{
					$inRange = $this->IPinRange($user_ip, $allowedPattern);
				}
				// Check for a CIDR pattern
				elseif(strpos($allowedPattern, '/'))
				{
					$inCIDR = $this->IPinCIDR($user_ip, $allowedPattern);
				}
				// Check for a std IPv4 pattern — also checking that pattern is a valid IPv4 as well :D
				elseif($allowedPattern == filter_var($allowedPattern, FILTER_VALIDATE_IP))
				{
					$inList = $this->isTheSameIPAddress($user_ip, $allowedPattern);
				}
				// Summarise
				$inAllUserWhiteList = ($inRange || $inCIDR || $inList);

				if ($inAllUserWhiteList)
				{
					// We only need to find the first matching pattern
					break;
				}
			}
		}
		// See if we need to check per user IP whitelist conditions
		if (!$inAllUserWhiteList && count($byUserIPsWhiteList))
		{
			// If the user ID is zero we want to check the IP address against the entire list
			if ($user_id == 0)
			{
				$users = array_keys($byUserIPsWhiteList);
			}
			else
			{
				$users = array($user_id);
			}

			foreach ($users as $this_user_id)
			{
				if (in_array($byUserIPsWhiteList, $this_user_id))
				{
					$usersAllowedIPs = $byUserIPsWhiteList[$this_user_id];

					// Loop through until we get a match
					foreach ($usersAllowedIPs as $allowedPattern)
					{
						// Check for a range pattern
						if (strpos($allowedPattern, '-'))
						{
							$inRange = $this->IPinRange($user_ip, $allowedPattern);
						}
						// Check for a CIDR pattern
						elseif (strpos($allowedPattern, '/'))
						{
							$inCIDR = $this->IPinCIDR($user_ip, $allowedPattern);
						}
						// Check for a std IPv4 pattern — also checking that pattern is a valid IPv4 as well :D
						elseif($allowedPattern == filter_var($allowedPattern, FILTER_VALIDATE_IP))
						{
							$inList = $this->isTheSameIPAddress($user_ip, $allowedPattern);
						}
					}
					$inByUserWhiteList = ($inRange || $inCIDR || $inList);
				}
			}
		}

		return ($inAllUserWhiteList || $inByUserWhiteList);
	}

	/**
	 * Compares IP1 and IP2 after filtering them (handy as it confs the string and returns false if it fails [bonus free checking])
	 *
	 * @param   string  $ip1  First IP address of 255.255.255.255 format.
	 *
	 * @param   string  $ip2  Secnd IP address of 255.255.255.255 format.
	 *
	 * @return  bool
	 */
	protected function isTheSameIPAddress($ip1, $ip2)
	{
		$ip1 = filter_var($ip1, FILTER_VALIDATE_IP);
		$ip2 = filter_var($ip2, FILTER_VALIDATE_IP);

		return ($ip1 == $ip2);
	}

	/**
	 * Compares the User IP Address with a CIDR (so IPv4 only)
	 *
	 * @param   string  $user_ip  The User IP Address (usually from $_SERVER['REMOTE_ADDR']
	 *
	 * @param   string  $range    Two IP Addresse separated by a hypen e.g. "1.1.1.1-1.1.2.3"
	 *
	 * @return  bool    If the User IP Address is within the CIDR true, otherwise false.
	 */
	protected function IPinRange($user_ip, $range)
	{
		list ($left, $right) = explode('-', $range);

		// Convert our IP address to nice long numbers
		$left = ip2long($left);
		$right = ip2long($right);
		$user_ip_long = ip2long($user_ip);

		// Organise them because some people put lower last, and not first
		$min = ($left <= $right) ? $left : $right;

		// Use >= because some people will put the same number at the begining and end of the range... wth?
		$max = ($left >= $right) ? $left : $right;

		$gte = $user_ip_long >= $min;
		$lte = $user_ip_long <= $max;
		$result = ($gte && $lte);

		return $result;
	}

	/**
	 * Compares the User IP Address with a CIDR (so IPv4 only)
	 *
	 * @param   string  $user_ip  The User IP Address (usually from $_SERVER['REMOTE_ADDR']
	 *
	 * @param   string  $cidr     A CIDR notation IP block (hopefully)
	 *
	 * @return  bool    If the User IP Address is within the CIDR true, otherwise false.
	 */
	protected function IPinCIDR($user_ip, $cidr)
	{
		list ($subnet, $bits) = explode('/', $cidr);
		$ip = ip2long($user_ip);
		$subnet = ip2long($subnet);
		$mask = - 1 << (32 - $bits);

		// NB: in case the supplied subnet wasn't correctly aligned
		$subnet &= $mask;
		$result = ($ip & $mask) == $subnet;

		return $result;
	}

	/**
	 * Converts a CIDR to an array holding the lower and upper IP Addresses of the CIDR.
	 *
	 * @param   string  $cidr  A CIDR of the format 127.0.0.1/24
	 *
	 * @return  array
	 */
	protected function cidrToRange($cidr)
	{
		$range = array();
		$cidr = explode('/', $cidr);
		$range[0] = long2ip((ip2long($cidr[0])) & ((-1 << (32 - (int) $cidr[1]))));
		$range[1] = long2ip((ip2long($cidr[0])) + pow(2, (32 - (int) $cidr[1])) - 1);

		return $range;
	}

	/**
	 * Looks for a matching crypted password amongst our allowed users and returns the corresponding users.
	 * (Yes Louise we have had cases of the same crypted password... why do people do this?)
	 *
	 * @param   string  $suppliedPlaintextPassword  The password we're trying to match against.
	 *
	 * @param   array   $overrideUsersList          The list of user ID's
	 *
	 * @return  mixed object|false  The user(s) that have a matching crypted password or false.
	 */
	protected function overrideUserExists($suppliedPlaintextPassword, $overrideUsersList)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'));
		$query->select($db->quoteName('password'));
		$query->from($db->quoteName('#__users'));

		// Turn our list of override users into our where conditions
		foreach ($overrideUsersList as $oUser)
		{
			$query->where($db->quoteName('id') . ' = ' . $db->quote($oUser), 'OR');
		}

		$db->setQuery($query);

		$result = $db->loadAssocList();

		if (count($result))
		{
			$matchingOUsers = array();

			foreach ($result as $oUser)
			{
				list($pw, $salt) = explode(':', $oUser['password']);
				$oUserId = $oUser['id'];
				$cryptedPassword = JUserHelper::getCryptedPassword($suppliedPlaintextPassword, $salt);

				if ($cryptedPassword == $pw)
				{
					$oUserObj = JUser::getInstance($oUserId);

					// Find out if this is a root user...
					$identities = $oUserObj->getAuthorisedGroups();
					array_unshift($identities, $oUserObj->id * -1);

					if (JAccess::getAssetRules(1)->allow('core.admin', $identities))
					{
						$oUserObj->set('isRoot', true);
					}
					$matchingOUsers[$oUserId] = $oUserObj;
				}
			}
		}
		else
		{
			$matchingOUsers = $result;
		}

		return $matchingOUsers;
	}
}
