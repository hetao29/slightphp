<?php
/** class.pop3.php
.---------------------------------------------------------------------------.
|  Software: PHPMailer - PHP email class                                    |
|   Version: 2.2.1                                                          |
|   Contact: via sourceforge.net support pages (also www.codeworxtech.com)  |
|      Info: http://phpmailer.sourceforge.net                               |
|   Support: http://sourceforge.net/projects/phpmailer/                     |
| ------------------------------------------------------------------------- |
|    Author: Andy Prevost (project admininistrator)                         |
|    Author: Brent R. Matzelle (original founder)                           |
| Copyright (c) 2004-2007, Andy Prevost. All Rights Reserved.               |
| Copyright (c) 2001-2003, Brent R. Matzelle                                |
| ------------------------------------------------------------------------- |
|   License: Distributed under the Lesser General Public License (LGPL)     |
|            http://www.gnu.org/copyleft/lesser.html                        |
| This program is distributed in the hope that it will be useful - WITHOUT  |
| ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or     |
| FITNESS FOR A PARTICULAR PURPOSE.                                         |
| ------------------------------------------------------------------------- |
| We offer a number of paid services (www.codeworxtech.com):                |
| - Web Hosting on highly optimized fast and secure servers                 |
| - Technology Consulting                                                   |
| - Oursourcing (highly qualified programmers and graphic designers)        |
'---------------------------------------------------------------------------'
*/

/**
 * POP Before SMTP Authentication Class
 * Version 2.1
 *
 * Author: Richard Davey (rich@corephp.co.uk)
 * Modifications: Andy Prevost
 * License: LGPL, see PHPMailer License
 *
 * Specifically for PHPMailer to allow POP before SMTP authentication.
 * Does not yet work with APOP - if you have an APOP account, contact me
 * and we can test changes to this script.
 *
 * This class is based on the structure of the SMTP class by Chris Ryan
 *
 * This class is rfc 1939 compliant and implements all the commands
 * required for POP3 connection, authentication and disconnection.
 *
 * @package PHPMailer
 * @author Richard Davey
 */

namespace SlightPHP;
/** 提供 SMTP 发送之前的 POP3 连接和认证。 */
class POP3 {
  /**
   * Default POP3 port
   * @var int
   */
  public $POP3_PORT = 110;

  /**
   * Default Timeout
   * @var int
   */
  public $POP3_TIMEOUT = 30;

  /**
   * POP3 Carriage Return + Line Feed
   * @var string
   */
  public $CRLF = "\r\n";

  /**
   * Displaying Debug warnings? (0 = now, 1+ = yes)
   * @var int
   */
  public $do_debug = 2;

  /**
   * POP3 Mail Server
   * @var string
   */
  public $host;

  /**
   * POP3 Port
   * @var int
   */
  public $port;

  /**
   * POP3 Timeout Value
   * @var int
   */
  public $tval;

  /**
   * POP3 Username
   * @var string
   */
  public $username;

  /**
   * POP3 Password
   * @var string
   */
  public $password;

  /**#@+
   * @access private
   */
  private $pop_conn;
  private $connected;
  private $error;     //  Error log array
  /**#@-*/

  /**
   * Constructor, sets the initial values
   * @access public
   * @return POP3
   */
  public function __construct() {
    $this->pop_conn  = 0;
    $this->connected = false;
    $this->error     = null;
  }

  /**
   * 连接、登录后断开 POP3，用于 SMTP 前置认证。
   * PHP 8 中 port 和 tval 位于必需参数之前，调用时也必须提供。
   * @access public
   * @param string $host POP3 服务器地址。
   * @param int|false $port 端口；false 使用配置的默认端口。
   * @param int|float|false $tval 超时秒数；false 使用配置的默认值。
   * @param string $username 登录用户名。
   * @param string $password 登录密码。
   * @param int $debug_level 调试输出级别。
   * @return bool 连接并登录成功时为 true。
   * @throws \TypeError 在 PHP 8 中，连接失败后仍尝试断开无效流时可能抛出。
   */
  public function Authorise ($host, $port = false, $tval = false, $username, $password, $debug_level = 0) {
    $this->host = $host;

    //  If no port value is passed, retrieve it
    if ($port == false) {
      $this->port = $this->POP3_PORT;
    } else {
      $this->port = $port;
    }

    //  If no port value is passed, retrieve it
    if ($tval == false) {
      $this->tval = $this->POP3_TIMEOUT;
    } else {
      $this->tval = $tval;
    }

    $this->do_debug = $debug_level;
    $this->username = $username;
    $this->password = $password;

    //  Refresh the error log
    $this->error = null;

    //  Connect
    $result = $this->Connect($this->host, $this->port, $this->tval);

    if ($result) {
      $login_result = $this->Login($this->username, $this->password);

      if ($login_result) {
        $this->Disconnect();

        return true;
      }

    }

    //  We need to disconnect regardless if the login succeeded
    $this->Disconnect();

    return false;
  }

