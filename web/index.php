<?php
/**
 * --------------------------------------------------------------------
 *                       APPLICATION CONTROLLER
 * --------------------------------------------------------------------
 * Author V.Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 28.10.2016. Last modified on 10.11.2016
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

// TEST
//echo  $base_path . '<br>'; echo  $action . '<br>';


// Set language to Ukrainian
//$language = "uk_UA.UTF-8";  // locality should be determined here
//$language = "uk";  // locality should be determined here
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
$twig = new Twig_Environment($loader,array('cache' => defined('TWIG_CACHE')?TWIG_CACHE:NULL,));
$twig->addExtension(new Twig_Extensions_Extension_I18n());
$twig->addFilter($filter_sizeHR);

if ($action == 'announces') {

    //var_dump(Announce::getInstance()->getHumanReadable());

    echo $twig->render('announces.twig', array(
            'base_path' => $base_path,
            'page_title' => 'Magnet Flea market',
            'navAction' => $action,
            'account' => Account::getInstance()->get(),
            //'current_date' => date('d.m.Y H:i',time()),
            'announces' => Announce::getInstance()->getHumanReadable(),
        )
    );
    exit;

} elseif ($action == 'history' && Account::getInstance()->isAuth()) {

    echo $twig->render('history.twig', array(
            'base_path' => $base_path,
            'page_title' => 'History of announcements',
            'navAction' => $action,
            'account' => Account::getInstance()->get(),
            'current_date' => date('d.m.Y H:i',time()),
        )
    );
    exit;

} elseif ($action == 'search' && Account::getInstance()->isAuth()) {

    $search_query = isset($_POST['search_query']) ? $_POST['search_query'] : '';
    //var_dump(BitTorrent::getInstance()->Search($search_query));

    echo $twig->render('search.twig', array(
            'base_path' => $base_path,
            'page_title' => 'Search',
            'navAction' => $action,
            'account' => Account::getInstance()->get(),
            'current_date' => date('d.m.Y H:i',time()),
            'search_query' => $search_query,
            'search_result' => BitTorrent::getInstance()->Search($search_query),
        )
    );

    exit;

} elseif ($action == 'faq' && Account::getInstance()->isAuth()) {

    echo $twig->render('faq.twig', array(
            'base_path' => $base_path,
            'page_title' => 'FAQ',
            'navAction' => $action,
            'account' => Account::getInstance()->get(),
            'current_date' => date('d.m.Y H:i',time()),
        )
    );

    exit;

} elseif ($action == 'stats' && Account::getInstance()->isAdm()) {

    echo $twig->render('empty_page.twig', array(
            'base_path' => $base_path,
            'page_title' => 'Statistic',
            'navAction' => $action,
            'account' => Account::getInstance()->get(),
            'current_date' => date('d.m.Y H:i',time()),
        )
    );

    exit;

} elseif ($action == 'profile' && Account::getInstance()->isAuth()) {


    echo $twig->render('empty_page.twig', array(
            'base_path' => $base_path,
            'page_title' => 'Profile',
            'navAction' => $action,
            'account' => Account::getInstance()->get(),
            'current_date' => date('d.m.Y H:i',time()),
        )
    );
    exit;
}


// ###################################################################################
// What The Fuck ???
// 307 редирект используется вместо 302го (для повторной отправки данных тем же способом, но в другом месте).
// 303 редирект применяется вместо 302 (для повторной отправки данных, но уже другим способом).
// 308 редирект – для постоянного перенаправления запроса из браузера.
header( "Location: $base_path/", true, 303 ); // transfer to the home page using redirect.
exit; // Bye!