<?php

namespace Amsat\FoxDb;

class ConnectionManager {

    private $_mysqli_conn = null;
    private $_pdo_conn = null;

    private $_config = null;

    public function __construct($configPath=null) {
        $this->_loadConfig($configPath);
    }

    private function _loadConfig($configPath=null) {
        // Load DB Config
        if($configPath == null) {
            $configPath = __DIR__."/../../../config/foxdb_readonly.local.php";
        }
        $this->config = require $configPath;
    }

    public function getMysqliConnection($reconnect=false) {
        if($this->_mysqli_conn !== null && !$reconnect) {
            return $this->_mysqli_conn;
        }

        $c = $this->_config;
        $this->_mysqli_conn = new \mysqli($c['host'], $c['username'], $c['password'], $c['database']);

        return $this->_mysqli_conn;
    }

    public function getPdoConnection($reconnect=false) {
        if($this->_pdo_conn != null && !$reconnect) {
            return $this->_pdo_conn;
        }

        $c = $this->config;

        $dsn = "mysql:host={$c['host']};dbname={$c['database']};charset=utf8";

        $this->_pdo_conn = new \PDO($dsn, $c['username'], $c['password']);

        return $this->_pdo_conn;
    }

}
