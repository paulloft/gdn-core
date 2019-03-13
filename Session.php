<?php
namespace Garden;

use Garden\Helpers\Date;
use Garden\Helpers\Text;
use Garden\Db\DB;

/**
 * @author PaulLoft <info@paulloft.ru>
 * @copyright 2016 Paulloft
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 */
class Session
{
    /**
     * Current user id
     * @var int
     */
    public $userID = 0;

    protected $model;

    use Traits\Singleton;

    private function __construct()
    {
        $this->model = new Model('session', 'sessionID');
        session_start();
    }

    /**
     * Check user authorization
     * @return bool
     */
    public function valid()
    {
        return $this->userID > 0;
    }

    /**
     * set id for current user
     * @param int $userID
     */
    public function start($userID)
    {
        $this->userID = $userID;
    }

    /**
     * Create new session
     * @param int $userID
     * @param bool $remember if false user session will be ended after the close window
     */
    public function create($userID, $remember = false)
    {
        $salt = Config::get('main.hashsalt');
        $lifetime = Config::get('session.lifetime');
        $sessionID = md5($salt.$userID.session_id().time());
        $expireDate = Date::create()
            ->addSeconds($remember ? $lifetime : 60*60*8)
            ->toSql();

        $this->model->insert([
            'sessionID' => $sessionID,
            'userID' => $userID,
            'expire' => $expireDate,
            'lastActivity' => DB::expr('now()'),
            'userAgent' => Gdn::request()->getEnvKey('HTTP_USER_AGENT'),
            'ip' => Gdn::request()->getIP()
        ]);

        $this->setCookie('sessionid', $sessionID, ($remember ? $lifetime : 0));

        $this->start($userID);
    }

    /**
     * Close session for user by userID
     * @param int $userID
     * @param bool $endAll if true all sessions will be closed
     */
    public function end($userID = false, $endAll = false)
    {
        $sessionID = $this->getCookie('sessionid');

        $this->deleteCookie('sessionid');

        $where = $endAll ? ['userID' => $userID ?: $this->userID] : ['sessionID' => $sessionID];
        $this->model->delete($where);

        if ($this->userID === $userID) {
            $this->userID = 0;
        }

        session_destroy();
    }

    /**
     * Get session userID
     * @return bool|mixed
     */
    public function get()
    {
        $sessionID = $this->getCookie('sessionid');
        if (!$sessionID) {
            return false;
        }

        $session = $this->model->getID($sessionID);
        if (!$session) {
            return false;
        }

        $userID = val('userID', $session);
        $expire = val('expire', $session);

        if (time() > strtotime($expire)) {
            $this->end($userID);
            return false;
        }

        return $userID;
    }

    public function update()
    {
        $sessionID = $this->getCookie('sessionid');
        if (!$sessionID) {
            return;
        }

        $this->model->update($sessionID, [
            'lastActivity' => DB::expr('now()'),
            'lastIP' => Gdn::request()->getIP()
        ]);
    }

    /**
     * set cookie value
     * @param string $name
     * @param string $value
     * @param int $lifetime
     */
    public function setCookie($name, $value, $lifetime)
    {
        $name   = Config::get('session.cookie.prefix').$name;
        $path   = Config::get('session.cookie.path');
        $domain = Config::get('session.cookie.domain');


        // If the domain being set is completely incompatible with the current domain then make the domain work.
        $host = Gdn::request()->getHost();
        if (!Text::strEnds($host, trim($domain, '.'))) {
            $domain = '';
        }

        if ($lifetime < 0) {
            unset($_COOKIE[$name]);
            $expires = -1;
        } else {
            $_COOKIE[$name] = $value;
            $expires = $lifetime ? time() + $lifetime : 0;
        }
        setcookie($name, $value, $expires, $path, $domain);
    }

    /**
     * get cookie value
     * @param $name
     * @param string|bool $default if cookie is non exists return $default value
     * @param bool $usePrefix use or not system cookie prefix
     * @return string|bool
     */
    public function getCookie($name, $default = false, $usePrefix = true)
    {
        $name = $usePrefix ? Config::get('session.cookie.prefix').$name : $name;
        return val($name, $_COOKIE, $default);
    }

    /**
     * delete cookie
     * @param string $name
     */
    public function deleteCookie($name)
    {
        $this->setCookie($name, null, -1);
    }
}