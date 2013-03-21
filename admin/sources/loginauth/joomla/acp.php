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

$config		= array(
					array(
							'title'			=> 'Joomla Database Host',
							'description'	=> "Usually 'localhost' if database is you do not have more than one server",
							'key'			=> 'sodjom_host',
							'type'			=> 'string'
						),
					array(
							'title'			=> 'Joomla Database Port',
							'description'	=> 'Leave blank if not sure',
							'key'			=> 'sodjom_dbport',
							'type'			=> 'string'
						),
					array(
							'title'			=> 'Joomla Database Name',
							'description'	=> 'The name of the Joomla database you want to authenticate against',
							'key'			=> 'sodjom_dbname',
							'type'			=> 'string'
						),
					array(
							'title'			=> 'Joomla Database Username',
							'description'	=> 'Username for your Joomla database',
							'key'			=> 'sodjom_dbuser',
							'type'			=> 'string'
						),
					array(
							'title'			=> 'Joomla Database Password',
							'description'	=> "Password for your Joomla database",
							'key'			=> 'sodjom_dbpwd',
							'type'			=> 'string'
						),
					array(
							'title'			=> 'Joomla Database Table Prefix',
							'description'	=> 'If your Joomla database has table prefixes, enter it here.',
							'key'			=> 'sodjom_dbprefix',
							'type'			=> 'string'
						),
						array(
							'title'			=> 'Joomla Encryption',
							'description'	=> "How are passwords hashed in Joomla? default is MD5-HEX" ,
							'key'			=> 'sodjom_hashtype',
							'type'			=> 'select',
							'options'		=> array( array( 'md5-hex', 'MD5-HEX' ), array( 'plain', 'Plain Text' ), array('sha', 'SHA'), array('crypt', 'CRYPT'), array('md5-base64', 'MD5-BASE64'), array('ssha', 'SSHA'), array('smd5', 'SMD5'), array('aprmd5', 'APRMD5'), )
						),
					array(
							'title'			=> 'Joomla Database Connection Type',
							'description'	=> "This field is only used for databases that use connection types, such as MS-SQL",
							'key'			=> 'sodjom_sqltype',
							'type'			=> 'string',
						),
					);
