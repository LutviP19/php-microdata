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
    // protected static string $primaryKey = 'id';
    protected static string $primaryKey = 'ulid';

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
        $sessionData = [];
        $userId = $user->ulid ?? null;

        if(!$userId || is_null($userId)) {
            unauthorized_response("Invalid Credentials!");
        }

        foreach ($user as $key => $value) {
            [$newKey, $newValue] = match ($key) {
                'id', 'user_id' => ['user_id', encryptData($value)],
                'ulid'          => ['uid', $value],
                default         => [$key, $value],
            };

            $sessionData[$newKey] = $newValue;
        }

        // delete cache by userId        
        // delCache("client_token:{$userId}");
        delCache("user_abilities:{$userId}");

        // $validateClient = new \App\Core\Security\Middleware\ValidateClient($userId);
        // $clientToken = $validateClient->getToken();
        // $clientTokenGen = $validateClient->generateToken();

        // $sessionData['client_token'] = $clientTokenGen;

        // Delete session cache in Redis
        $clientToken = generateRandomString();

        // if (false === $validateClient->matchToken($clientTokenGen)) {
        //     return false;
        // }

        // Set Basic Sessions
        foreach ($sessionData as $sKey => $sVal) {
            Session::set($sKey, $sVal);
        }

        // Secret initialization
        $gnr = generateRandomString(32, true);
        $sessionData['gnr'] = $gnr;
        $sessionData['secret'] = encryptData($clientToken, $gnr);
        $sessionData['authId'] = generateUlid();

        // initAuthToken
        $tokenAuth = "";
        if ($this->activeClientMiddleware) {
            // Persistence Session in
            $this->sessionId = session_create_id('pmdapi-');
            $prefixKey = sessionKeyFormat($userId, $this->sessionId);
            $sessionData['sessionKeyApi'] = $prefixKey;
            $sessionData['sessionId'] = $this->sessionId;

            // Generate token
            $now = time();
            $authV4 = new SodiumAuthV4();
            $expToken = $now + config('session.exptime');
            $expTokenRefresh = 7;
            
            $tokenData = [
                'uid' => $userId, 
                'jti' => $this->sessionId,
                // 'role' => $payload['role'] ?? 'access_backend',
                // "fingerprint" => get_device_fingerprint(true, $userId),                
                "iss" => "php-microdata.{$userId}",
                // "aud" => config("app.url"),
                // "iat" => $now,
                // // "user_permissions" => $user_permissions,
            ];

            // dd($_POST, true);
            // dd($isRefresh);
            if($isRefresh) {
                // delCache(sessionKeyFormat($userId));
                $refreshToken = $_POST['refresh_token'];
                $tokenAuth = Gate::refreshSodiumAuthV4($refreshToken, $tokenData);
            } else {
                $authV4->setExpiration($expToken, $expTokenRefresh);
                $tokenAuth = $authV4->generateToken($tokenData);
            }
            // dd($tokenAuth, true);

            $sessionData['tokenAuth'] = $tokenAuth ?? null;

            // Cache session
            delCache(sessionKeyFormat($userId));
            cacheContent('set', $prefixKey, $sessionData, config('session.exptime'));

            // Cache refresh Token
            if(!is_null($sessionData['tokenAuth']) && isset($sessionData['tokenAuth']['refresh_token'])) {
                $refreshTokenTimeout = days_to_seconds($expTokenRefresh);
                $prefixKeyRefreshToken = "refresh_token:{$userId}:".$this->sessionId;
                delCache("refresh_token:{$userId}*");
                cacheContent('set', $prefixKeyRefreshToken, $sessionData['tokenAuth']['refresh_token'], $refreshTokenTimeout);
            }

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
