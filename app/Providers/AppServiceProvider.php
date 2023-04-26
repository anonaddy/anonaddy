<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Sanctum::ignoreMigrations();
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

        Builder::macro('jsonPaginate', function (int $maxResults = null, int $defaultSize = null) {
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

        Collection::macro('jsonPaginate', function (int $maxResults = null, int $defaultSize = null, $page = null) {
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
