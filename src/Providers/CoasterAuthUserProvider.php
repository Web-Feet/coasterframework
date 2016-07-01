<?php namespace CoasterCms\Providers;

use Config;
use Hash;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class CoasterAuthUserProvider implements UserProvider
{

    protected $model;

    public function __construct()
    {
        $this->model = Config::get('auth.providers.users.model');
    }

    public function retrieveByID($identifier)
    {
        return $this->createModel()->newQuery()->where('id', '=', $identifier)->where('active', '=', 1)->first();
    }

    public function retrieveByToken($identifier, $token)
    {
        $model = $this->createModel();
        return $model->newQuery()
            ->where($model->getKeyName(), $identifier)
            ->where($model->getRememberTokenName(), $token)
            ->where('active', '=', 1)
            ->first();
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
        $user->save();
    }

    public function retrieveByCredentials(array $credentials)
    {
        return $this->createModel()->newQuery()->where('email', '=', $credentials['username'])->where('active', '=', 1)->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return Hash::check($credentials['password'], $user->getAuthPassword());
    }

    public function createModel()
    {
        $class = '\\' . ltrim($this->model, '\\');

        return new $class;
    }

}
