<?php

/* Dariuz Rubin */
// define Ringcentral info

   
/*defined('RINGCENTRAL_APP_KEY') || define('RINGCENTRAL_APP_KEY','_15Ah5iSTOKba5_Lc-RD9Q');
defined('RINGCENTRAL_APP_SECRET') || define('RINGCENTRAL_APP_SECRET','1JM7I7ZoS3mbqnsywmDWUwpnkJGciBSE-ma5EcwlKNeg');
defined('RINGCENTRAL_APP_SERVER') || define('RINGCENTRAL_APP_SERVER','https://platform.devtest.ringcentral.com');
defined('RINGCENTRAL_APP_USERNAME') || define('RINGCENTRAL_APP_USERNAME','14156305104');
defined('RINGCENTRAL_APP_EXTENSION') || define('RINGCENTRAL_APP_EXTENSION','101');
defined('RINGCENTRAL_APP_PASSWORD') || define('RINGCENTRAL_APP_PASSWORD','P@ssw0rd');*/
defined('RINGCENTRAL_APP_KEY') || define('RINGCENTRAL_APP_KEY','Bqei0WvGS3C1B7oGaS4U1w');
defined('RINGCENTRAL_APP_SECRET') || define('RINGCENTRAL_APP_SECRET','U7AwtEhgSbG_moIHGSfiQgUVZGQPkKRNersgfg_DCvoA');
defined('RINGCENTRAL_APP_SERVER') || define('RINGCENTRAL_APP_SERVER','https://platform.ringcentral.com');
defined('RINGCENTRAL_APP_USERNAME') || define('RINGCENTRAL_APP_USERNAME','18669448315');
defined('RINGCENTRAL_APP_EXTENSION') || define('RINGCENTRAL_APP_EXTENSION','');
defined('RINGCENTRAL_APP_PASSWORD') || define('RINGCENTRAL_APP_PASSWORD','B3aming*');
 

/*==============*/
// define path to application directory
defined('APPLICATION_PATH') || define('APPLICATION_PATH', __DIR__ . '/application');

defined('CONFIGFILE') || define('CONFIGFILE', dirname(dirname(APPLICATION_PATH)). DIRECTORY_SEPARATOR.'etc'. DIRECTORY_SEPARATOR. 'bwerp.ini');


// Define application environment
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// set include path
set_include_path(realpath(APPLICATION_PATH . '/../library')
        . PATH_SEPARATOR . get_include_path());

require_once 'Zend/Config/Ini.php';
require_once 'Zend/Application.php';

class Application {

    public static $env;
    private static $_db = null;
    private static $_authnet = null;

    public static function bootstrap($resource = null) {
        include_once 'Zend/Loader/Autoloader.php';
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('Saffron_');
        $application = new Zend_Application(self::_getEnv(), self::_getConfig());
        
        self::setupDb();

        return $application->getBootstrap()->bootstrap($resource);
    }

    public static function run() {  
       self::bootstrap()->run();
    }

    private static function _getEnv() {             
        return self::$env ? : APPLICATION_ENV;
    }

    private static function _getConfig() {
        $env = self::_getEnv();        
        //$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', $env, true);        
        $config = new Zend_Config_Ini(CONFIGFILE, $env, true);
        return $config->toArray();
       
    }
   
    public static function setupDb() {
        try {
            $resources = self::_getConfig();
            $db = Zend_Db::factory('Pdo_Mysql', $resources['resources']['multidb']['core']);
            $db->getConnection();
            $businessDb = Zend_Db::factory('Pdo_Mysql', $resources['resources']['multidb']['bw']);
            $businessDb->getConnection();
            Zend_Registry::set('db', $db);
            Zend_Registry::set('bwbusiness', $businessDb);
            
            if (preg_match('/shipstation/i', $_SERVER['REQUEST_URI']) ) {
                $wscDb = Zend_Db::factory('Pdo_Mysql', $resources['resources']['multidb']['wsc']);
                $wscDb->getConnection();
                $tscDb = Zend_Db::factory('Pdo_Mysql', $resources['resources']['multidb']['tsc']);
                $tscDb->getConnection();
                $glwDb = Zend_Db::factory('Pdo_Mysql', $resources['resources']['multidb']['glw']);
                $glwDb->getConnection();
                $dvwDb = Zend_Db::factory('Pdo_Mysql', $resources['resources']['multidb']['dvw']);
                $dvwDb->getConnection();    
                Zend_Registry::set('whitesmi_rdm', $wscDb);
                Zend_Registry::set('grinner_rdm', $tscDb);
                Zend_Registry::set('glamjam_redeem', $glwDb);
                Zend_Registry::set('divinty_rdm', $dvwDb);
            }
                        
        } catch (Zend_Db_Adapter_Exception $e) {
            # perhaps a failed login credential, or perhaps the RDBMS is not running
            die('A nasty boo-boo occurred: ' . $e->getMessage());
        } catch (Zend_Exception $e) {
            # perhaps factory() failed to load the specified Adapter class
            die('Another nasty boo-boo occurred: ' . $e->getMessage());
        }        
    }
    
    static public function getDBConnection() {
    
        if (self::$_db != null) {
            return self::$_db;
        } 
        $resources = self::_getConfig();

        self::$_db = Zend_Db::factory('Pdo_Mysql',
                                       $resources['resources']['multidb']['core']);
        Zend_Db_Table::setDefaultAdapter(self::$_db);
        return self::$_db;
    }
   
}