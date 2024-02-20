<?php

namespace App\Providers;

use App\Models\Constanta\ProductType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::directive('rp', function ( $expression ) { return "<?php echo number_format($expression,0,',','.'); ?>"; });


        if($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
        Http::macro('kiosbank', function () {
            return Http::baseUrl(env('KIOSBANK_URL'))->withOptions(["verify"=>false]);
        });

        Builder::macro('myWhereLike', function($columns, $search) {
            if($search){
                $this->where(function($query) use ($columns, $search) {
                  foreach(\Arr::wrap($columns) as $column) {
                    $query->orWhere($column, 'LIKE', "%{$search}%");
                  }
                });
            }
           
            return $this;
        });

        Builder::macro('myWhereLikeCol',  function($filter, $colum_filter = []) {
            $colum_model = empty($colum_filter) ? $this->model->getFillable() : $colum_filter;
            if($filter){
                $this->where(function($query) use ($filter, $colum_model) {
                    foreach($filter as $column => $search) {
                        if($search!= null){
                            if(in_array($column,$colum_model)){
                                $query->orWhere($column, 'LIKE', "%{$search}%");
                            }
                        }
                    }
                });
            }
           
            return $this;
        });

        Builder::macro('myWhereLikeStart', function($columns, $search) {
            if($search){
                $this->where(function($query) use ($columns, $search) {
                  foreach(\Arr::wrap($columns) as $column) {
                    $query->orWhere($column, 'LIKE', "{$search}%");
                  }
                });
            }
           
            return $this;
        });

        Builder::macro('myWhereLikeStartCol',  function($filter, $colum_filter = []) {
            $colum_model = empty($colum_filter) ? $this->model->getFillable() : $colum_filter;
            if($filter){
                $this->where(function($query) use ($filter, $colum_model) {
                    foreach($filter as $column => $search) {
                        if($search!= null){
                            if(in_array($column,$colum_model)){
                                $query->orWhere($column, 'LIKE', "{$search}%");
                            }
                        }
                    }
                });
            }
           
            return $this;
        });

        Builder::macro('myWheres', function($filter, $colum_filter = []) {
            $colum_model = empty($colum_filter) ? $this->model->getFillable() : $colum_filter;
            if($filter){
                $this->where(function($query) use ($filter, $colum_model) {
                    foreach($filter as $column => $search) {
                        if($search!= null){
                            if(in_array($column,$colum_model)){
                                $query->where($column, $search);
                            }
                        }
                    }
                });
            }
           
            return $this;
        });
        // if(env('LOG_VIEWER_AUTH') === true)
        // {
        //     LogViewer::auth(function ($request) {
        //         $auth = $request->header('authorization');
        //         if($auth)
        //         {
        //             $token = explode(' ',$auth);
        //             $token = $token[1] ?? '';
        //             $basic = base64_decode($token);
        //             $basic = explode(':',$basic);
        //             $email = $basic[0] ?? '';
        //             $user_role = User::where('email', $email)->first()?->role;
        //             return ($user_role == User::ADMIN);
        //         }
        //         return false;
        //     });
        // }
    }
}
