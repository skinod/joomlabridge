<?php

/**
 * <pre>
 * Skinod
 * Login handler abstraction : Joomla
 * </pre>
 *
 * @author   	$Author: sijad $
 * @link		http://skinod.com
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class login_joomla extends login_core implements interface_login
{

	protected $method_config	= array();
	protected $jom_conf			= array();
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @param	array 		Configuration info for this method
	 * @param	array 		Custom configuration info for this method
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $method, $conf=array() )
	{
		$this->method_config	= $method;
		$this->jom_conf	= $conf;
		
		
		parent::__construct( $registry );
	}

	/**
	 * Authenticate the request
	 *
	 * @access	public
	 * @param	string		Username
	 * @param	string		Email Address
	 * @param	string		Password
	 * @return	boolean		Authentication successful
	 */
	public function authenticate( $username, $email_address, $password )
	{
		//-----------------------------------------
		// Check admin authentication request
		//-----------------------------------------
		
		if ( $this->is_admin_auth )
		{
			$this->adminAuthLocal( $username, $email_address, $password );
			
  			if ( $this->return_code == 'SUCCESS' )
  			{
  				return true;
  			}
		}

		//-----------------------------------------
		// GET DB object
		//-----------------------------------------

		if ( ! class_exists( 'dbMain' ) )
		{
			require_once( IPS_KERNEL_PATH.'classDb.php' );/*noLibHook*/
			require_once( IPS_KERNEL_PATH.'classDb' . ucwords($this->settings['sql_driver']) . '.php' );/*noLibHook*/
		}

		$classname = "db_driver_" . $this->settings['sql_driver'];

		$RDB = new $classname;

		$RDB->obj['sql_host']				= $this->jom_conf['sodjom_host'];
		$RDB->obj['sql_database']			= $this->jom_conf['sodjom_dbname'];
		$RDB->obj['sql_user']				= $this->jom_conf['sodjom_dbuser'];
		$RDB->obj['sql_pass']				= $this->jom_conf['sodjom_dbpwd'];
		$RDB->obj['sql_port']				= $this->jom_conf['sodjom_dbport'];  
		$RDB->obj['sql_tbl_prefix']			= $this->jom_conf['sodjom_dbprefix'];
		$RDB->obj['use_shutdown']			= 0;
		$RDB->obj['force_new_connection']	= 1;
		
		if( $this->jom_conf['sodjom_sqltype'] )
		{
			$RDB->connect_vars['sql_type']	= $this->jom_conf['sodjom_sqltype'];
		}

		//--------------------------------
		// Get a DB connection
		//--------------------------------

		$RDB->connect();

		$email_address = IPSText::checkEmailAddress($email_address)?$email_address:'';
		$fild = ($email_address)?$email_address:$username;
		$column = ($email_address)?'email':'username';
		
		$joomla_member = $RDB->buildAndFetch( array( 'select' => '*',
															'from'   => 'users',
															'where'  => $column."='".$RDB->addSlashes($fild)."'") );

		$RDB->disconnect();
		
		//-----------------------------------------
		// Check
		//-----------------------------------------

		if ( ! $joomla_member[ $column ] )
		{
			$this->return_code = 'NO_USER';
			return false;
		}

		//-----------------------------------------
		// Check password
		//-----------------------------------------

		$password			= html_entity_decode($password, ENT_QUOTES);
		$html_entities		= array( "&#33;", "&#036;", "&#092;" );
		$replacement_char	= array( "!", "$", "\\" );
		$password 			= str_replace( $html_entities, $replacement_char, $password );
		
		$parts	= explode(':', $joomla_member['password']);
		$crypt	= $parts[0];
		$salt	= @$parts[1];
		$testcrypt = $this->_getCryptedPassword($password, $salt, $this->jom_conf['sodjom_hashtype']);
		
		
		if ($crypt != $testcrypt) {
			$this->return_code = 'WRONG_AUTH';
			return false;
		}

		//-----------------------------------------
		// Still here? Then we have a username
		// and matching password.. so get local member
		// and see if there's a match.. if not, create
		// one!
		//-----------------------------------------

		$this->_loadMember( $joomla_member['username'] );
		$loademail = IPSMember::load( strtolower($joomla_member['email']) );
		
		if ( (is_array($loademail) and count($loademail)) or $this->member_data['member_id'] )
		{
			$this->return_code = 'ERROR';
			return false;
		}

		$this->return_code = 'SUCCESS';

		$this->member_data = $this->createLocalMember( array( 'members' => array( 'name' => $joomla_member['username'], 'password' => $password, 'email' => $joomla_member['email'] ) ) );
		
		return true;
		
	}
	
	//this method is from joomla
	private function _getCryptedPassword($plaintext, $salt = '', $encryption = 'md5-hex') {
		$encryption = $encryption?$encryption:'md5-hex';
		$salt = $this->_getSalt($encryption, $salt, $plaintext);

		switch ($encryption)
		{
			case 'plain':
				return $plaintext;

			case 'sha':
				$encrypted = base64_encode(mhash(MHASH_SHA1, $plaintext));
				return $encrypted;

			case 'crypt':
			case 'crypt-des':
			case 'crypt-md5':
			case 'crypt-blowfish':
				return crypt($plaintext, $salt);

			case 'md5-base64':
				$encrypted = base64_encode(mhash(MHASH_MD5, $plaintext));
				return $encrypted;

			case 'ssha':
				$encrypted = base64_encode(mhash(MHASH_SHA1, $plaintext . $salt) . $salt);
				return $encrypted;

			case 'smd5':
				$encrypted = base64_encode(mhash(MHASH_MD5, $plaintext . $salt) . $salt);
				return $encrypted;

			case 'aprmd5':
				$length = strlen($plaintext);
				$context = $plaintext . '$apr1$' . $salt;
				$binary = $this->_bin(md5($plaintext . $salt . $plaintext));

				for ($i = $length; $i > 0; $i -= 16)
				{
					$context .= substr($binary, 0, ($i > 16 ? 16 : $i));
				}
				for ($i = $length; $i > 0; $i >>= 1)
				{
					$context .= ($i & 1) ? chr(0) : $plaintext[0];
				}

				$binary = $this->_bin(md5($context));

				for ($i = 0; $i < 1000; $i++)
				{
					$new = ($i & 1) ? $plaintext : substr($binary, 0, 16);
					if ($i % 3)
					{
						$new .= $salt;
					}
					if ($i % 7)
					{
						$new .= $plaintext;
					}
					$new .= ($i & 1) ? substr($binary, 0, 16) : $plaintext;
					$binary = $this->_bin(md5($new));
				}

				$p = array();
				for ($i = 0; $i < 5; $i++)
				{
					$k = $i + 6;
					$j = $i + 12;
					if ($j == 16)
					{
						$j = 5;
					}
					$p[] = $this->_toAPRMD5((ord($binary[$i]) << 16) | (ord($binary[$k]) << 8) | (ord($binary[$j])), 5);
				}

				return '$apr1$' . $salt . '$' . implode('', $p) . $this->_toAPRMD5(ord($binary[11]), 3);

			case 'md5-hex':
			default:
				$encrypted = ($salt) ? md5($plaintext . $salt) : md5($plaintext);
				return $encrypted;
		}
	}
	
	//this method is from joomla
	private function _bin($hex) {
		$bin = '';
		$length = strlen($hex);
		for ($i = 0; $i < $length; $i += 2)
		{
			$tmp = sscanf(substr($hex, $i, 2), '%x');
			$bin .= chr(array_shift($tmp));
		}
		return $bin;
	}
	
	//this method is from joomla
	private function _getSalt($encryption = 'md5-hex', $seed = '', $plaintext = '') {
		// Encrypt the password.
		switch ($encryption)
		{
			case 'crypt':
			case 'crypt-des':
				if ($seed)
				{
					return substr(preg_replace('|^{crypt}|i', '', $seed), 0, 2);
				}
				else
				{
					return substr(md5(mt_rand()), 0, 2);
				}
				break;

			case 'crypt-md5':
				if ($seed)
				{
					return substr(preg_replace('|^{crypt}|i', '', $seed), 0, 12);
				}
				else
				{
					return '$1$' . substr(md5(mt_rand()), 0, 8) . '$';
				}
				break;

			case 'crypt-blowfish':
				if ($seed)
				{
					return substr(preg_replace('|^{crypt}|i', '', $seed), 0, 16);
				}
				else
				{
					return '$2$' . substr(md5(mt_rand()), 0, 12) . '$';
				}
				break;

			case 'ssha':
				if ($seed)
				{
					return substr(preg_replace('|^{SSHA}|', '', $seed), -20);
				}
				else
				{
					return mhash_keygen_s2k(MHASH_SHA1, $plaintext, substr(pack('h*', md5(mt_rand())), 0, 8), 4);
				}
				break;

			case 'smd5':
				if ($seed)
				{
					return substr(preg_replace('|^{SMD5}|', '', $seed), -16);
				}
				else
				{
					return mhash_keygen_s2k(MHASH_MD5, $plaintext, substr(pack('h*', md5(mt_rand())), 0, 8), 4);
				}
				break;

			case 'aprmd5': /* 64 characters that are valid for APRMD5 passwords. */
				$APRMD5 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

				if ($seed)
				{
					return substr(preg_replace('/^\$apr1\$(.{8}).*/', '\\1', $seed), 0, 8);
				}
				else
				{
					$salt = '';
					for ($i = 0; $i < 8; $i++)
					{
						$salt .= $APRMD5{rand(0, 63)};
					}
					return $salt;
				}
				break;

			default:
				$salt = '';
				if ($seed)
				{
					$salt = $seed;
				}
				return $salt;
				break;
		}
	}
	
	//this method is from joomla
	protected function _toAPRMD5($value, $count) {
		/* 64 characters that are valid for APRMD5 passwords. */
		$APRMD5 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

		$aprmd5 = '';
		$count = abs($count);
		while (--$count)
		{
			$aprmd5 .= $APRMD5[$value & 0x3f];
			$value >>= 6;
		}
		return $aprmd5;
	}
	
	/**
	 * Load a member
	 *
	 * @access	protected
	 * @param	string		Username
	 * @return	@e void
	 */
	protected function _loadMember( $username )
	{
		$member = $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'where' => "members_l_username='" . strtolower($username) . "'" ) );
		
		if( $member['member_id'] )
		{
			$this->member_data = IPSMember::load( $member['member_id'], 'extendedProfile,groups' );
		}
	}
}
