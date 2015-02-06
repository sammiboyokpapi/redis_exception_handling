<?php
/**
 * Description of Options
 *
 * @author Oluwasegun
 */

class Options {

	//INFO: predis connection parameter
	public static $redis_scheme 	= 'tcp';
	public static $redis_host 		= '176.1.1.1'; //specify other IP for external connection
	public static $redis_port 		= '6379';
	public static $redis_database 	= 5; //0 is the default database


	//INFO: other important stuff
	public static $query_limit 		= 300000000000;

	//INFO: data sheet for predis
	public static $_prequest		= 'request';
	public static $_prequest_key	= '_reqid';


	public static $_subscription 		= 'subscription';
	public static $_subscription_key 	= '_subkey';
}
