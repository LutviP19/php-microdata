<?php

/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Data\Authlogin;

use App\Structs\Authlogin\AuthloginStruct;
use PDO; // new PDO object
use App\Core\Database\ConnectionV2; // Uncomment to use custom DB connection
use App\Core\Support\{Session, Hash};
use App\Core\Auth\{Gate, SodiumAuthV4};

class AuthloginData extends AuthloginStruct
{
    /**
     * static table name for this Class.
     *
     * @var string
     */
    protected static string $table = "users";

    /**
     * Primary key column name
     */
    protected static string $primaryKey = 'id';

    /**
     * Enable or disable Soft Deletes
     */
    protected static bool $useSoftDeletes = true;

    /**
     * Soft delete timestamp column
     */
    protected static string $deletedAtColumn = 'deleted_at';

    /**
     * Auth Vars
     */
    protected $activeClientMiddleware = true;
    protected $headers;
    protected $sessionId;
    protected $sessionName;

    public function __construct(?PDO $pdo = null)
    {
        // Default connection
        $conn = $pdo ?? ConnectionV2::make(config('default_db'));

        // save it to static properties
        static::$staticPdo = $conn;

        // Session state
        $this->headers = getallheaders();
        $this->sessionName = \session_name() ?? '';
        $this->sessionId = $_COOKIE[$this->sessionName] ?? null;
    }

    /**
     * getAllData
     *
     * @param  mixed $id
     * @param  mixed $selectCols
     * @return void
     */
    public function getAllData($id = null, $selectCols = '*')
    {
        if (!is_null($id)) {
            // Logic to getUserProfile
            return static::select($selectCols)->where(static::$primaryKey, '=', $id)->limit(10)->get();
        }

        return static::select($selectCols)->limit(10)->get();
    }

    /**
     * checkCredentials
     */
    public function checkCredentials(?string $email, ?string $password): mixed
    {
        if (empty($email) || empty($password)) {
            return false;
        }

        $user = static::where('email', '=', $email)->whereAnd('status', '=', true)->first();
        // dd($user);

        if (!empty($user)) {
            // Check ULID - Create if not exists
            if (empty($user->ulid)) {
                $newUlid = generateUlid();
                static::updateById($user->id, ['ulid' => $newUlid]);
                $user->ulid = $newUlid;
            }

            $hash = new Hash();
            if ($hash->matchPassword($password, $user->password)) {

                return $user;
            }
        }

        return false;
    }

    /**
     * setLoginSession function, Set session for login user
     *
     * @param  object  $user
     * @param  bool  $isRefresh
     * @return mixed
     */
    protected function setLoginSession($user, $isRefresh = false)
    {
        // Save to the DB if there is no ulid
        // $db->query("UPDATE {$this->table} SET ulid) = ? WHERE id = ?", [$newUlid, $user_id]);

        $sessionData = [];
        foreach ($user as $key => $value) {
            [$newKey, $newValue] = match ($key) {
                'id', 'user_id' => ['user_id', encryptData($value)],
                'ulid'          => ['uid', $value],
                default         => [$key, $value],
            };

            $sessionData[$newKey] = $newValue;
        }

        $gnr = generateRandomString(32, true);
        $userId = $user->ulid ?? $sessionData['uid'] ?? \get_device_fingerprint();
        $sessionData['gnr'] = $gnr;

        // delete cache by uid        
        // delCache("client_token:{$userId}");
        delCache("user_abilities:{$userId}");

        // $validateClient = new \App\Core\Security\Middleware\ValidateClient($userId);
        // $clientToken = $validateClient->getToken();
        // $clientTokenGen = $validateClient->generateToken();

        // $sessionData['client_token'] = $clientTokenGen;

        // Delete session cache in Redis
        $clientToken = generateRandomString();
        $sessionKeyApi = sessionKeyFormat($userId, 'pmdapi-*');
        delCache($sessionKeyApi);

        // if (false === $validateClient->matchToken($clientTokenGen)) {

        //     return false;
        // }

        // Set Basic Sessions
        foreach ($sessionData as $sKey => $sVal) {
            Session::set($sKey, $sVal);
        }

        // Secret initialization
        Session::set('secret', encryptData($clientToken, $gnr));
        $sessionData['secret'] = Session::get('secret');
        $sessionData['authId'] = generateUlid();

        // initAuthToken
        $tokenAuth = "";
        if ($this->activeClientMiddleware) {

            // $authToken = $this->initAuthToken($sessionData);
            // if ($isRefresh) {
            //     $getRefreshToken = getBearerToken();
            //     $tokenAuth = $authToken->refreshToken($getRefreshToken, [
            //         'uid' => $userId,
            //         // "user_permissions" => cacheContent('get', "user_abilities:{$userId}")
            //     ]) ?? null;
            // } else {
            //     $tokenAuth = $authToken->generateToken([
            //         'uid' => $userId,
            //         // "user_permissions" => cacheContent('get', "user_abilities:{$userId}")
            //     ]) ?? null;
            // }

            // Generate token
            $authV4 = new SodiumAuthV4();
            $expToken = time() + config('session.exptime');
            $tokenAuth = $authV4->encode(['uid' => $userId, 'role' => 'admin', 'exp' => $expToken]);
            // dd($tokenAuth);

            $sessionData['tokenAuth'] = $tokenAuth ?? null;
            // $sessionData['tokenAuthRefresh'] = $tokenAuth['refresh_token'] ?? '';

            // Persistence Session in Swoole
            $this->sessionId = session_create_id('pmdapi-');
            $prefixKey = sessionKeyFormat($userId, $this->sessionId);
            $sessionData['sessionKeyApi'] = $prefixKey;
            $sessionData['sessionId'] = $this->sessionId;

            delCache($prefixKey);
            cacheContent('set', $prefixKey, $sessionData, config('session.exptime'));

            // // Cache refresh Token
            // $refreshTokenTimeout = days_to_seconds(7);
            // $prefixKeyRefreshToken = "refresh_token:{$userId}";
            // delCache("refresh_token:{$userId}");
            // cacheContent('set', $prefixKeyRefreshToken, Session::get('tokenAuthRefresh'), $refreshTokenTimeout);

            foreach ($sessionData as $sKey => $sVal) {
                if (Session::has($sKey)) {
                    continue;
                }

                Session::set($sKey, $sVal);
            }
        }

        return $tokenAuth;
    }

}
