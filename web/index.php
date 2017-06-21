<?php
/**
 * APPLICATION CONTROLLER
 * --------------------------------------------------------------------
 * Author V.Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 28.10.2016. Last modified on 21.06.2017
 * --------------------------------------------------------------------
 */

require_once __DIR__ . '/../app/autoload.php';
header('Content-Type: text/html; charset=utf-8');


// Get BASE_PATH, ACTION, PARAMS[]
$action = '';
$alias = '';
$base_path = '';
$path = trim(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/");
$path = mb_strtolower($path);
if (strlen(dirname($_SERVER['SCRIPT_NAME'])) > 1 ) {
    $base_path = dirname($_SERVER['SCRIPT_NAME']);
    $path = substr($path, strlen($base_path));
}
if (empty($base_path)) $alias = $_SERVER['SERVER_NAME'];
if (!empty($alias)) {
    $base_path = 'http://'.$alias;
}
@list($action, $params) = explode("/", $path, 2);
if (empty($action)) $action = 'announces'; // set default action
$params = explode("/", $params);

define("BASE_PATH", $base_path);


// Set language
//$language = "ru";  // locality should be determined here
$language = LangDetect::getInstance()->getBestMatch();
if (defined('LC_MESSAGES')) setlocale(LC_MESSAGES, $language); // Linux
putenv("LC_ALL={$language}"); // Windows

// Specify the location of the translation tables
$domain = "messages"; // which language file to use
bindtextdomain($domain, __DIR__."/../locale");
bind_textdomain_codeset($domain, 'UTF-8');
// Choose domain
textdomain($domain);

if (false === function_exists('gettext')) {
    // it's need for I18n extensions
    echo "You do not have the gettext library installed with PHP.";
    exit(1);
}


// Templating initialization
$loader = new Twig_Loader_Filesystem(TEMPLATES);
$twig = new Twig_Environment($loader,array('cache' => defined('TWIG_CACHE')?TWIG_CACHE:false,));

// Create custom filter and functions for TWIG
$twig->addGlobal('base_path', BASE_PATH);
$twig->addGlobal('navAction', $action);
$twig->addExtension(new Twig_Extensions_Extension_I18n());
$twig->addFilter($filter_sizeHR);


if ($action == 'announces') {
    $page_num = (isset($_POST['page_num']) && is_numeric($_POST['page_num'])) ? (int)$_POST['page_num'] : 1;
    echo $twig->render('announces.twig', array(
            'page_title' => 'Magnet Flea market',
            'account' => Account::getInstance()->get(),
            'announces' => Announce::getInstance()->getPageOfList($page_num, 50),
        )
    );
    exit;

} elseif ($action == 'history' && Account::getInstance()->isAuth()) {
    $page_num = (isset($_POST['page_num']) && is_numeric($_POST['page_num'])) ? (int)$_POST['page_num'] : 1;
    echo $twig->render('history.twig', array(
            'page_title' => 'History of announcements',
            'account' => Account::getInstance()->get(),
            'announces' => History::getInstance()->getPageOfList($page_num, 50),
        )
    );
    exit;

} elseif ($action == 'search' && Account::getInstance()->isAuth()) {
    $page_num = (isset($_POST['page_num']) && is_numeric($_POST['page_num'])) ? (int)$_POST['page_num'] : 1;
    $search_query = isset($_POST['search_query']) ? $_POST['search_query'] : '';
    if (isset($params[0]) && !empty($params[0])) $search_query = urldecode($params[0]);
    echo $twig->render('search.twig', array(
            'page_title' => 'Search',
            'account' => Account::getInstance()->get(),
            'announces' => History::getInstance()->Search($search_query, $page_num, 20),
        )
    );
    exit;

} elseif ($action == 'statistic' && Account::getInstance()->isAdm()) {
    echo $twig->render('statistic.twig', array(
            'page_title' => 'Statistic',
            'account' => Account::getInstance()->get(),
            'statistic' => Announce::getInstance()->getStatistic(),
        )
    );
    exit;

} elseif ($action == 'about') {
    echo $twig->render('about.twig', array(
            'page_title' => 'FAQ',
            'account' => Account::getInstance()->get(),
        )
    );
    exit;
/*
} elseif ($action == 'profile' && Account::getInstance()->isAuth()) {

    echo $twig->render('profile.twig', array(
            'base_path' => $base_path,
            'page_title' => 'Profile',
            'navAction' => $action,
            'account' => Account::getInstance()->get(),
        )
    );
    exit;

} elseif ($action == 'faq') {

    $language = LangDetect::getInstance()->getBestMatch();
    $faq_file_name = TEMPLATES . 'faq_'. $language . '.html';
    if (!file_exists($faq_file_name)){
        $faq_file_name = TEMPLATES . 'faq_'. $language . '.html';
    }

    echo $twig->render('faq.twig', array(
            'base_path' => $base_path,
            'page_title' => 'FAQ',
            'navAction' => $action,
            'account' => Account::getInstance()->get(),
            'custom_faq' => Account::getInstance()->get(),
        )
    );
    exit;
*/
}


// ###################################################################################
// What The Fuck ???
// 307 редирект используется вместо 302го (для повторной отправки данных тем же способом, но в другом месте).
// 303 редирект применяется вместо 302 (для повторной отправки данных, но уже другим способом).
// 308 редирект – для постоянного перенаправления запроса из браузера.
header( "Location: $base_path/", true, 303 ); // transfer to the home page using redirect.
exit; // Bye!