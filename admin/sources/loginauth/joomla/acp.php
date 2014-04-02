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
							'title'			=> 'Joomla Database Connection Type',
							'description'	=> "This field is only used for databases that use connection types, such as MS-SQL",
							'key'			=> 'sodjom_sqltype',
							'type'			=> 'string',
						),
					);
