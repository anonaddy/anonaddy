<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ListUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anonaddy:list-users
                            {--columns=* : The columns to return}
                            {--username= : The Username of the user}
                            {--json : Output as JSON}
                            {--sort= : The column to sort by}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the list of current users';

    /**
     * The table headers for the command.
     *
     * @var array
     */
    protected $headers = ['ID', 'Username', 'Bandwidth', 'Created_At', 'Updated_At'];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $users = $this->getUsers();

        if (empty($users)) {
            return $this->error("Your application doesn't have any matching users.");
        }

        $this->displayUsers($users);
    }

    /**
     * Compile the users into a displayable format.
     *
     * @return array
     */
    protected function getUsers()
    {
        if ($columns = $this->option('columns')) {
            $users = User::select($columns);
        } else {
            $users = User::select($this->getColumns());
        }

        if ($username = $this->option('username')) {
            $users->where('username', $username);
        }

        if ($sort = $this->option('sort')) {
            $users->orderBy($sort);
        }

        return $users->get()->toArray();
    }

    /**
     * Display the user information on the console.
     *
     * @param  array  $users
     * @return void
     */
    protected function displayUsers(array $users)
    {
        if ($this->option('json')) {
            $this->line(json_encode(array_values($users)));

            return;
        }

        $this->table($this->getHeaders(), $users);
    }

    /**
     * Get the table headers for the visible columns.
     *
     * @return array
     */
    protected function getHeaders()
    {
        return Arr::only($this->headers, array_keys($this->getColumns()));
    }

    /**
     * Get the column names to show (lowercase table headers).
     *
     * @return array
     */
    protected function getColumns()
    {
        $availableColumns = array_map('strtolower', $this->headers);

        if ($columns = $this->option('columns')) {
            return array_intersect($availableColumns, $this->parseColumns($columns));
        }

        return $availableColumns;
    }

    /**
     * Parse the column list.
     *
     * @param  array  $columns
     * @return array
     */
    protected function parseColumns(array $columns)
    {
        $results = [];

        foreach ($columns as $i => $column) {
            if (Str::contains($column, ',')) {
                $results = array_merge($results, explode(',', $column));
            } else {
                $results[] = $column;
            }
        }

        return array_map('strtolower', $results);
    }
}
