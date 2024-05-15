<?php

namespace App\Providers;

use App\Http\Responses\LoginViewResponse;
use App\Http\Responses\RegisterSuccessResponse;
use App\Http\Responses\RegisterViewResponse;
use App\Models\PersonalAccessToken;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\Sanctum;
use LaravelWebauthn\Services\Webauthn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Webauthn::registerViewResponseUsing(RegisterViewResponse::class);
        Webauthn::registerSuccessResponseUsing(RegisterSuccessResponse::class);
        Webauthn::loginViewResponseUsing(LoginViewResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventAccessingMissingAttributes();
        Model::preventSilentlyDiscardingAttributes();
        Model::preventLazyLoading();

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Password::defaults(function () {
            $rule = Password::min(8);

            return $this->app->isProduction()
                        ? $rule->letters()->uncompromised()
                        : $rule;
        });

        Builder::macro('jsonPaginate', function (?int $maxResults = null, ?int $defaultSize = null) {
            $maxResults = $maxResults ?? 100;
            $defaultSize = $defaultSize ?? 100;
            $paginationMethod = 'paginate'; // 'simplePaginate' or 'paginate';

            $size = (int) is_null(request()->input('page.size')) ? $defaultSize : request()->input('page.size');

            $size = $size > $maxResults ? $maxResults : $size;

            $paginator = $this
                ->{$paginationMethod}($size, ['*'], 'page.number')
                ->setPageName('page[number]')
                ->appends(Arr::except(request()->input(), 'page.number'));

            return $paginator;
        });

        Collection::macro('paginate', function (?int $defaultSize = null, ?int $maxResults = null, $page = null) {
            $maxResults = $maxResults ?? 100;
            $defaultSize = $defaultSize ?? 25;
            $size = (int) is_null(request()->input('pageSize')) ? $defaultSize : request()->input('pageSize');

            $size = $size > $maxResults ? $maxResults : $size;
            $page = (int) is_null(request()->input('page')) ? 1 : request()->input('page');

            return new LengthAwarePaginator(
                $this->forPage($page, $size)->values(),
                $this->count(),
                $size,
                $page,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'query' => Arr::except(LengthAwarePaginator::resolveQueryString(), 'page'),
                    'pageName' => 'page',
                ]
            );
        });

        Collection::macro('jsonPaginate', function (?int $maxResults = null, ?int $defaultSize = null, $page = null) {
            $maxResults = $maxResults ?? 100;
            $defaultSize = $defaultSize ?? 100;
            $size = (int) is_null(request()->input('page.size')) ? $defaultSize : request()->input('page.size');
            $size = $size > $maxResults ? $maxResults : $size;

            $page = (int) is_null(request()->input('page.number')) ? 1 : request()->input('page.number');

            return new LengthAwarePaginator(
                $this->forPage($page, $size),
                $this->count(),
                $size,
                $page,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'query' => Arr::except(LengthAwarePaginator::resolveQueryString(), 'page.number'),
                    'pageName' => 'page[number]',
                ]
            );
        });
    }
}
