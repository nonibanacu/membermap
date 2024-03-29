<?php
/**
 * @brief		Build process plugin
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Member Map
 * @since		22 Oct 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\membermap\extensions\core\Build;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Build process plugin
 */
class _combineCss
{
	/**
	 * Build
	 *
	 * @return	void
	 * @throws	\RuntimeException
	 */
	public function build()
	{
	}
	
	/**
	 * Finish Build
	 * Moved the pbckcode acs.js file over from development as CKEditor's build routine seems to break it
	 *
	 * @return	void
	 */
	protected function finish()
	{
	}
}