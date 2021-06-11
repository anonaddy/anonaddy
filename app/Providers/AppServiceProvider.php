<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Swift_Preferences;

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
        Blade::withoutComponentTags();

        Swift_Preferences::getInstance()->setQPDotEscape(true);

        Builder::macro('jsonPaginate', function (int $maxResults = null, int $defaultSize = null) {
            $maxResults = $maxResults ?? 30;
            $defaultSize = $defaultSize ?? 30;
            $paginationMethod = 'simplePaginate'; // or 'paginate';

            $size = (int) request()->input('page.size', $defaultSize);

            $size = $size > $maxResults ? $maxResults : $size;

            $paginator = $this
                ->{$paginationMethod}($size, ['*'], 'page.number')
                ->setPageName('page[number]')
                ->appends(Arr::except(request()->input(), 'page.number'));

            return $paginator;
        });

        Collection::macro('jsonPaginate', function (int $maxResults = null, int $defaultSize = null, $page = null) {
            $maxResults = $maxResults ?? 30;
            $defaultSize = $defaultSize ?? 30;
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
                    'pageName' => 'page[number]',
                ]
            );
        });
    }
}
