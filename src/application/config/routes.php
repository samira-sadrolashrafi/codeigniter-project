<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/

$route['default_controller'] = 'auth/login';

$route['404_override'] = '';

$route['translate_uri_dashes'] = FALSE;


// Auth pages
$route['login']['get'] = 'auth/login';

$route['register']['get'] = 'auth/register';


// Auth API
$route['auth/login']['post'] = 'auth_api/login';

$route['auth/register']['post'] = 'auth_api/register';

$route['auth/logout']['post'] = 'auth_api/logout';


// Dashboard
$route['dashboard']['get'] = 'dashboard/index';

$route['api/dashboard/balance']['get'] = 'dashboard_api/balance';

$route['api/dashboard/balance']['post'] = 'dashboard_api/update_balance';


// Category API

$route['api/categories']['get'] = 'category_api/index';

$route['api/categories/(:num)']['get'] = 'category_api/show';

$route['api/categories/store']['post'] = 'category_api/store';

$route['api/categories/update/(:num)']['post'] = 'category_api/update/$1';

$route['api/categories/(:num)/delete']['post'] = 'category_api/delete/$1';

$route['api/categories/(:num)/restore']['post'] = 'category_api/restore/$1';