  /**
   * 连接 POP3 服务器并检查问候响应。
   * @access public
   * @param string $host POP3 服务器地址。
   * @param int|false $port 端口；直接调用时 false 传给 fsockopen，不会替换成默认端口。
   * @param int|float $tval 连接和读取超时秒数。
   * @return bool|null 连接失败时为 false，问候响应不是 +OK 时为 null。
   */
  public function Connect ($host, $port = false, $tval = 30) {
    //  Are we already connected?
    if ($this->connected) {
      return true;
    }

    /*
    On Windows this will raise a PHP Warning error if the hostname doesn't exist.
    Rather than supress it with @fsockopen, let's capture it cleanly instead
    */

    set_error_handler(array(&$this, 'catchWarning'));

    //  Connect to the POP3 server
    $this->pop_conn = fsockopen($host,    //  POP3 Host
                  $port,    //  Port #
                  $errno,   //  Error Number
                  $errstr,  //  Error Message
                  $tval);   //  Timeout (seconds)

    //  Restore the error handler
    restore_error_handler();

    //  Does the Error Log now contain anything?
    if ($this->error && $this->do_debug >= 1) {
      $this->displayErrors();
    }

    //  Did we connect?
    if ($this->pop_conn == false) {
      //  It would appear not...
      $this->error = array(
        'error' => "Failed to connect to server $host on port $port",
        'errno' => $errno,
        'errstr' => $errstr
      );

      if ($this->do_debug >= 1) {
        $this->displayErrors();
      }

      return false;
    }

    //  Increase the stream time-out

    //  Check for PHP 4.3.0 or later
    if (version_compare(phpversion(), '5.0.0', 'ge')) {
      stream_set_timeout($this->pop_conn, $tval, 0);
    } else {
      //  Does not work on Windows
      if (substr(PHP_OS, 0, 3) !== 'WIN') {
        socket_set_timeout($this->pop_conn, $tval, 0);
      }
    }

    //  Get the POP3 server response
    $pop3_response = $this->getResponse();

    //  Check for the +OK
    if ($this->checkResponse($pop3_response)) {
    //  The connection is established and the POP3 server is talking
    $this->connected = true;
      return true;
    }

  }

  /**
   * 登录 POP3 服务器（不支持 APOP）。
   * @access public
   * @param string $username 用户名；空字符串使用实例保存的用户名。
   * @param string $password 密码；空字符串使用实例保存的密码。
   * @return bool 登录是否成功。
   * @throws \TypeError 在 PHP 8 中，未连接时仍尝试写入无效流可能抛出。
   */
  public function Login ($username = '', $password = '') {
    if ($this->connected == false) {
      $this->error = 'Not connected to POP3 server';

      if ($this->do_debug >= 1) {
        $this->displayErrors();
      }
    }

    if (empty($username)) {
      $username = $this->username;
    }

    if (empty($password)) {
      $password = $this->password;
    }

    $pop_username = "USER $username" . $this->CRLF;
    $pop_password = "PASS $password" . $this->CRLF;

    //  Send the Username
    $this->sendString($pop_username);
    $pop3_response = $this->getResponse();

    if ($this->checkResponse($pop3_response)) {
      //  Send the Password
      $this->sendString($pop_password);
      $pop3_response = $this->getResponse();

      if ($this->checkResponse($pop3_response)) {
        return true;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }

  /**
   * 发送 QUIT 并关闭 POP3 连接。
   * @access public
   * @return void
   * @throws \TypeError 在 PHP 8 中，连接流无效时可能抛出。
   */
  public function Disconnect () {
    $this->sendString('QUIT');

    fclose($this->pop_conn);
  }

  /////////////////////////////////////////////////
  //  Private Methods
  /////////////////////////////////////////////////

  /**
   * Get the socket response back.
   * $size is the maximum number of bytes to retrieve
   * @access private
   * @param integer $size
   * @return string|false 响应文本；读取失败时为 false。
   */
  private function getResponse ($size = 128) {
    $pop3_response = fgets($this->pop_conn, $size);

    return $pop3_response;
  }

  /**
   * Send a string down the open socket connection to the POP3 server
   * @access private
   * @param string $string
   * @return int|false 已写入字节数；写入失败时为 false。
   */
  private function sendString ($string) {
    $bytes_sent = fwrite($this->pop_conn, $string, strlen($string));

    return $bytes_sent;
  }

  /**
   * Checks the POP3 server response for +OK or -ERR
   * @access private
   * @param string $string
   * @return boolean
   */
  private function checkResponse ($string) {
    if (substr($string, 0, 3) !== '+OK') {
      $this->error = array(
        'error' => "Server reported an error: $string",
        'errno' => 0,
        'errstr' => ''
      );

      if ($this->do_debug >= 1) {
        $this->displayErrors();
      }

      return false;
    } else {
      return true;
    }

  }

  /**
   * If debug is enabled, display the error message array
   * @access private
   * @return void
   */
  private function displayErrors () {
    echo '<pre>';

    foreach ($this->error as $single_error) {
      print_r($single_error);
    }

    echo '</pre>';
  }

  /**
   * Takes over from PHP for the socket warning handler
   * @access private
   * @param integer $errno
   * @param string $errstr
   * @param string $errfile
   * @param integer $errline
   * @return void
   */
  private function catchWarning ($errno, $errstr, $errfile, $errline) {
    $this->error[] = array(
      'error' => "Connecting to the POP3 server raised a PHP warning: ",
      'errno' => $errno,
      'errstr' => $errstr
    );
  }

  //  End of class
}
