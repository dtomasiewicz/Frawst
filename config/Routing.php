<?php
	/**
	 * This configuration file defines advanced routing information for
	 * your application.
	 * 
	 * To specify a custom routing instruction, add a key/value pair to the
	 * following array. The key should be a Perl-compatible Regular Expression
	 * to match against the requested route; the value should be the (internal)
	 * replacement route to use.
	 * 
	 * For example, if user profiles can be found at /user/profile/<profile ID>,
	 * but you want to simplify this so that they can be found at /u/<profile ID>, you
	 * can add the following key/value pair:
	 * 
	 *   '/u\/(.*)/i' => 'user/profile/$1'
	 *   
	 * Note: Use single quotes to enclose the keys and values, and remember to escape
	 *       slashes and other special RegEx characters with a backslash.
	 * 
	 * Note: By default, sub-requests do not follow these custom routing rules. You must
	 *       pass in your own Route instance if you want them to, though it is recommended
	 *       that you always use internal routes for sub-requests instead, since internal
	 *       routes are less likely to change. 
	 */
	$cfg['rules'] = array(
		
	);