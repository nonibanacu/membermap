//<?php

/*
 * Because of this: https://community.invisionpower.com/4bugtrack/active-reports/ipshelpersformnumber-causing-problems-with-floats-on-some-locales-r9015/
 */

class membermap_hook_NumberInput extends _HOOK_CLASS_
{
	public function formatValue()
	{
		if ( in_array( $this->htmlId, array( 'marker_lat', 'marker_lon' ) ) )
		{
			$value = $this->value;

			$value = floatval( $value );

			$value = round( $value, 6 );

			/* Convert decimal point and thousand separators to a way PHP understand */
			$value = str_replace( trim( \IPS\Member::loggedIn()->language()->locale['thousands_sep'] ), '', $value );
			$value = str_replace( trim( \IPS\Member::loggedIn()->language()->locale['decimal_point'] ), '.', $value );

			
			/* If it's not numeric, throw an exception */
			if ( ( !is_numeric( $value ) and $value !== '' and $this->required === FALSE ) or ( !is_numeric( $value ) and $this->required === TRUE ) )
			{
				throw new \InvalidArgumentException( 'form_number_bad' );
			}

			return $value;
		}
		else
		{
			parent::formatValue();
		}
	}
}