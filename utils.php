<?php

$localeData = null;

function i8ln($word)
{
    global $locale;
    if ($locale == "en") {
        return $word;
    }

    global $localeData;
    if ($localeData == null) {
        $filepath = 'static/dist/locales/' . $locale . '.min.json';
        if (file_exists($filepath)) {
            $json_contents = file_get_contents($filepath);
            $localeData = json_decode($json_contents, true);
        } else {
            return $word;
        }
    }

    if (isset($localeData[$word])) {
        return $localeData[$word];
    } else {
        return $word;
    }
}

function setSessionCsrfToken()
{
    if (empty($_SESSION['token'])) {
        generateToken();
    }
}

function refreshCsrfToken()
{
    global $sessionLifetime;
    if (time() - $_SESSION['c'] > $sessionLifetime) {
        session_regenerate_id(true);
        generateToken();
    }
    return $_SESSION['token'];
}

function generateToken()
{
    $_SESSION['token'] = base64_encode(openssl_random_pseudo_bytes(32));
    $_SESSION['c'] = time();
}

function validateToken($token)
{
    global $enableCsrf;
    if ((!$enableCsrf) || ($enableCsrf && isset($token) && $token === $_SESSION['token'])) {
        return true;
    } else {
        return false;
    }
}

function generateRandomString($length = 8)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function createUserAccount($user, $password, $new_expire_timestamp)
{
    global $db, $logfile;

    $count = $db->count("users",[
        "user" => $user,
        "login_system" => 'native'
    ]);

    if ($count == 0) {
        $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
        $db->insert("users", [
            "user" => $user,
            "temp_password" => $hashedPwd,
            "expire_timestamp" => $new_expire_timestamp,
            "login_system" => 'native'
        ]);
        
        $logMsg = "INSERT INTO users (user, temp_password, expire_timestamp, login_system) VALUES ('{$user}', '{$hashedPwd}', '{$new_expire_timestamp}', 'native'); -- " . date('Y-m-d H:i:s') . "\r\n";
        file_put_contents($logfile, $logMsg, FILE_APPEND);

        return true;
    } else {
        return false;
    }
}

function resetUserPassword($user, $password, $resetType)
{
    global $db, $logfile;
    
    $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
    if ($resetType == 0) {
        $db->update("users", [
            "temp_password" => $hashedPwd
        ], [
            "user" => $user,
            "login_system" => 'native'
        ]);
        $logMsg = "UPDATE users SET temp_password = '{$hashedPwd}' WHERE user = '{$user}' AND login_system = 'native'; -- " . date('Y-m-d H:i:s') . "\r\n";
    } elseif ($resetType == 1) {
        $db->update("users", [
            "password" => null,
            "temp_password" => $hashedPwd
        ], [
            "user" => $user,
            "login_system" => 'native'
        ]);
        $logMsg = "UPDATE users SET password = null, temp_password = '{$hashedPwd}' WHERE user = '{$user}' AND login_system = 'native'; -- " . date('Y-m-d H:i:s') . "\r\n";
    } else {
        $db->update("users", [
            "password" => $hashedPwd,
            "temp_password" => null
        ], [
            "user" => $user,
            "login_system" => 'native'
        ]);
        $logMsg = "UPDATE users SET password = '{$hashedPwd}', temp_password = null WHERE user = '{$user}' AND login_system = 'native'; -- " . date('Y-m-d H:i:s') . "\r\n";
    }

    file_put_contents($logfile, $logMsg, FILE_APPEND);

    return true;
}

function updateExpireTimestamp($user, $login_system, $new_expire_timestamp)
{
    global $db, $logfile;

    $db->update("users", [
        "expire_timestamp" => $new_expire_timestamp
    ], [
        "user" => $user,
        "login_system" => $login_system
    ]);

    $logMsg = "UPDATE users SET expire_timestamp = '{$new_expire_timestamp}' WHERE user = '{$user}' AND login_system = '{$login_system}'; -- " . date('Y-m-d H:i:s') . "\r\n";
    file_put_contents($logfile, $logMsg, FILE_APPEND);

    return true;
}

function destroyCookiesAndSessions()
{
    global $db;
    
    $db->update("users", [
        "Session_ID" => null
    ], [
        "id" => $_SESSION['user']->id,
        "login_system" => $_SESSION['user']->login_system
    ]);

    unset($_SESSION);
    unset($_COOKIE['LoginCookie']);
    setcookie("LoginCookie", "", time()-3600);
    session_destroy();
    session_write_close();
}

function validateCookie($cookie)
{
    global $db;
    $info = $db->query(
        "SELECT id, user, login_system, expire_timestamp FROM users WHERE Session_ID = :session_id", [
            ":session_id" => $cookie
        ]
    )->fetch();

    if (!empty($info['user'])) {
		$_SESSION['user'] = new \stdClass();
        $_SESSION['user']->id = $info['id'];
        $_SESSION['user']->user = $info['user'];
        $_SESSION['user']->login_system = $info['login_system'];
        $_SESSION['user']->expire_timestamp = $info['expire_timestamp'];
        setcookie("LoginCookie", $cookie, time()+60*60*24*7);
        return true;
    } else {
        destroyCookiesAndSessions();
        return false;
    }
}