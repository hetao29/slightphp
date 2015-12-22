<?php
/*{{{LICENSE
+-----------------------------------------------------------------------+
| SlightPHP Framework                                                   |
+-----------------------------------------------------------------------+
| This program is free software; you can redistribute it and/or modify  |
| it under the terms of the GNU General Public License as published by  |
| the Free Software Foundation. You should have received a copy of the  |
| GNU General Public License along with this program.  If not, see      |
| http://www.gnu.org/licenses/.                                         |
| Copyright (C) 2008-2009. All Rights Reserved.                         |
+-----------------------------------------------------------------------+
| Supports: http://www.slightphp.com                                    |
+-----------------------------------------------------------------------+
}}}*/

/**
 * @package SlightPHP
 */
class SLog
{
    public static $CONSOLE = true;

    public static $LOG = false;

    public static $LOGFILE = "";

    // Log level definition
    const LOG_LEVEL_NONE = 0x00;
    const LOG_LEVEL_FATAL = 0x01;
    const LOG_LEVEL_WARNING = 0x02;
    const LOG_LEVEL_NOTICE = 0x04;
    const LOG_LEVEL_TRACE = 0x08;
    const LOG_LEVEL_DEBUG = 0x10;
    const LOG_LEVEL_ALL = 0xFF;

    // Log type definition
    const LOG_TYPE_LOCAL_LOG = 'LOCAL_LOG';
    const LOG_TYPE_NET_LOG = 'NET_LOG';

    /**
     * @var array
     */
    public static $logLevelMap = array(
        self::LOG_LEVEL_NONE    => 'NONE',
        self::LOG_LEVEL_FATAL   => 'FATAL',
        self::LOG_LEVEL_WARNING => 'WARNING',
        self::LOG_LEVEL_NOTICE  => 'NOTICE',
        self::LOG_LEVEL_TRACE   => 'TRACE',
        self::LOG_LEVEL_DEBUG   => 'DEBUG',
        self::LOG_LEVEL_ALL     => 'ALL',
    );

    /**
     * @var array
     */
    public static $logTypes = array(
        self::LOG_TYPE_LOCAL_LOG,
        self::LOG_TYPE_NET_LOG,
    );

    /**
     * Log output device type, can be "LOCAL_LOG", "STDOUT"
     *
     * @var string
     */
    protected $type;

    /**
     * Log level
     *
     * @var int
     */
    protected $level;

    /**
     * Log file path for local log file, or module name for comLog
     *
     * @var string
     */
    protected $path;

    /**
     * Log file name
     *
     * @var string
     */
    protected $filename;

    /**
     * Client IP
     *
     * @var string
     */
    protected $clientIP;

    /**
     * Log Id for current request
     *
     * @var uint
     */
    protected $logId;

    /**
     * PHP start time of current request
     *
     * @var uint
     */
    protected $startTime;

    /**
     * @var log
     */
    private static $instance = null;

    /**
     * Constructor
     *
     * @param array $conf
     * @param uint $startTime
     */
    private function __construct($conf, $startTime)
    {
        $this->type     = $conf->type;
        $this->level    = $conf->level;
        $this->path     = $conf->path;
        $this->filename = $conf->filename;

        $this->startTime = $startTime;
        $this->logId     = $this->__logId();
        $this->clientIP  = SUtil::getIP();
        if ($this->type === self::LOG_TYPE_NET_LOG) {
            openlog($conf['appName'], LOG_PID | LOG_PERROR, LOG_LOCAL1);
        }

    }

    /**
     * @return log
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            $startTime      = microtime(true) * 1000;
            $logConf        = !empty($GLOBALS['LOG_CONF']) ? $GLOBALS['LOG_CONF'] : 'default';
            $logConfig = SConfig::getConfig(ROOT_CONFIG."/log.conf", $logConf);

            if (!empty($logConfig->filename)) {
                $logConfig->filename = sprintf($logConfig->filename, date('Ymd'));
                self::$instance = new SLog($logConfig, $startTime);
            } else {
                $stdClass = new stdClass();
                $stdClass->appName = 'AllLog';
                $stdClass->type = 'LOCAL_LOG';
                $stdClass->level = '0x15';
                $stdClass->path = '/tmp';
                $stdClass->filename = 'All_log.'.date('Ymd');

                self::$instance = new SLog($stdClass, $startTime);
            }

        }

        return self::$instance;
    }

    /**
     * Write debug log
     *
     * @return int
     */
    public static function debug()
    {
        $args = func_get_args();

        return SLog::getInstance()->writeLog(self::LOG_LEVEL_DEBUG, $args);
    }

