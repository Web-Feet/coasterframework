<?php namespace CoasterCms\Providers;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class CoasterAuthUserProvider extends EloquentUserProvider
{

    /**
     * @param mixed $identifier
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function retrieveByID($identifier)
    {
        return $this->createModel()->newQuery()->where('id', '=', $identifier)->where('active', '=', 1)->first();
    }

    /**
     * @param mixed $identifier
     * @param string $token
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function retrieveByToken($identifier, $token)
    {
        $model = $this->createModel();

        return $model->newQuery()
            ->where($model->getKeyName(), $identifier)
            ->where($model->getRememberTokenName(), $token)
            ->where('active', '=', 1)
            ->first();
    }

    /**
     * @param Authenticatable $user
     * @param string $token
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
        $user->save();
    }

    /**
     * @param array $credentials
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function retrieveByCredentials(array $credentials)
    {
        return $this->createModel()->newQuery()->where('email', '=', $credentials['username'])->where('active', '=', 1)->first();
    }

}
