<?php
/**
 * @brief		Public Controller
 * @author		<a href='http://ipb.silvesterwebdesigns.com'>Stuart Silvester & Martin Aronsen</a>
 * @copyright	(c) 2015 Stuart Silvester & Martin Aronsen
 * @package		IPS Social Suite
 * @subpackage	Member Map
 * @since		20 Oct 2015
 * @version		3.0.0
 */


namespace IPS\membermap\modules\front\membermap;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * showmap
 */
class _showmap extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		
		parent::execute();
	}

	/**
	 * Show the map
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$markers = array();

		/* Rebuild JSON cache if needed */
		if ( ! is_file ( \IPS\ROOT_PATH . '/datastore/membermap_cache/membermap-index.json' ) OR \IPS\Request::i()->rebuildCache === '1' )
		{
			\IPS\membermap\Map::i()->recacheJsonFile();
		}


		$getByUser = intval( \IPS\Request::i()->member_id );

		if ( \IPS\Request::i()->filter == 'getByUser' AND $getByUser )
		{
			$markers = \IPS\membermap\Map::i()->getMarkerByMember( $getByUser );
		}
		else if ( \IPS\Request::i()->filter == 'getOnlineUsers' )
		{
			$markers = \IPS\membermap\Map::i()->getMarkersByOnlineMembers();
		}

		/* Load JS and CSS */
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_leaflet.js', 'membermap', 'front' ) );
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_main.js', 'membermap', 'front' ) );

		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'membermap.css', 'membermap' ) );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'leaflet.css', 'membermap' ) );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'jquery-ui.css', 'membermap' ) );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'Control.FullScreen.css', 'membermap' ) );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'Control.Loading.css', 'membermap' ) );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'leaflet.awesome-markers.css', 'membermap' ) );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'leaflet.contextmenu.css', 'membermap' ) );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'MarkerCluster.css', 'membermap' ) );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'showLoading.css', 'membermap' ) );

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( '__app_membermap' );
		
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
        \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'map' )->showMap( json_encode( $markers ) );

        /* Update session location */
        \IPS\Session::i()->setLocation( \IPS\Http\Url::internal( 'app=membermap', 'front', 'membermap' ), array(), 'loc_membermap_viewing_membermap' );

        /* Things we need to know in the Javascript */
        $is_supmod		= \IPS\Member::loggedIn()->modPermission() ?: 0;
        $member_id		= \IPS\Member::loggedIn()->member_id ?: 0;
        $canEdit		= \IPS\Member::loggedIn()->group['g_membermap_canEdit'] ?: 0;
        $canDelete		= \IPS\Member::loggedIn()->group['g_membermap_canDelete'] ?: 0;

        \IPS\Output::i()->endBodyCode .= <<<EOF
		<script type='text/javascript'>
			ips.setSetting( 'is_supmod', {$is_supmod} );
			ips.setSetting( 'member_id', {$member_id} );
			ips.setSetting( 'membermap_canEdit', {$canEdit} );
			ips.setSetting( 'membermap_canDelete', {$canDelete} );

			ips.membermap.initMap();
		</script>
EOF;
	}

	/**
	 * Loads add/update location form
	 *
	 * @return	void
	 */
	protected function add()
	{
		if ( ! \IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->error( 'no_permission', '', 403, '' );
		}

		/* Get the members location, if it exists */
		$existing = \IPS\membermap\Map::i()->getMarkerByMember( \IPS\Member::loggedIn()->member_id );

		\IPS\Output::i()->title	= \IPS\Member::loggedIn()->language()->addToStack( ( ! $existing ? 'membermap_button_addLocation' : 'membermap_button_editLocation' ) );

		/* Check permissions */
		if ( $existing AND ! \IPS\Member::loggedIn()->group['g_membermap_canEdit'] )
		{
			\IPS\Output::i()->error( 'membermap_error_cantEdit', '', 403, '' );
		}
		else if ( ! \IPS\Member::loggedIn()->group['g_membermap_canAdd'] )
		{
			\IPS\Output::i()->error( 'membermap_error_cantAdd', '123', 403, '' );
		}

		$geoLocForm =  new \IPS\Helpers\Form( 'membermap_form_geoLocation', NULL, NULL, array( 'id' => 'membermap_form_geoLocation' ) );
		$geoLocForm->class = 'ipsForm_vertical ipsType_center';

		$geoLocForm->addHeader( 'membermap_current_location' );
		$geoLocForm->addHtml( '<li class="ipsType_center"><i class="fa fa-fw fa-4x fa-location-arrow"></i></li>' );
		$geoLocForm->addHtml( '<li class="ipsType_center">This will use a feature in your browser to detect your current location using GPS, Cellphone triangulation, Wifi, Router, or IP address</li>' );
		$geoLocForm->addButton( 'membermap_current_location', 'button', NULL, 'ipsButton ipsButton_primary', array( 'id' => 'membermap_currentLocation' ) );


		$form = new \IPS\Helpers\Form( 'membermap_form_location', NULL, NULL, array( 'id' => 'membermap_form_location' ) );
		$form->class = 'ipsForm_vertical ipsType_center';

		$form->addHeader( 'Search for your location' );
		$form->add( new \IPS\Helpers\Form\Text( 'membermap_location', '', FALSE, array( 'placeholder' => "Enter your address / city / county / country, you can be as specific as you like" ), NULL, NULL, NULL, 'membermap_location' ) );
		$form->addButton( 'save', 'submit', NULL, 'ipsPos_center ipsButton ipsButton_primary', array( 'id' => 'membermap_locationSubmit' ) );

		$form->hiddenValues['lat'] = \IPS\Request::i()->lat;
		$form->hiddenValues['lng'] = \IPS\Request::i()->lng;

		if ( $values = $form->values() )
		{
			try
			{
				$values['member_id'] = \IPS\Member::loggedIn()->member_id;

				\IPS\membermap\Map::i()->saveMarker( $values );
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=membermap&dropBrowserCache=1' ) );
				return;
			}
			catch( \Exception $e )
			{
				$form->error	= \IPS\Member::loggedIn()->language()->addToStack( 'membermap_' . $e->getMessage() );
				
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'map' )->addLocation( $geoLocForm, $form );
				return;
			}
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'map' )->addLocation( $geoLocForm, $form );
	}

	/**
	 * Delete a marker
	 *
	 * @return	void
	 */
	protected function delete()
	{
		if ( ! \IPS\Member::loggedIn()->member_id OR ! intval( \IPS\Request::i()->member_id ) )
		{
			\IPS\Output::i()->error( 'no_permission', '1', 403, '' );
		}

		/* Get the marker */
		$existing = \IPS\membermap\Map::i()->getMarkerByMember( intval( \IPS\Request::i()->member_id ) );

		if ( $existing['member_id'] )
		{
			$is_supmod		= \IPS\Member::loggedIn()->modPermission() ?: 0;

			if ( $is_supmod OR ( $existing['member_id'] == \IPS\Member::loggedIn()->member_id AND \IPS\Member::loggedIn()->group['g_membermap_canDelete'] ) )
			{
				\IPS\membermap\Map::i()->deleteMarker( $existing['member_id'] );
				\IPS\Output::i()->json( 'OK' );
			}
		}

		/* Fall back to a generic error */
		\IPS\Output::i()->error( 'no_permission', '2', 403, '' );
	}

	protected function embed()
	{
		$this->manage();

		\IPS\Output::i()->title = NULL;
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( \IPS\Output::i()->output ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
	}
}