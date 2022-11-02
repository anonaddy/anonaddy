<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Sanctum::ignoreMigrations();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Builder::macro('jsonPaginate', function (int $maxResults = null, int $defaultSize = null) {
            $maxResults = $maxResults ?? 100;
            $defaultSize = $defaultSize ?? 100;
            $paginationMethod = 'paginate'; // 'simplePaginate' or 'paginate';

            $size = (int) request()->input('page.size', $defaultSize);

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
            $size = (int) request()->input('page.size', $defaultSize);
            $size = $size > $maxResults ? $maxResults : $size;

            $page = (int) request()->input('page.number', 1);

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
