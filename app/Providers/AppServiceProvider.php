<?php

namespace App\Providers;

use App\Models\User;
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

        Http::macro('kiosbank', function () {
            return Http::baseUrl(env('KIOSBANK_URL'))->withOptions(["verify"=>false]);
        });
        
        if(env('LOG_VIEWER_AUTH') === true)
        {
            LogViewer::auth(function ($request) {
                $auth = $request->header('authorization');
                if($auth)
                {
                    $token = explode(' ',$auth);
                    $token = $token[1] ?? '';
                    $basic = base64_decode($token);
                    $basic = explode(':',$basic);
                    $email = $basic[0] ?? '';
                    $user_role = User::where('email', $email)->first()?->role;
                    return ($user_role == User::ADMIN);
                }
                return false;
            });
        }
    }
}
