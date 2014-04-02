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
		$hash 				= $joomla_member['password'];
		
		// If we are using phpass
		if (strpos($hash, '$P$') === 0)
		{
			require_once(dirname(__FILE__) . "/phpass/PasswordHash.php");
			// Use PHPass's portable hashes with a cost of 10.
			$phpass = new PasswordHash(10, true);

			$match = $phpass->CheckPassword($password, $hash);
		}
		else
		{
			// Check the password
			$parts = explode(':', $hash);
			$crypt = $parts[0];
			$salt  = @$parts[1]; 
			$testcrypt = md5($password . $salt) . ($salt ? ':' . $salt : '');

			$match = $this->timingSafeCompare($hash, $testcrypt);
		}

		if ($match !== TRUE) {
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

	// this method is from joomla
	public static function timingSafeCompare($known, $unknown)
	{
		// Prevent issues if string length is 0
		$known .= chr(0);
		$unknown .= chr(0);

		$knownLength = strlen($known);
		$unknownLength = strlen($unknown);

		// Set the result to the difference between the lengths
		$result = $knownLength - $unknownLength;

		// Note that we ALWAYS iterate over the user-supplied length to prevent leaking length info.
		for ($i = 0; $i < $unknownLength; $i++)
		{
			// Using % here is a trick to prevent notices. It's safe, since if the lengths are different, $result is already non-0
			$result |= (ord($known[$i % $knownLength]) ^ ord($unknown[$i]));
		}

		// They are only identical strings if $result is exactly 0...
		return $result === 0;
	}

}