    /**
     * Write trace log
     *
     * @return int
     */
    public static function trace()
    {
        $args = func_get_args();

        return SLog::getInstance()->writeLog(self::LOG_LEVEL_TRACE, $args);
    }

    /**
     * Write notice log
     *
     * @return int
     */
    public static function notice()
    {
        $args = func_get_args();

        return SLog::getInstance()->writeLog(self::LOG_LEVEL_NOTICE, $args);
    }

    /**
     * Write warning log
     *
     * @return int
     */
    public static function warning()
    {
        $args = func_get_args();

        return SLog::getInstance()->writeLog(self::LOG_LEVEL_WARNING, $args);
    }

    /**
     * Write fatal log
     *
     * @return int
     */
    public static function fatal()
    {
        $args = func_get_args();

        return SLog::getInstance()->writeLog(self::LOG_LEVEL_FATAL, $args);
    }

    /**
     * Get logId for current http request
     *
     * @return int
     */
    public static function logId()
    {
        return SLog::getInstance()->logId;
    }

    /**
     * @param $logId
     */
    public static function setLogId($logId)
    {
        SLog::getInstance()->logId = $logId;
    }


    /**
     * Get the real remote client's IP
     *
     * @return string
     */
    public static function getClientIP()
    {
        return SLog::getInstance()->clientIP;
    }

    /**
     * Write log
     *
     * @param int $level Log level
     * @param array $args format string and parameters
     *
     * @return int
     */
    protected function writeLog($level, Array $args)
    {
        if ($level > $this->level || !isset(self::$logLevelMap[$level])) {
            return 0;
        }
        $timeUsed = microtime(true) * 1000 - $this->startTime;

        $fmt = array_shift($args);
        $str = vsprintf($fmt, $args);
        if ($level == self::LOG_LEVEL_NOTICE) {
            $str = sprintf(
                "%s:@@%s@@host[%s]@@ip[%s]@@logId[%u]@@uri[%s]@@time_used[%d]@@%s\n",
                self::$logLevelMap[$level],
                date('Y-m-d H:i:s:', time()),
                $_SERVER['HTTP_HOST'],
                $this->clientIP,
                $this->logId,
                isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
                $timeUsed, $str
            );
        } else {
            $str = sprintf(
                "%s: %s host[%s] ip[%s] logId[%u] uri[%s] time_used[%d] %s\n",
                self::$logLevelMap[$level],
                date('Y-m-d H:i:s:', time()),
                $_SERVER['HTTP_HOST'],
                $this->clientIP,
                $this->logId,
                isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
                $timeUsed, $str
            );
        }

        if ($this->type === self::LOG_TYPE_LOCAL_LOG) {
            $filename = $this->path.'/'.$this->filename;
            if ($level < self::LOG_LEVEL_NOTICE) {
                $filename .= '.wf';
            }

            $strLen = file_put_contents($filename, $str, FILE_APPEND | LOCK_EX);
            @chmod($filename, 0777);

            return $strLen;
        } else {
            syslog(LOG_DEBUG, $str);

            return strlen($str);
        }
    }

    /**
     * @return int
     */
    private function __logId()
    {
        $arr = gettimeofday();

        return ((($arr['sec'] * 100000 + $arr['usec'] / 10) & 0x7FFFFFFF));
    }

    /**
     * @param $info
     */
    public static function write($info)
    {
        if (is_object($info) || is_array($info)) {
            $infoText = var_export($info, true);
        } elseif (is_bool($info)) {
            $infoText = $info ? "true" : "false";
        } else {
            $infoText = $info;
        }
        $infoText = "[".date("Y-m-d H:i:s")."] ".$infoText;

        if (!empty(SLog::$LOGFILE)) {
            error_log($infoText."\r\n", 3, SLog::$LOGFILE);
        } else {
            error_log($infoText);
        }

        if (SLog::$CONSOLE)
            echo "<!--\n".$infoText."\n-->";
    }
}

